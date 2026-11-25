<?php
/** Chat SMTT nativo: texto, emoji e links, sem anexos. */
if (!defined('ABSPATH')) { exit; }

final class SMTT_Intranet_Native_Chat {
    const DB_VERSION = '2';
    const OPTION_SCHEMA = 'smtt_native_chat_schema';
    const OPTION_SETTINGS = 'smtt_native_chat_settings';
    const REST_NS = 'smtt-intranet/v1';

    private static function table($name) {
        global $wpdb;
        return $wpdb->prefix . 'smtt_chat_' . $name;
    }

    public static function register() {
        add_action('init', array(__CLASS__, 'install_if_needed'), 23);
        add_action('rest_api_init', array(__CLASS__, 'routes'));
        add_filter('rest_pre_serve_request', array(__CLASS__, 'no_cache_rest'), 10, 4);
        add_action('admin_post_smtt_chat_save_settings', array(__CLASS__, 'save_settings'));
        add_action('admin_post_smtt_chat_create_group', array(__CLASS__, 'admin_create_group'));
        add_action('admin_post_smtt_chat_group_members', array(__CLASS__, 'admin_change_members'));
        add_action('admin_post_smtt_chat_group_update', array(__CLASS__, 'admin_update_group'));
    }

    public static function settings() {
        $current = get_option(self::OPTION_SETTINGS, array());
        return wp_parse_args(is_array($current) ? $current : array(), array(
            'enabled' => '1',
            'allow_groups' => '1', // legado: apenas o CPD poderá administrar grupos
            'max_length' => 2000,
        ));
    }

    public static function enabled() {
        $settings = self::settings();
        return (string) $settings['enabled'] === '1';
    }

