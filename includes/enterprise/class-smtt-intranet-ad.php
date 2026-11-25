<?php
/** AD read-only discovery; no credentials are stored in the WordPress database. */
if (!defined('ABSPATH')) { exit; }

class SMTT_Intranet_AD {
    const DB_VERSION = '1';
    const CRON_HOOK = 'smtt_intranet_ad_refresh_catalog';
    const OPT_SYNC = 'smtt_intranet_ad_last_sync';
    const OPT_CPD = 'smtt_intranet_ad_cpd_sid';
    const OPT_ADMINS = 'smtt_intranet_ad_admin_sid';

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'smtt_intranet_ad_objects';
    }

    public static function installed() {
        if (get_option('smtt_intranet_ad_db_version') !== self::DB_VERSION) {
            self::create_table();
        }
    }

    public static function create_table() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = self::table();
        $collation = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE {$table} (
            object_guid CHAR(32) NOT NULL,
            object_sid VARCHAR(100) NOT NULL DEFAULT '',
            object_kind VARCHAR(12) NOT NULL,
            object_name VARCHAR(190) NOT NULL,
            object_dn TEXT NOT NULL,
            scan_id VARCHAR(48) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (object_guid),
            KEY object_sid (object_sid),
            KEY object_kind (object_kind),
            KEY scan_id (scan_id),
            KEY is_active (is_active)
        ) {$collation};");
        // Só registrar a versão se o banco realmente criou a tabela.
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))) === $table) {
            update_option('smtt_intranet_ad_db_version', self::DB_VERSION, false);
        }
    }

    public static function config_ready() {
        return defined('SMTT_AD_LDAPS_URL') && defined('SMTT_AD_BASE_DN') &&
            defined('SMTT_AD_BIND_DN') && defined('SMTT_AD_BIND_PASSWORD') &&
            (string) SMTT_AD_LDAPS_URL !== '' && (string) SMTT_AD_BASE_DN !== '' &&
            (string) SMTT_AD_BIND_DN !== '' && (string) SMTT_AD_BIND_PASSWORD !== '';
    }

    public static function connect() {
        if (!self::config_ready()) {
            return new WP_Error('smtt_ad_configuration', 'Configure a conexão LDAPS no wp-config.php.');
        }
        if (!function_exists('ldap_connect')) {
            return new WP_Error('smtt_ad_extension', 'A extensão PHP LDAP não está habilitada no Ubuntu.');
        }
        $url = (string) SMTT_AD_LDAPS_URL;
        // AD credentials must never transit an unencrypted LDAP connection.
        if (!preg_match('~^ldaps://[a-z0-9._-]+(?::636)?/?$~i', $url)) {
            return new WP_Error('smtt_ad_tls', 'Use uma URL ldaps:// com certificado TLS validado.');
        }
        if (defined('LDAP_OPT_X_TLS_REQUIRE_CERT') && defined('LDAP_OPT_X_TLS_DEMAND')) {
            ldap_set_option(null, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_DEMAND);
        }
        $link = @ldap_connect($url);
        if (!$link) { return new WP_Error('smtt_ad_connect', 'Não foi possível iniciar LDAPS.'); }
        ldap_set_option($link, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($link, LDAP_OPT_REFERRALS, 0);
        if (defined('LDAP_OPT_NETWORK_TIMEOUT')) { ldap_set_option($link, LDAP_OPT_NETWORK_TIMEOUT, 5); }
        ldap_set_option($link, LDAP_OPT_TIMELIMIT, 10);
        // TLS certificate trust is configured at the OS/OpenLDAP level.
        if (!@ldap_bind($link, (string) SMTT_AD_BIND_DN, (string) SMTT_AD_BIND_PASSWORD)) {
            @ldap_unbind($link);
            return new WP_Error('smtt_ad_bind', 'Falha de autenticação LDAPS. Confira conectividade, certificado e conta de leitura.');
        }
        return $link;
    }

    public static function sid_from_binary($binary) {
        if (!is_string($binary) || strlen($binary) < 8) { return ''; }
        $bytes = array_values(unpack('C*', $binary));
        $revision = $bytes[0];
        $count = $bytes[1];
        if ($count > 15 || strlen($binary) < 8 + $count * 4) { return ''; }
        $authority = 0;
        for ($i = 2; $i < 8; $i++) { $authority = $authority * 256 + $bytes[$i]; }
        $sid = 'S-' . $revision . '-' . $authority;
        for ($i = 0; $i < $count; $i++) {
            $part = unpack('V', substr($binary, 8 + $i * 4, 4));
            $sid .= '-' . sprintf('%u', $part[1]);
        }
        return $sid;
    }

    private static function attribute($entry, $name) {
        $key = strtolower($name);
        return isset($entry[$key][0]) ? (string) $entry[$key][0] : '';
    }

    public static function read_pages($link, $filter, $attributes, $callback) {
        $base = (string) SMTT_AD_BASE_DN;
        $cookie = '';
        $pages = 0;
        do {
            if (++$pages > 200) { return new WP_Error('smtt_ad_size', 'Consulta AD excedeu o limite seguro de páginas.'); }
            $controls = array(array('oid' => '1.2.840.113556.1.4.319', 'iscritical' => true,
                'value' => array('size' => 500, 'cookie' => $cookie)));
            $result = @ldap_search($link, $base, $filter, $attributes, 0, 0, 15, LDAP_DEREF_NEVER, $controls);
            if (!$result) { return new WP_Error('smtt_ad_search', 'Falha na consulta paginada ao Active Directory.'); }
            $entries = ldap_get_entries($link, $result);
            if (!is_array($entries)) { return new WP_Error('smtt_ad_entries', 'Resposta LDAP inválida.'); }
            for ($i = 0; $i < (int) $entries['count']; $i++) { $callback($entries[$i]); }
            $received = array();
            if (!@ldap_parse_result($link, $result, $err, $matched, $message, $referrals, $received)) {
                return new WP_Error('smtt_ad_paging', 'Não foi possível validar a paginação LDAP.');
            }
            $cookie = '';
            foreach ($received as $control) {
                if (($control['oid'] ?? '') === '1.2.840.113556.1.4.319') {
                    $cookie = (string) ($control['value']['cookie'] ?? '');
                }
            }
        } while ($cookie !== '');
        return true;
    }

    public static function sync() {
        self::installed();
        if (get_transient('smtt_intranet_ad_sync_lock')) {
            return new WP_Error('smtt_ad_busy', 'Uma sincronização do AD já está em andamento.');
        }
        set_transient('smtt_intranet_ad_sync_lock', 1, 5 * MINUTE_IN_SECONDS);
        $link = self::connect();
        if (is_wp_error($link)) {
            delete_transient('smtt_intranet_ad_sync_lock');
            return $link;
        }
        global $wpdb;
        $table = self::table();
        $scan = gmdate('YmdHis') . '_' . wp_generate_password(8, false, false);
        $count = array('groups' => 0, 'ous' => 0);
        $db_failed = false;
        $result = self::read_pages($link,
            '(|(objectCategory=group)(objectClass=organizationalUnit))',
            array('objectGUID', 'objectSid', 'cn', 'ou', 'distinguishedName', 'objectClass'),
            function ($entry) use ($wpdb, $table, $scan, &$count, &$db_failed) {
                $classes = isset($entry['objectclass']) ? array_map('strtolower', array_slice($entry['objectclass'], 0, (int) $entry['objectclass']['count'])) : array();
                $kind = in_array('organizationalunit', $classes, true) ? 'ou' : 'group';
                $guid = bin2hex(self::attribute($entry, 'objectGUID'));
                if (strlen($guid) !== 32) { return; }
                $name = sanitize_text_field(self::attribute($entry, $kind === 'ou' ? 'ou' : 'cn'));
                $dn = sanitize_text_field(self::attribute($entry, 'distinguishedName'));
                $sid = $kind === 'group' ? self::sid_from_binary(self::attribute($entry, 'objectSid')) : '';
                $saved = $wpdb->replace($table, array(
                    'object_guid' => $guid, 'object_sid' => $sid, 'object_kind' => $kind,
                    'object_name' => $name, 'object_dn' => $dn, 'scan_id' => $scan, 'is_active' => 1
                ), array('%s','%s','%s','%s','%s','%s','%d'));
                if ($saved === false) { $db_failed = true; }
                $count[$kind === 'ou' ? 'ous' : 'groups']++;
            }
        );
        @ldap_unbind($link);
        if (!is_wp_error($result) && $db_failed) {
            $result = new WP_Error('smtt_ad_db', 'Não foi possível gravar todos os objetos AD.');
        }
        if (!is_wp_error($result)) {
            // Soft-delete: content and permissions are never erased on AD deletion.
            $wpdb->query($wpdb->prepare("UPDATE {$table} SET is_active = 0 WHERE scan_id <> %s", $scan));
            update_option(self::OPT_SYNC, array('at' => current_time('mysql'), 'count' => $count), false);
        }
        delete_transient('smtt_intranet_ad_sync_lock');
        return is_wp_error($result) ? $result : $count;
    }

    public static function list_objects($kind = 'group', $search = '', $limit = 150) {
        global $wpdb;
        $table = self::table();
        $like = '%' . $wpdb->esc_like($search) . '%';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT object_guid, object_sid, object_name, object_dn FROM {$table}
             WHERE is_active = 1 AND object_kind = %s AND object_name LIKE %s
             ORDER BY object_name ASC LIMIT %d",
            $kind === 'ou' ? 'ou' : 'group', $like, max(1, min(500, (int) $limit))
        ), ARRAY_A);
    }

    public static function valid_group_sid($sid) {
        global $wpdb;
        if (!preg_match('/^S-1-[0-9-]+$/', (string) $sid)) { return false; }
        $table = self::table();
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT 1 FROM {$table} WHERE object_sid = %s AND object_kind = 'group' AND is_active = 1 LIMIT 1", $sid
        ));
    }

    /** CPD-only live user search; no full directory replication or password exposure. */
    public static function search_users($search, $limit = 30) {
        if (!SMTT_Intranet_Corporate_Identity::is_admin()) { return new WP_Error('smtt_ad_forbidden', 'Acesso restrito ao CPD.'); }
        $search = sanitize_text_field((string) $search);
        if (strlen($search) < 2 || strlen($search) > 80) { return array(); }
        $link = self::connect();
        if (is_wp_error($link)) { return $link; }
        $q = ldap_escape($search, '', LDAP_ESCAPE_FILTER);
        $filter = '(&(objectCategory=person)(objectClass=user)(!(userAccountControl:1.2.840.113556.1.4.803:=2))(|(displayName=*' . $q . '*)(sAMAccountName=*' . $q . '*)(userPrincipalName=*' . $q . '*)))';
        $limit = min(50, max(1,absint($limit)));
        $result = @ldap_search($link,(string)SMTT_AD_BASE_DN,$filter,array('displayName','objectSid','sAMAccountName','userPrincipalName'),0,$limit,12);
        if (!$result) { @ldap_unbind($link); return new WP_Error('smtt_ad_user_search','Falha na pesquisa de usuários.'); }
        $entries = ldap_get_entries($link,$result);
        $users=array();
        if (is_array($entries)) {
            for($i=0;$i<(int)$entries['count'] && $i<$limit;$i++) {
                $sid=self::sid_from_binary(self::attribute($entries[$i],'objectSid'));
                if (!$sid) { continue; }
                $users[]=array('sid'=>$sid,'name'=>self::attribute($entries[$i],'displayName'),
                    'login'=>self::attribute($entries[$i],'sAMAccountName'));
            }
        }
        @ldap_unbind($link);
        return $users;
    }

    public static function find_user($link, $claim) {
        $upn = sanitize_text_field((string) ($claim['upn'] ?? ''));
        $sam = sanitize_text_field((string) ($claim['samaccountname'] ?? ''));
        if ($upn !== '') { $field = 'userPrincipalName'; $value = $upn; }
        elseif ($sam !== '') { $field = 'sAMAccountName'; $value = $sam; }
        else { return new WP_Error('smtt_ad_identity_missing', 'O SSO não informou um identificador de usuário corporativo.'); }
        $escaped = ldap_escape($value, '', LDAP_ESCAPE_FILTER);
        $search = @ldap_search($link, (string) SMTT_AD_BASE_DN,
            '(&(objectCategory=person)(objectClass=user)(!(userAccountControl:1.2.840.113556.1.4.803:=2))(' . $field . '=' . $escaped . '))',
            array('distinguishedName', 'objectSid', 'userPrincipalName', 'sAMAccountName'), 0, 2, 10);
        if (!$search) { return new WP_Error('smtt_ad_user_lookup', 'Não foi possível validar a conta no AD.'); }
        $entries = ldap_get_entries($link, $search);
        if (!is_array($entries) || (int) $entries['count'] !== 1) {
            return new WP_Error('smtt_ad_user_not_found', 'Usuário corporativo não encontrado de forma inequívoca no AD.');
        }
        $entry = $entries[0];
        $sid = self::sid_from_binary(self::attribute($entry, 'objectSid'));
        if (!empty($claim['sid']) && !hash_equals((string) $claim['sid'], $sid)) {
            return new WP_Error('smtt_ad_identity_mismatch', 'Identidade SSO não confere com o Active Directory.');
        }
        return array('sid' => $sid, 'dn' => self::attribute($entry, 'distinguishedName'),
            'upn' => self::attribute($entry, 'userPrincipalName'), 'samaccountname' => self::attribute($entry, 'sAMAccountName'));
    }

    public static function user_group_sids($link, $user_dn) {
        $groups = array();
        $filter = '(&(objectCategory=group)(member:1.2.840.113556.1.4.1941:=' . ldap_escape($user_dn, '', LDAP_ESCAPE_FILTER) . '))';
        $result = self::read_pages($link, $filter, array('objectSid'), function ($entry) use (&$groups) {
            $sid = self::sid_from_binary(self::attribute($entry, 'objectSid'));
            if ($sid !== '') { $groups[$sid] = true; }
        });
        return is_wp_error($result) ? $result : array_keys($groups);
    }
}
