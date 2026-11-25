<?php
/** Menu de aplicativos da Home: itens administráveis, rascunho, publicação e referências a Sistemas Internos. */
if (!defined('ABSPATH')) { exit; }
final class SMTT_Intranet_App_Launcher {
    const DRAFT = 'smtt_apps_menu_draft_v1';
    const PUBLISHED = 'smtt_apps_menu_published_v1';
    const PREVIOUS = 'smtt_apps_menu_previous_v1';
    const EXCLUDED = 'smtt_apps_draft_excluded_systems_v1';

    public static function register() {
        add_action('admin_post_smtt_apps_add', array(__CLASS__, 'add'));
        add_action('admin_post_smtt_apps_save', array(__CLASS__, 'save'));
        add_action('admin_post_smtt_apps_publish', array(__CLASS__, 'publish'));
        add_action('admin_post_smtt_apps_restore', array(__CLASS__, 'restore'));
        add_action('admin_post_smtt_apps_delete', array(__CLASS__, 'delete'));
    }
    private static function defaults() {
        $rows = array(
            array('google','Google','Pesquisa','https://www.google.com'),
            array('gmail','Gmail','E-mail','https://mail.google.com'),
            array('drive','Drive','Arquivos','https://drive.google.com'),
            array('docs','Documentos','Google Docs','https://docs.google.com/document/'),
            array('sheets','Planilhas','Google Sheets','https://docs.google.com/spreadsheets/'),
            array('slides','Apresentações','Google Slides','https://docs.google.com/presentation/'),
            array('translate','Tradutor','Google Translate','https://translate.google.com/'),
            array('wikipedia','Wikipedia','Pesquisa aberta','https://pt.wikipedia.org/'),
            array('calculator','Calculadora','Utilitário','https://www.google.com/search?q=calculadora'),
        );
        $items = array();
        foreach ($rows as $r) {
            $items[] = array('id'=>'builtin_'.$r[0], 'source'=>'link','title'=>$r[1], 'subtitle'=>$r[2], 'url'=>$r[3],
                'icon'=>'builtin:'.$r[0], 'visible'=>1, 'audience'=>'all', 'sectors'=>array(), 'groups'=>array(), 'users'=>array());
        }
        return $items;
    }
    /** An option missing entirely means "use the legacy nine applications". An empty saved array means none. */
    public static function published() {
        $stored = get_option(self::PUBLISHED, null);
        return is_array($stored) ? $stored : self::defaults();
    }
    public static function draft() {
        $stored = get_option(self::DRAFT, null);
        $items = is_array($stored) ? $stored : self::published();
        $present = array();
        foreach ($items as $row) { if (is_array($row) && !empty($row['id'])) { $present[$row['id']] = true; } }
        // A system selected on its own form is queued in the draft, not published unexpectedly.
        $excluded=(array)get_option(self::EXCLUDED,array());
        foreach (self::flagged_systems() as $post) {
            $id='system_'.(int)$post->ID;
            if (!isset($present[$id]) && !in_array($id,$excluded,true)) {
                $items[] = array('id'=>$id,'source'=>'system','system_id'=>(int)$post->ID, 'visible'=>1,
                    'title'=>'','subtitle'=>'','url'=>'','icon'=>'', 'audience'=>'all','sectors'=>array(),'groups'=>array(),'users'=>array());
            }
        }
        return $items;
    }
    private static function flagged_systems() {
        return get_posts(array('post_type'=>'smtt_internal_system','post_status'=>'publish', 'numberposts'=>-1,
            'meta_query'=>array(array('key'=>'_smtt_show_app_launcher','value'=>'1'))));
    }
    public static function system_post($id) {
        $p=get_post(absint($id));
        return $p && $p->post_type==='smtt_internal_system' && $p->post_status==='publish' ? $p : null;
    }
    public static function system_details($row) {
        $id = absint($row['system_id'] ?? 0);
        $p = self::system_post($id);
        if (!$p || (string)get_post_meta($id,'_smtt_show_app_launcher',true)!=='1' ||
            !in_array((string)get_post_meta($id,'_smtt_status',true),array('ativo',''),true)) { return null; }
        $url=(string)get_post_meta($id,'_smtt_access_path',true);
        if (!self::safe_url($url)) { return null; }
        return array('title'=>$p->post_title,'subtitle'=>'Sistema interno','url'=>$url,
            'icon'=>(string)get_post_meta($id,'_smtt_icon',true) ?: 'smtt:aplicacao-corporativa','post_id'=>$id);
    }
    private static function safe_url($url) {
        return is_string($url) && preg_match('~^https?://~i', $url) && (bool)wp_parse_url($url, PHP_URL_HOST) &&
            !wp_parse_url($url,PHP_URL_USER) && !wp_parse_url($url,PHP_URL_PASS);
    }
    private static function require_cpd($nonce) {
        if (!is_user_logged_in() || !SMTT_Intranet_Corporate_Identity::is_admin()) {
            wp_die('Somente o CPD pode administrar aplicativos.','Acesso negado',array('response'=>403));
        }
        check_admin_referer('smtt_apps_'.$nonce);
    }
    private static function back($message) {
        wp_safe_redirect(SMTT_Intranet_Manager::url(array('section'=>'apps','msg'=>$message)));
        exit;
    }
    private static function valid_icon($candidate, $previous = '') {
        if (preg_match('/^builtin:(google|gmail|drive|docs|sheets|slides|translate|wikipedia|calculator)$/',(string)$candidate)) {
            return (string)$candidate;
        }
        if ($candidate !== '' && SMTT_Intranet_Icon_Library::valid_value((string)$candidate)) { return (string)$candidate; }
        return (string)$previous;
    }
    private static function validate_audience($item) {
        $aud = sanitize_key((string)($item['audience'] ?? 'all'));
        if (!in_array($aud,array('all','users','sectors','groups'),true)) { $aud='all'; }
        $sectors = array();
        foreach ((array)($item['sectors']??array()) as $sector) {
            $id = absint($sector);
            if ($id && term_exists($id,'smtt_sector')) { $sectors[$id] = $id; }
        }
        $groups = array();
        foreach ((array)($item['groups']??array()) as $sid) {
            $sid = sanitize_text_field((string)$sid);
            if (SMTT_Intranet_AD::valid_group_sid($sid)) { $groups[$sid]=$sid; }
        }
        $users=array();
        foreach ((array)($item['users']??array()) as $user_id) {
            $uid=absint($user_id);
            if ($uid && get_userdata($uid)) { $users[$uid]=$uid; }
        }
        return array($aud,array_values($sectors),array_values($groups),array_values($users));
    }
    public static function add() {
        self::require_cpd('add');
        $title = sanitize_text_field(wp_unslash($_POST['new_title']??''));
        $url = esc_url_raw(wp_unslash($_POST['new_url']??''),array('http','https'));
        if ($title==='' || strlen($title)>100 || !self::safe_url($url)) {
            wp_die('Informe um título (até 100 caracteres) e uma URL HTTP(S) válida.','Aplicativo inválido',array('response'=>400));
        }
        $items = self::draft();
        $id='app_'.str_replace('-','',wp_generate_uuid4());
        $items[] = array('id'=>$id,'source'=>'link','title'=>$title,
            'subtitle'=>sanitize_text_field(wp_unslash($_POST['new_subtitle']??'')), 'url'=>$url,
            'icon'=>self::valid_icon(sanitize_text_field(wp_unslash($_POST['new_icon']??'')), 'smtt:aplicacao-corporativa'),
            'visible'=>1,'audience'=>'all','sectors'=>array(),'groups'=>array(),'users'=>array());
        update_option(self::DRAFT,array_values($items),false);
        SMTT_Intranet_Policy::audit('add_app_draft',$id);
        self::back('apps_added');
    }
    public static function save() {
        self::require_cpd('save');
        $original = self::draft();
        $by_id = array(); foreach ($original as $app) { if (!empty($app['id'])) { $by_id[$app['id']]=$app; } }
        $posted = isset($_POST['apps']) && is_array($_POST['apps']) ? wp_unslash($_POST['apps']) : array();
        $sorted = array();
        // The list order comes from DOM order (updated when the CPD drags or uses arrows).
        foreach ($posted as $id=>$data) {
            $id=sanitize_key((string)$id);
            if (!isset($by_id[$id]) || !is_array($data)) { continue; }
            $old=$by_id[$id];
            list($aud,$sectors,$groups,$users)=self::validate_audience($data);
            $row=$old;
            $row['visible']=!empty($data['visible'])?1:0;
            $row['audience']=$aud;
            $row['sectors']=$sectors;
            $row['groups']=$groups;
            $row['users']=$users;
            if (($old['source']??'link')==='link') {
                $title=sanitize_text_field($data['title']??'');
                $url=esc_url_raw((string)($data['url']??''),array('http','https'));
                if ($title==='' || strlen($title)>100 || !self::safe_url($url)) {
                    wp_die('Preencha o nome e uma URL HTTPS/HTTP válida para todos os aplicativos.','Dados inválidos',array('response'=>400));
                }
                $row['title']=$title;
                $row['subtitle']=substr(sanitize_text_field($data['subtitle']??''),0,100);
                $row['url']=$url;
                $row['icon']=self::valid_icon(sanitize_text_field($data['icon']??''),$old['icon']??'');
            }
            $sorted[]=$row;
        }
        // A failed/incomplete form must not silently delete or hide applications.
        if (count($sorted)!==count($by_id)) {
            wp_die('A lista de aplicativos está incompleta. Recarregue a página e tente novamente.','Dados incompletos',array('response'=>400));
        }
        update_option(self::DRAFT,$sorted,false);
        SMTT_Intranet_Policy::audit('save_apps_draft',count($sorted));
        self::back('apps_draft');
    }
    public static function delete() {
        self::require_cpd('delete');
        $id=sanitize_key(wp_unslash($_POST['app_id']??''));
        $items=self::draft(); $kept=array(); $found=false;
        foreach ($items as $item) {
            if (($item['id']??'')!==$id) { $kept[]=$item; continue; }
            $found=true;
            if (($item['source']??'')==='system') {
                $excluded=(array)get_option(self::EXCLUDED,array());
                $excluded[]=$id;
                update_option(self::EXCLUDED,array_values(array_unique($excluded)),false);
            }
        }
        if (!$found) { wp_die('Aplicativo não encontrado.','Erro',array('response'=>404)); }
        update_option(self::DRAFT,$kept,false);
        SMTT_Intranet_Policy::audit('remove_app_draft',$id);
        self::back('apps_removed');
    }
    public static function publish() {
        self::require_cpd('publish');
        $draft=self::draft();
        // System removal takes effect only on publication (never while editing a draft).
        foreach ((array)get_option(self::EXCLUDED,array()) as $removed) {
            if (preg_match('/^system_(\d+)$/',(string)$removed,$m) && self::system_post((int)$m[1])) {
                update_post_meta((int)$m[1],'_smtt_show_app_launcher','0');
            }
        }
        delete_option(self::EXCLUDED);
        update_option(self::PREVIOUS,self::published(),false);
        update_option(self::PUBLISHED,array_values($draft),false);
        update_option(self::DRAFT,array_values($draft),false);
        SMTT_Intranet_Policy::audit('publish_apps_menu',count($draft));
        self::back('apps_published');
    }
    public static function restore() {
        self::require_cpd('restore');
        $previous=get_option(self::PREVIOUS,null);
        if (!is_array($previous)) { wp_die('Não existe publicação anterior para restaurar.','Sem histórico',array('response'=>400)); }
        $current=self::published();
        // Restore the menu AND each referenced system's menu flag.
        $previous_systems=array();
        foreach ($previous as $row) { if (($row['source']??'')==='system') { $previous_systems[]=(int)($row['system_id']??0); } }
        foreach ($current as $row) {
            if (($row['source']??'')==='system' && !in_array((int)($row['system_id']??0),$previous_systems,true)) {
                $system_id=(int)($row['system_id']??0);
                if (self::system_post($system_id)) {update_post_meta($system_id,'_smtt_show_app_launcher','0');}
            }
        }
        foreach ($previous_systems as $system_id) {
            if (self::system_post($system_id)) {update_post_meta($system_id,'_smtt_show_app_launcher','1');}
        }
        delete_option(self::EXCLUDED);
        update_option(self::PUBLISHED,$previous,false);
        update_option(self::DRAFT,$previous,false);
        update_option(self::PREVIOUS,$current,false);
        SMTT_Intranet_Policy::audit('restore_apps_menu',count($previous));
        self::back('apps_restored');
    }
    public static function is_preview() {
        return isset($_GET['smtt_apps_preview']) &&
            wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['smtt_apps_preview'])),'smtt_apps_preview') &&
            SMTT_Intranet_Corporate_Identity::is_admin();
    }
    public static function for_home() {
        $source=self::is_preview()?self::draft():self::published();
        $items=array();
        $identity=null;
        foreach ($source as $row) {
            if (!is_array($row) || empty($row['visible'])) { continue; }
            $aud=$row['audience']??'all';
            if ($aud==='users') {
                if (!in_array(get_current_user_id(),array_map('intval',(array)($row['users']??array())),true)) { continue; }
            } elseif ($aud!=='all') {
                if ($identity===null) { $identity=SMTT_Intranet_Corporate_Identity::current(); }
                if (is_wp_error($identity)) { continue; } // fail closed when SSO is not available
                if ($aud==='groups' && !array_intersect((array)($row['groups']??array()),(array)($identity['groups']??array()))) { continue; }
                if ($aud==='sectors' && !array_intersect(array_map('intval',(array)($row['sectors']??array())),
                    SMTT_Intranet_Policy::sector_ids_for_identity($identity))) { continue; }
            }
            if (($row['source']??'link')==='system') {
                $system=self::system_details($row);
                if (!$system) { continue; }
                if (!SMTT_Intranet_Corporate_Identity::is_admin() &&
                    !SMTT_Intranet_Access_Permissions::can_access_post($system['post_id'])) {
                    // Existing sector ACL and corporate sector binding are both accepted.
                    $verified=SMTT_Intranet_Corporate_Identity::current();
                    $allowed=false;
                    if (!is_wp_error($verified)) {
                        $terms=wp_get_object_terms($system['post_id'],'smtt_sector',array('fields'=>'ids'));
                        $allowed=!is_wp_error($terms) && (bool)array_intersect(array_map('intval',$terms),
                            SMTT_Intranet_Policy::sector_ids_for_identity($verified));
                    }
                    if (!$allowed) { continue; }
                }
                $row=array_merge($row,$system);
            }
            if (!self::safe_url($row['url']??'')) { continue; }
            $items[]=$row;
        }
        return $items;
    }
    public static function admin_panel() {
        $draft=self::draft();
        $sectors=get_terms(array('taxonomy'=>'smtt_sector','hide_empty'=>false,'orderby'=>'name'));
        if (is_wp_error($sectors)) { $sectors=array(); }
        $groups=SMTT_Intranet_AD::list_objects('group','',300);
        if (!is_array($groups)) { $groups=array(); }
        $users=get_users(array('number'=>300,'orderby'=>'display_name','order'=>'ASC','fields'=>array('ID','display_name','user_login')));
        $url=admin_url('admin-post.php');
        $home=SMTT_Intranet_Home_Routing::url()?:home_url('/');
        $preview=add_query_arg('smtt_apps_preview',wp_create_nonce('smtt_apps_preview'),$home);
        ?>
        <section class="smtt-ep__panel smtt-app-admin">
          <h2>Adicionar aplicativo independente</h2>
          <p>Para sites e ferramentas que não fazem parte de Sistemas Internos. A inclusão entra no rascunho, sem alterar a Home publicada.</p>
          <form method="post" action="<?php echo esc_url($url); ?>" class="smtt-app-admin__add-form">
            <input type="hidden" name="action" value="smtt_apps_add"><?php wp_nonce_field('smtt_apps_add'); ?>
            <label for="smtt-app-new-title">Nome do aplicativo<input id="smtt-app-new-title" maxlength="100" required name="new_title" placeholder="Ex.: Protocolo Digital"></label>
            <label for="smtt-app-new-url">Endereço (https://...)<input id="smtt-app-new-url" type="url" required name="new_url" placeholder="https://..."></label>
            <label for="smtt-app-new-subtitle">Subtítulo<input id="smtt-app-new-subtitle" name="new_subtitle" maxlength="100" placeholder="Ex.: Portal institucional"></label>
            <div class="smtt-app-admin__field"><label for="smtt-app-new-icon">Ícone</label><div class="smtt-ep__iconinput"><input id="smtt-app-new-icon" data-icon-value name="new_icon" placeholder="Selecione na biblioteca de ícones"><button type="button" data-open-icons>Escolher ícone</button></div></div>
            <button class="smtt-ep__button smtt-app-admin__add-submit" type="submit">+ Adicionar ao rascunho</button>
          </form>
        </section>
        <section class="smtt-ep__panel smtt-app-admin">
          <h2>Menu de Aplicativos (9 pontos)</h2>
          <p>Adicione atalhos, edite os existentes e organize a ordem. As mudanças ficam em rascunho até você publicar. Sistemas Internos marcados para aparecer aqui entram no rascunho automaticamente.</p>
          <div class="smtt-app-admin__toolbar">
            <a class="smtt-ep__button" href="<?php echo esc_url($preview); ?>" target="_blank" rel="noopener noreferrer">Pré-visualizar rascunho ↗</a>
            <a class="smtt-ep__button smtt-app-admin__outline" href="<?php echo esc_url($home); ?>" target="_blank" rel="noopener noreferrer">Ver menu publicado ↗</a>
            <span class="smtt-app-admin__count"><?php echo (int)count($draft); ?> aplicativos</span>
          </div>
          <p class="smtt-ep__note">Arraste pelo botão ⋮⋮ ou use ↑ ↓. Marque "Exibir" para mostrar o item. Uma URL direta exige sempre as permissões do serviço de destino.</p>
          <form method="post" action="<?php echo esc_url($url); ?>" id="smtt-apps-editor">
            <input type="hidden" name="action" value="smtt_apps_save">
            <?php wp_nonce_field('smtt_apps_save'); ?>
            <div class="smtt-app-admin__list" data-apps-sortable>
            <?php foreach ($draft as $app):
              $id=(string)($app['id']??''); if (!$id) {continue;}
              $system=($app['source']??'link')==='system';
              $details=$system?self::system_details($app):null;
              $label=$system?($details['title']??get_the_title((int)($app['system_id']??0))):($app['title']??'');
              $icon=$system?($details['icon']??'smtt:aplicacao-corporativa'):($app['icon']??'');
              $aud=$app['audience']??'all'; ?>
              <article class="smtt-app-admin__row" draggable="true" data-app-row="<?php echo esc_attr($id); ?>">
                <div class="smtt-app-admin__rowhead">
                  <button type="button" class="smtt-app-admin__grab" data-app-drag aria-label="Arrastar <?php echo esc_attr($label); ?>" title="Arrastar para reordenar">⠿</button>
                  <span class="smtt-app-admin__thumb"><?php if (!$system && strpos($icon,'builtin:')===0) { echo '<span class="dashicons dashicons-admin-site-alt3"></span>'; } else { echo SMTT_Intranet_Icon_Library::html($icon); } ?></span>
                  <strong><?php echo esc_html($label); ?></strong>
                  <?php if ($system): ?><span class="smtt-app-admin__source">Sistema Interno #<?php echo absint($app['system_id']??0); ?></span><?php endif; ?>
                  <button type="button" data-move="up" aria-label="Subir <?php echo esc_attr($label); ?>">↑</button>
                  <button type="button" data-move="down" aria-label="Descer <?php echo esc_attr($label); ?>">↓</button>
                  <label class="smtt-ep__check"><input type="checkbox" name="apps[<?php echo esc_attr($id); ?>][visible]" value="1" <?php checked(!empty($app['visible'])); ?>> Exibir</label>
                </div>
                <details class="smtt-app-admin__details">
                  <summary>Editar detalhes <span aria-hidden="true">▾</span></summary>
                <div class="smtt-app-admin__fields">
                  <?php if (!$system): ?>
                    <label>Nome<input maxlength="100" required name="apps[<?php echo esc_attr($id); ?>][title]" value="<?php echo esc_attr($app['title']??''); ?>"></label>
                    <label>Subtítulo<input maxlength="100" name="apps[<?php echo esc_attr($id); ?>][subtitle]" value="<?php echo esc_attr($app['subtitle']??''); ?>"></label>
                    <label class="smtt-app-admin__wide">URL<input type="url" required name="apps[<?php echo esc_attr($id); ?>][url]" value="<?php echo esc_attr($app['url']??''); ?>"></label>
                    <label>Ícone (biblioteca ou marca)<span class="smtt-ep__iconinput"><input data-icon-value name="apps[<?php echo esc_attr($id); ?>][icon]" value="<?php echo esc_attr($icon); ?>"><button type="button" data-open-icons>Escolher</button></span></label>
                  <?php else: ?>
                    <div class="smtt-app-admin__wide"><p>Nome, URL e ícone são atualizados no cadastro original do sistema. <?php if ($details): ?><a href="<?php echo esc_url(SMTT_Intranet_Manager::url(array('section'=>'modules','module'=>'systems','edit'=>$details['post_id']))); ?>">Editar sistema</a><?php else: ?><strong>Este sistema está inativo, sem URL web ou foi desmarcado no cadastro.</strong><?php endif; ?></p></div>
                  <?php endif; ?>
                  <label>Quem pode ver<select name="apps[<?php echo esc_attr($id); ?>][audience]" data-app-audience>
                    <option value="all" <?php selected($aud,'all'); ?>>Todos os funcionários</option>
                    <option value="users" <?php selected($aud,'users'); ?>>Usuários específicos</option>
                    <option value="sectors" <?php selected($aud,'sectors'); ?>>Setores selecionados (AD)</option>
                    <option value="groups" <?php selected($aud,'groups'); ?>>Grupos do AD</option>
                  </select></label>
                  <fieldset data-app-users <?php echo $aud==='users'?'':'hidden'; ?>><legend>Usuários WordPress / SSO autorizados</legend>
                    <?php foreach($users as $user): ?><label class="smtt-ep__check"><input type="checkbox" name="apps[<?php echo esc_attr($id); ?>][users][]" value="<?php echo (int)$user->ID; ?>" <?php checked(in_array((int)$user->ID,array_map('intval',(array)($app['users']??array())),true)); ?>> <?php echo esc_html($user->display_name . ' (' . $user->user_login . ')'); ?></label><?php endforeach; ?>
                    <?php if (!$users): ?><small>Nenhum usuário disponível.</small><?php endif; ?>
                  </fieldset>
                  <fieldset data-app-sectors <?php echo $aud==='sectors'?'':'hidden'; ?>><legend>Setores autorizados</legend>
                    <?php foreach($sectors as $term): ?><label class="smtt-ep__check"><input type="checkbox" name="apps[<?php echo esc_attr($id); ?>][sectors][]" value="<?php echo (int)$term->term_id; ?>" <?php checked(in_array((int)$term->term_id,array_map('intval',(array)($app['sectors']??array())),true)); ?>> <?php echo esc_html($term->name); ?></label><?php endforeach; ?>
                    <?php if (!$sectors): ?><small>Cadastre os setores antes de restringir um aplicativo.</small><?php endif; ?>
                  </fieldset>
                  <fieldset data-app-groups <?php echo $aud==='groups'?'':'hidden'; ?>><legend>Grupos AD autorizados</legend>
                    <?php foreach($groups as $g): ?><label class="smtt-ep__check"><input type="checkbox" name="apps[<?php echo esc_attr($id); ?>][groups][]" value="<?php echo esc_attr($g['object_sid']); ?>" <?php checked(in_array($g['object_sid'],(array)($app['groups']??array()),true)); ?>> <?php echo esc_html($g['object_name']); ?></label><?php endforeach; ?>
                    <?php if (!$groups): ?><small>Os grupos aparecerão quando o AD estiver sincronizado.</small><?php endif; ?>
                  </fieldset>
                </div>
                <button type="submit" class="smtt-ep__danger" form="smtt-app-remove-<?php echo esc_attr($id); ?>" data-app-remove="<?php echo esc_attr($label); ?>">Remover deste menu</button>
                </details>
              </article>
            <?php endforeach; ?>
            </div>
            <button class="smtt-ep__button" type="submit">Salvar rascunho</button>
          </form>
          <?php foreach($draft as $app): if (empty($app['id'])) {continue;} ?>
          <form id="smtt-app-remove-<?php echo esc_attr($app['id']); ?>" method="post" action="<?php echo esc_url($url); ?>" class="smtt-app-admin__hiddenform">
            <input type="hidden" name="action" value="smtt_apps_delete"><input type="hidden" name="app_id" value="<?php echo esc_attr($app['id']); ?>"><?php wp_nonce_field('smtt_apps_delete'); ?>
          </form>
          <?php endforeach; ?>
          <div class="smtt-app-admin__publish">
            <form method="post" action="<?php echo esc_url($url); ?>"><input type="hidden" name="action" value="smtt_apps_publish"><?php wp_nonce_field('smtt_apps_publish'); ?><button class="smtt-ep__button smtt-app-admin__publishbtn" data-app-publish type="submit">Publicar rascunho</button></form>
            <?php if (is_array(get_option(self::PREVIOUS,null))): ?><form method="post" action="<?php echo esc_url($url); ?>"><input type="hidden" name="action" value="smtt_apps_restore"><?php wp_nonce_field('smtt_apps_restore'); ?><button class="smtt-ep__button smtt-app-admin__outline" type="submit" data-app-restore>Restaurar publicação anterior</button></form><?php endif; ?>
          </div>
        </section>
        <?php
    }
}