    public static function install_if_needed() {
        if (get_option(self::OPTION_SCHEMA) === self::DB_VERSION) { return; }
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $c = self::table('conversations');
        $p = self::table('participants');
        $m = self::table('messages');
        // O índice UNIQUE em direct_key impede duplicação das conversas privadas.
        $sql1 = "CREATE TABLE {$c} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            type varchar(12) NOT NULL,
            title varchar(150) NOT NULL DEFAULT '',
            direct_key varchar(90) DEFAULT NULL,
            group_source varchar(12) NOT NULL DEFAULT 'manual',
            ad_group_sid varchar(100) DEFAULT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_by bigint(20) unsigned NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            last_message_id bigint(20) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY direct_key (direct_key),
            UNIQUE KEY ad_group_sid (ad_group_sid),
            KEY group_source (group_source),
            KEY updated_at (updated_at),
            KEY last_message_id (last_message_id)
        ) {$charset};";
        $sql2 = "CREATE TABLE {$p} (
            conversation_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            role varchar(16) NOT NULL DEFAULT 'member',
            joined_at datetime NOT NULL,
            last_read_message_id bigint(20) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY  (conversation_id,user_id),
            KEY user_conversation (user_id,conversation_id)
        ) {$charset};";
        $sql3 = "CREATE TABLE {$m} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            conversation_id bigint(20) unsigned NOT NULL,
            sender_id bigint(20) unsigned NOT NULL,
            body text NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY conversation_order (conversation_id,id),
            KEY sender_id (sender_id)
        ) {$charset};";
        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);
        foreach (array($c, $p, $m) as $table) {
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))) !== $table) {
                return; // Nova tentativa numa próxima requisição, sem marcar instalação incompleta.
            }
        }
        // A migração v2 precisa ter finalizado antes de atualizar o marcador do esquema.
        foreach (array('group_source','ad_group_sid','is_active') as $column) {
            if (!$wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$c} LIKE %s", $column))) {
                return;
            }
        }
        update_option(self::OPTION_SCHEMA, self::DB_VERSION, false);
    }

    private static function text_length($value) {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private static function text_slice($value, $limit) {
        return function_exists('mb_substr') ? mb_substr($value, 0, $limit, 'UTF-8') : substr($value, 0, $limit);
    }

    public static function routes() {
        $ns = self::REST_NS;
        $read = array('permission_callback' => array(__CLASS__, 'authorize'));
        register_rest_route($ns, '/chat/summary', array_merge($read, array('methods' => 'GET', 'callback' => array(__CLASS__, 'summary'))));
        register_rest_route($ns, '/chat/people', array_merge($read, array('methods' => 'GET', 'callback' => array(__CLASS__, 'people'))));
        register_rest_route($ns, '/chat/conversations', array(
            array_merge($read, array('methods' => 'GET', 'callback' => array(__CLASS__, 'summary'))),
            array_merge($read, array('methods' => 'POST', 'callback' => array(__CLASS__, 'create_conversation'))),
        ));
        register_rest_route($ns, '/chat/conversations/(?P<id>\d+)/messages', array(
            array_merge($read, array('methods' => 'GET', 'callback' => array(__CLASS__, 'messages'))),
            array_merge($read, array('methods' => 'POST', 'callback' => array(__CLASS__, 'send_message'))),
        ));
        register_rest_route($ns, '/chat/conversations/(?P<id>\d+)/read', array_merge($read, array('methods' => 'POST', 'callback' => array(__CLASS__, 'mark_read'))));
    }

    public static function no_cache_rest($served, $result, $request, $server) {
        if (strpos($request->get_route(), '/smtt-intranet/v1/chat/') === 0) { nocache_headers(); }
        return $served;
    }

    public static function authorize() {
        if (!is_user_logged_in()) {
            return new WP_Error('smtt_chat_login', 'Faça login para utilizar o Chat SMTT.', array('status' => 401));
        }
        if (!self::enabled()) {
            return new WP_Error('smtt_chat_disabled', 'O Chat SMTT está desativado.', array('status' => 403));
        }
        self::sync_ad_memberships();
        return true;
    }

    /** Grupos AD: associação verificada no servidor, nunca por dados enviados pelo navegador. */
    private static function is_member($id, $uid = 0) {
        global $wpdb;
        $uid = $uid ?: get_current_user_id();
        $c = self::table('conversations');
        $p = self::table('participants');
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT c.group_source,c.ad_group_sid FROM {$c} c INNER JOIN {$p} p ON p.conversation_id=c.id
             WHERE c.id=%d AND c.is_active=1 AND p.user_id=%d LIMIT 1", $id, $uid
        ), ARRAY_A);
        if (!$row) { return false; }
        if ($row['group_source'] !== 'ad') { return true; }
        // Nunca reutilizar participação AD antiga para autorizar acesso após revogação.
        if ($uid !== get_current_user_id()) { return false; }
        $identity = SMTT_Intranet_Corporate_Identity::current();
        return !is_wp_error($identity) && in_array($row['ad_group_sid'], $identity['groups'] ?? array(), true);
    }

    /** A associação AD acompanha a identidade corporativa do usuário atual. */
    private static function sync_ad_memberships() {
        global $wpdb;
        $uid = get_current_user_id();
        if (!$uid) { return; }
        $conversations = self::table('conversations');
        $participants = self::table('participants');
        $rows = $wpdb->get_results(
            "SELECT id,ad_group_sid FROM {$conversations} WHERE group_source='ad' AND is_active=1", ARRAY_A
        );
        if (!$rows) { return; } // Não consultar o AD quando não há canais corporativos.
        $identity = SMTT_Intranet_Corporate_Identity::current();
        // Sem SSO válido, nenhum grupo vinculado ao AD permanece autorizado.
        $sids = is_wp_error($identity) ? array() : (array) ($identity['groups'] ?? array());
        $now = current_time('mysql',true);
        foreach ((array) $rows as $row) {
            $gid=(int)$row['id'];
            if (in_array($row['ad_group_sid'], $sids, true)) {
                // Inserção idempotente. O funcionário vê o grupo ao acessar o chat.
                $wpdb->query($wpdb->prepare(
                    "INSERT IGNORE INTO {$participants} (conversation_id,user_id,role,joined_at,last_read_message_id)
                     VALUES (%d,%d,'member',%s,0)", $gid,$uid,$now
                ));
            } else {
                $wpdb->delete($participants, array('conversation_id'=>$gid,'user_id'=>$uid),array('%d','%d'));
            }
        }
    }

    private static function member_or_error($id) {
        if ($id <= 0 || !self::is_member($id)) {
            return new WP_Error('smtt_chat_forbidden', 'Conversa não disponível para este usuário.', array('status' => 403));
        }
        return true;
    }

    private static function valid_target($uid) {
        $uid = absint($uid);
        if (!$uid || $uid === get_current_user_id() || !get_userdata($uid)) { return false; }
        return (bool) apply_filters('smtt_chat_can_message_user', true, get_current_user_id(), $uid);
    }

    public static function people($request) {
        global $wpdb;
        $q = sanitize_text_field((string) $request->get_param('q'));
        if (self::text_length($q) < 2) { return rest_ensure_response(array('people' => array())); }
        $q = self::text_slice($q, 60);
        $like = '%' . $wpdb->esc_like($q) . '%';
        $found = $wpdb->get_results($wpdb->prepare(
            "SELECT ID, display_name FROM {$wpdb->users} WHERE ID <> %d AND user_status = 0 AND (display_name LIKE %s OR user_login LIKE %s) ORDER BY display_name ASC LIMIT 20",
            get_current_user_id(), $like, $like
        ), ARRAY_A);
        $people = array();
        foreach ((array) $found as $user) {
            if (self::valid_target($user['ID'])) {
                $people[] = array('id' => (int) $user['ID'], 'name' => $user['display_name'] ?: 'Usuário ' . $user['ID']);
            }
        }
        return rest_ensure_response(array('people' => $people));
    }

    public static function summary($request) {
        global $wpdb;
        $uid = get_current_user_id();
        $limit = max(1, min(50, absint($request->get_param('limit') ?: 20)));
        $conversations = self::table('conversations');
        $participants = self::table('participants');
        $messages = self::table('messages');
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT c.id,c.type,c.title,c.group_source,c.last_message_id,c.updated_at,p.last_read_message_id,
                    m.body AS last_text,m.sender_id AS last_sender, u.display_name AS sender_name,
                    (SELECT ux.display_name FROM {$participants} px JOIN {$wpdb->users} ux ON ux.ID=px.user_id WHERE px.conversation_id=c.id AND px.user_id<>%d ORDER BY px.user_id LIMIT 1) AS other_name
             FROM {$participants} p JOIN {$conversations} c ON c.id=p.conversation_id
             LEFT JOIN {$messages} m ON m.id=c.last_message_id AND m.created_at>=p.joined_at
             LEFT JOIN {$wpdb->users} u ON u.ID=m.sender_id
             WHERE p.user_id=%d AND c.is_active=1 ORDER BY c.updated_at DESC,c.id DESC LIMIT %d",
            $uid, $uid, $limit
        ), ARRAY_A);
        $unread = $wpdb->get_results($wpdb->prepare(
            "SELECT p.conversation_id,COUNT(*) AS unread FROM {$participants} p
             JOIN {$messages} m ON m.conversation_id=p.conversation_id AND m.id>p.last_read_message_id AND m.sender_id<>p.user_id AND m.created_at>=p.joined_at
             JOIN {$conversations} c ON c.id=p.conversation_id AND c.is_active=1
             WHERE p.user_id=%d GROUP BY p.conversation_id", $uid
        ), ARRAY_A);
        $counts = array(); $total = 0;
        foreach ((array) $unread as $item) { $counts[(int)$item['conversation_id']] = (int)$item['unread']; $total += (int)$item['unread']; }
        $result = array();
        foreach ((array) $rows as $row) {
            $id = (int) $row['id'];
            $result[] = array(
                'id' => $id, 'type' => $row['type'], 'group_source' => $row['group_source'],
                'title' => $row['type'] === 'direct' ? ($row['other_name'] ?: 'Conversa privada') : $row['title'],
                'last_text' => $row['last_text'] === null ? '' : $row['last_text'],
                'last_sender' => (int)$row['last_sender'],
                'last_sender_name' => $row['sender_name'] ?: '',
                'last_message_id' => (int)$row['last_message_id'],
                'updated_at' => $row['updated_at'],
                'unread' => $counts[$id] ?? 0,
            );
        }
        return rest_ensure_response(array('conversations' => $result, 'unread_total' => $total));
    }

    public static function create_conversation($request) {
        global $wpdb;
        $uid = get_current_user_id();
        $kind = sanitize_key((string) $request->get_param('type'));
        $table = self::table('conversations');
        $participants = self::table('participants');
        $now = current_time('mysql', true);
        if ($kind === 'direct') {
            $target = absint($request->get_param('recipient_id'));
            if (!self::valid_target($target)) { return new WP_Error('smtt_chat_target', 'Selecione outro funcionário válido.', array('status'=>400)); }
            $key = min($uid, $target) . ':' . max($uid, $target);
            $id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE direct_key=%s LIMIT 1", $key));
            if (!$id) {
                $inserted = $wpdb->insert($table, array('type'=>'direct','title'=>'','direct_key'=>$key,'created_by'=>$uid,'created_at'=>$now,'updated_at'=>$now), array('%s','%s','%s','%d','%s','%s'));
                $id = $inserted ? (int) $wpdb->insert_id : (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE direct_key=%s LIMIT 1", $key));
            }
            if (!$id) { return new WP_Error('smtt_chat_db', 'Não foi possível abrir a conversa.', array('status'=>500)); }
            foreach (array($uid,$target) as $person) {
                $wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$participants} (conversation_id,user_id,role,joined_at,last_read_message_id) VALUES (%d,%d,%s,%s,0)", $id,$person,'member',$now));
            }
            return rest_ensure_response(array('id'=>$id));
        }
        // Nenhum usuário cria grupos pelo chat, incluindo chamadas diretas à API.
        // Somente o CPD pode criá-los na Central Administrativa protegida.
        return new WP_Error('smtt_chat_group_cpd', 'Grupos são criados somente pelo CPD na Central Administrativa.', array('status'=>403));
    }

    public static function messages($request) {
        global $wpdb;
        $id = absint($request['id']);
        $access = self::member_or_error($id);
        if (is_wp_error($access)) { return $access; }
        $before = absint($request->get_param('before'));
        $table = self::table('messages');
        $p = self::table('participants');
        $joined = $wpdb->get_var($wpdb->prepare("SELECT joined_at FROM {$p} WHERE conversation_id=%d AND user_id=%d", $id, get_current_user_id()));
        if (!$joined) { return new WP_Error('smtt_chat_forbidden', 'Conversa indisponível.', array('status'=>403)); }
        if ($before) {
            $query = $wpdb->prepare("SELECT m.id,m.sender_id,m.body,m.created_at,u.display_name AS sender_name FROM {$table} m LEFT JOIN {$wpdb->users} u ON u.ID=m.sender_id WHERE m.conversation_id=%d AND m.id<%d AND m.created_at >= %s ORDER BY m.id DESC LIMIT 31",$id,$before,$joined);
        } else {
            $query = $wpdb->prepare("SELECT m.id,m.sender_id,m.body,m.created_at,u.display_name AS sender_name FROM {$table} m LEFT JOIN {$wpdb->users} u ON u.ID=m.sender_id WHERE m.conversation_id=%d AND m.created_at >= %s ORDER BY m.id DESC LIMIT 31",$id,$joined);
        }
        $rows = (array) $wpdb->get_results($query, ARRAY_A);
        $has_more = count($rows) > 30;
        if ($has_more) { array_pop($rows); }
        $rows = array_reverse($rows);
        $result = array();
        foreach ($rows as $row) { $result[] = array('id'=>(int)$row['id'],'sender_id'=>(int)$row['sender_id'],'sender_name'=>$row['sender_name']?:'Usuário','body'=>$row['body'],'created_at'=>$row['created_at']); }
        return rest_ensure_response(array('messages'=>$result,'has_more'=>$has_more));
    }

    public static function send_message($request) {
        global $wpdb;
        $uid = get_current_user_id();
        $id = absint($request['id']);
        $access = self::member_or_error($id);
        if (is_wp_error($access)) { return $access; }
        $raw = $request->get_param('text');
        if (!is_string($raw)) { return new WP_Error('smtt_chat_text','Mensagem inválida.',array('status'=>400)); }
        $body = trim(sanitize_textarea_field(str_replace(array("\r\n","\r"),"\n",$raw)));
        $limit = max(200,min(5000,(int)self::settings()['max_length']));
        if (!$body || self::text_length($body) > $limit) { return new WP_Error('smtt_chat_length','Mensagem vazia ou acima do limite de '.$limit.' caracteres.',array('status'=>400)); }
        // Limite simples contra envio repetitivo/robôs. Sem anexos, HTML ou arquivos.
        $rate_key = 'smtt_chat_rate_' . $uid;
        $rate = get_transient($rate_key);
        if (is_array($rate) && (int)($rate['at']??0) > time()-2) { return new WP_Error('smtt_chat_rate','Envie a próxima mensagem após um instante.',array('status'=>429)); }
        set_transient($rate_key,array('at'=>time()),5);
        $now = current_time('mysql',true);
        $table = self::table('messages');
        if (!$wpdb->insert($table,array('conversation_id'=>$id,'sender_id'=>$uid,'body'=>$body,'created_at'=>$now),array('%d','%d','%s','%s'))) {
            return new WP_Error('smtt_chat_db','Mensagem não enviada.',array('status'=>500));
        }
        $mid = (int)$wpdb->insert_id;
        $conversation = self::table('conversations');
        $wpdb->query($wpdb->prepare("UPDATE {$conversation} SET last_message_id=GREATEST(last_message_id,%d),updated_at=%s WHERE id=%d",$mid,$now,$id));
        return rest_ensure_response(array('id'=>$mid));
    }

    public static function mark_read($request) {
        global $wpdb;
        $id = absint($request['id']);
        $access = self::member_or_error($id);
        if (is_wp_error($access)) { return $access; }
        $conversation = self::table('conversations');
        $participants = self::table('participants');
        $uid = get_current_user_id();
        $last = (int)$wpdb->get_var($wpdb->prepare("SELECT last_message_id FROM {$conversation} WHERE id=%d",$id));
        $wpdb->query($wpdb->prepare("UPDATE {$participants} SET last_read_message_id=GREATEST(last_read_message_id,%d) WHERE conversation_id=%d AND user_id=%d",$last,$id,$uid));
        return rest_ensure_response(array('ok'=>true));
    }

    public static function statistics() {
        global $wpdb;
        return array(
            'conversations' => (int)$wpdb->get_var('SELECT COUNT(*) FROM '.self::table('conversations')),
            'messages' => (int)$wpdb->get_var('SELECT COUNT(*) FROM '.self::table('messages')),
            'participants' => (int)$wpdb->get_var('SELECT COUNT(DISTINCT user_id) FROM '.self::table('participants')),
        );
    }

    private static function require_cpd($nonce) {
        if (!class_exists('SMTT_Intranet_Corporate_Identity') || !SMTT_Intranet_Corporate_Identity::is_admin()) {
            wp_die('Apenas o CPD pode administrar grupos.', 'Acesso restrito',array('response'=>403));
        }
        check_admin_referer($nonce);
    }

    private static function back_to_groups($notice = '') {
        wp_safe_redirect(add_query_arg(array('section'=>'chat', 'smtt_notice'=>$notice), home_url('/administracao/')));
        exit;
    }

    /** Consulta administrativa: nomes e quantidade, sem abrir conteúdo privado. */
    public static function admin_groups() {
        if (!SMTT_Intranet_Corporate_Identity::is_admin()) { return array(); }
        global $wpdb;
        $c=self::table('conversations'); $p=self::table('participants');
        return (array)$wpdb->get_results(
            "SELECT c.id,c.title,c.group_source,c.ad_group_sid,c.is_active,COUNT(p.user_id) AS members
             FROM {$c} c LEFT JOIN {$p} p ON p.conversation_id=c.id
             WHERE c.type='group' GROUP BY c.id ORDER BY c.is_active DESC,c.title ASC LIMIT 250", ARRAY_A
        );
    }

    public static function admin_group_members($group_id) {
        if (!SMTT_Intranet_Corporate_Identity::is_admin()) { return array(); }
        global $wpdb;
        $p=self::table('participants');
        return (array)$wpdb->get_results($wpdb->prepare(
            "SELECT p.user_id,u.user_login,u.display_name FROM {$p} p JOIN {$wpdb->users} u ON u.ID=p.user_id
             WHERE p.conversation_id=%d ORDER BY u.display_name ASC LIMIT 300",$group_id
        ),ARRAY_A);
    }

    private static function parse_users($raw) {
        $names = preg_split('/[\s,;]+/', (string)$raw, -1, PREG_SPLIT_NO_EMPTY);
        if (count($names)>200) { return new WP_Error('smtt_chat_members_limit','Informe no máximo 200 usuários por operação.'); }
        $ids=array(); $unknown=array();
        foreach ($names as $name) {
            $user = get_user_by('login', sanitize_user($name, true));
            if (!$user) { $unknown[]=$name; continue; }
            $ids[$user->ID]=true;
        }
        if ($unknown) { return new WP_Error('smtt_chat_members_unknown','Usuários não cadastrados no WordPress: '.implode(', ',array_slice($unknown,0,5))); }
        return array_keys($ids);
    }

    public static function admin_create_group() {
        self::require_cpd('smtt_chat_create_group');
        global $wpdb;
        $type = sanitize_key((string)($_POST['group_source']??'manual'));
        $title = self::text_slice(trim(sanitize_text_field(wp_unslash($_POST['title']??''))),100);
        $sid = sanitize_text_field(wp_unslash($_POST['ad_group_sid']??''));
        if ($type !== 'manual' && $type !== 'ad') { wp_die('Tipo de grupo inválido.', 'Chat SMTT',array('response'=>400)); }
        if ($type === 'ad') {
            if (!SMTT_Intranet_AD::valid_group_sid($sid)) { wp_die('Selecione um grupo de segurança ativo do AD sincronizado.', 'Chat SMTT',array('response'=>400)); }
            $found = $wpdb->get_var($wpdb->prepare('SELECT id FROM '.self::table('conversations').' WHERE ad_group_sid=%s LIMIT 1',$sid));
            if ($found) { wp_die('Este grupo AD já foi vinculado ao Chat SMTT.', 'Chat SMTT',array('response'=>409)); }
        } else { $sid = null; }
        if ($title === '') { wp_die('Informe o nome do grupo.', 'Chat SMTT',array('response'=>400)); }
        $users = $type === 'manual' ? self::parse_users(wp_unslash($_POST['members_list']??'')) : array();
        if (is_wp_error($users)) { wp_die(esc_html($users->get_error_message()), 'Chat SMTT',array('response'=>400)); }
        $now = current_time('mysql',true);
        $ok = $wpdb->insert(self::table('conversations'),array(
            'type'=>'group','title'=>$title,'group_source'=>$type,'ad_group_sid'=>$sid,
            'created_by'=>get_current_user_id(),'created_at'=>$now,'updated_at'=>$now
        ),array('%s','%s','%s','%s','%d','%s','%s'));
        if (!$ok) { wp_die('Falha ao cadastrar o grupo.', 'Chat SMTT',array('response'=>500)); }
        $id=(int)$wpdb->insert_id;
        foreach ($users as $uid) {
            $wpdb->insert(self::table('participants'),array(
                'conversation_id'=>$id,'user_id'=>$uid,'role'=>'member',
                'joined_at'=>$now,'last_read_message_id'=>0
            ),array('%d','%d','%s','%s','%d'));
        }
        SMTT_Intranet_Policy::audit('chat_group_create', 'Chat group #'.$id.' '. $type);
        self::back_to_groups('chat_group_created');
    }

    public static function admin_change_members() {
        self::require_cpd('smtt_chat_group_members');
        global $wpdb;
        $id=absint($_POST['group_id']??0);
        $c=self::table('conversations');
        $source=$wpdb->get_var($wpdb->prepare("SELECT group_source FROM {$c} WHERE id=%d AND type='group' AND is_active=1",$id));
        if ($source!=='manual') { wp_die('Participantes de grupos AD são gerenciados somente no domínio.', 'Chat SMTT',array('response'=>403)); }
        $operation = sanitize_key($_POST['member_action']??'');
        $p=self::table('participants');
        if ($operation==='add') {
            $ids = self::parse_users(wp_unslash($_POST['members_list']??''));
            if (is_wp_error($ids)) { wp_die(esc_html($ids->get_error_message()),'Chat SMTT',array('response'=>400)); }
            foreach ($ids as $uid) {
                $wpdb->query($wpdb->prepare(
                    "INSERT IGNORE INTO {$p} (conversation_id,user_id,role,joined_at,last_read_message_id)
                     VALUES (%d,%d,'member',%s,0)",$id,$uid,current_time('mysql',true)
                ));
            }
        } elseif ($operation==='remove') {
            $uid=absint($_POST['member_id']??0);
            $wpdb->delete($p,array('conversation_id'=>$id,'user_id'=>$uid),array('%d','%d'));
        } else { wp_die('Operação inválida.', 'Chat SMTT',array('response'=>400)); }
        SMTT_Intranet_Policy::audit('chat_members_'.$operation,'Chat group #'.$id);
        self::back_to_groups('chat_group_updated');
    }

    public static function admin_update_group() {
        self::require_cpd('smtt_chat_group_update');
        global $wpdb;
        $id=absint($_POST['group_id']??0);
        $c=self::table('conversations');
        $existing=$wpdb->get_row($wpdb->prepare("SELECT id,group_source,is_active FROM {$c} WHERE id=%d AND type='group'",$id),ARRAY_A);
        if (!$existing) { wp_die('Grupo não encontrado.','Chat SMTT',array('response'=>404)); }
        $action=sanitize_key($_POST['group_action']??'');
        if ($action==='rename') {
            $title = self::text_slice(trim(sanitize_text_field(wp_unslash($_POST['title']??''))),100);
            if (!$title) { wp_die('Informe um título válido.', 'Chat SMTT',array('response'=>400)); }
            $wpdb->update($c,array('title'=>$title),array('id'=>$id),array('%s'),array('%d'));
        } elseif ($action==='archive') {
            // Arquivar sem apagar histórico. Um grupo AD arquivado não será reativado automaticamente.
            $wpdb->update($c,array('is_active'=>0),array('id'=>$id),array('%d'),array('%d'));
        } elseif ($action==='restore') {
            $wpdb->update($c,array('is_active'=>1),array('id'=>$id),array('%d'),array('%d'));
        } else { wp_die('Operação inválida.','Chat SMTT',array('response'=>400)); }
        SMTT_Intranet_Policy::audit('chat_group_'.$action, 'Chat group #'.$id);
        self::back_to_groups('chat_group_updated');
    }

    public static function save_settings() {
        if (!class_exists('SMTT_Intranet_Corporate_Identity') || !SMTT_Intranet_Corporate_Identity::is_admin()) {
            wp_die('Sem autorização.', 'Chat SMTT', array('response'=>403));
        }
        check_admin_referer('smtt_chat_settings');
        $settings = array(
            'enabled' => isset($_POST['enabled']) ? '1' : '0',
            'allow_groups' => '0', // grupos exclusivos da Central do CPD
            'max_length' => max(200,min(5000,absint($_POST['max_length']??2000))),
        );
        update_option(self::OPTION_SETTINGS,$settings,false);
        wp_safe_redirect(add_query_arg(array('section'=>'chat','smtt_notice'=>'chat_saved'),home_url('/administracao/')));
        exit;
    }
}
