<?php
/** Gerenciadores frontend; todos os formulários e gravações passam por autorização corporativa. */
if (!defined('ABSPATH')) { exit; }
final class SMTT_Intranet_Manager {
    public static function fields() {
        return array(
            'systems'=>array('description'=>array('Descrição','textarea'), 'access_type'=>array('Tipo de acesso','select:web|rede|atalho|remoto'), 'access_path'=>array('URL ou caminho','text'), 'icon'=>array('Ícone','icon'), 'priority'=>array('Prioridade','select:baixa|media|alta'), 'sort_order'=>array('Ordem','number'), 'featured'=>array('Destaque','checkbox'), 'show_sidebar'=>array('Exibir na navbar → Sistemas','checkbox'), 'show_access_center'=>array('Exibir na Central de Acessos','checkbox'), 'show_app_launcher'=>array('Exibir no Menu de Aplicativos (9 pontos)','checkbox'), 'status'=>array('Status','select:ativo|manutencao|inativo')),
            'folders'=>array('description'=>array('Descrição','textarea'), 'server_path'=>array('Caminho de rede','text'), 'manager'=>array('Responsável','text'), 'icon'=>array('Ícone','icon'), 'priority'=>array('Prioridade','select:baixa|media|alta'), 'sort_order'=>array('Ordem','number'), 'featured'=>array('Destaque','checkbox'), 'show_sidebar'=>array('Exibir na navbar → Pastas','checkbox'), 'show_access_center'=>array('Exibir na Central de Acessos','checkbox'), 'status'=>array('Status','select:ativo|manutencao|inativo')),
            'links'=>array('url'=>array('Endereço do link','url'), 'description'=>array('Descrição','textarea'), 'icon'=>array('Ícone','icon'), 'new_tab'=>array('Abrir em nova aba','checkbox'), 'sort_order'=>array('Ordem','number'), 'status'=>array('Status','select:ativo|inativo')),
            'documents'=>array('description'=>array('Descrição','textarea'), 'document_type'=>array('Tipo','select:arquivo|link|misto'), 'file_url'=>array('URL do arquivo','url'), 'external_url'=>array('Link externo','url'), 'sort_order'=>array('Ordem','number'), 'featured'=>array('Destaque','checkbox'), 'status'=>array('Status','select:ativo|inativo')),
            'agenda'=>array('item_type'=>array('Tipo','select:evento|reserva'), 'description'=>array('Descrição','textarea'), 'responsible_name'=>array('Responsável','text'), 'event_date'=>array('Data','date'), 'start_time'=>array('Início','time'), 'end_time'=>array('Fim','time'), 'location_text'=>array('Local','text'), 'status'=>array('Status','select:agendado|cancelado|concluido'), 'featured'=>array('Destaque','checkbox'), 'public_visible'=>array('Visível a todos','checkbox')),
            'mural'=>array('subtitle'=>array('Subtítulo','text'), 'summary'=>array('Resumo','textarea'), 'link_url'=>array('Link','url'), 'display_type'=>array('Exibição','select:card|banner|topo_deslizante|rodape_deslizante|alerta'), 'priority'=>array('Prioridade','select:baixa|media|alta'), 'start_date'=>array('Início','date'), 'end_date'=>array('Fim','date'), 'sort_order'=>array('Ordem','number'), 'status'=>array('Status','select:agendado|cancelado|concluido'), 'featured'=>array('Destaque','checkbox'), 'public_visible'=>array('Visível a todos','checkbox')),
            'knowledge'=>array('content_type'=>array('Formato do conteúdo','select:artigo|arquivo|link'), 'article_url'=>array('Endereço externo (se for link)','url'), 'summary'=>array('Resumo','textarea'), 'keywords'=>array('Palavras-chave','text'), 'sort_order'=>array('Ordem','number'), 'featured'=>array('Destaque','checkbox'), 'public_visible'=>array('Visível a todos','checkbox'), 'status'=>array('Status','select:ativo|inativo')),
            'custom'=>array('description'=>array('Descrição','textarea'), 'url'=>array('Link de acesso','url'), 'icon'=>array('Ícone','icon'), 'sort_order'=>array('Ordem','number'), 'status'=>array('Status','select:ativo|inativo')),
            'birthdays'=>array('role'=>array('Cargo','text'), 'birth_date'=>array('Nascimento','date'), 'status'=>array('Status','select:ativo|inativo')),
        );
    }
    public static function taxonomies() {
        return array('systems'=>array('smtt_sector'), 'folders'=>array('smtt_sector'), 'documents'=>array('smtt_sector','smtt_document_category'), 'agenda'=>array('smtt_sector','smtt_agenda_space','smtt_agenda_category'), 'mural'=>array('smtt_sector','smtt_mural_category'), 'knowledge'=>array('smtt_sector','smtt_kb_category'), 'birthdays'=>array('smtt_sector'), 'custom'=>array('smtt_catalog','smtt_sector'));
    }
    public static function url($args=array(),$mine=false) {
        return add_query_arg($args, home_url($mine?'/minha-gestao/':'/administracao/'));
    }
    public function register() {
        add_action('admin_post_smtt_portal_save', array($this,'save'));
        add_action('admin_post_smtt_portal_delete', array($this,'delete'));
        add_action('admin_post_smtt_portal_term', array($this,'save_sector'));
        add_action('admin_post_smtt_portal_binding', array($this,'save_binding'));
        add_action('admin_post_smtt_portal_grant', array($this,'save_grant'));
        add_action('admin_post_smtt_portal_delete_grant', array($this,'delete_grant'));
        add_action('admin_post_smtt_portal_options', array($this,'save_options'));
        add_action('admin_post_smtt_portal_logo', array($this,'save_logo'));
        add_action('admin_post_smtt_portal_import', array($this,'import'));
        add_action('admin_post_smtt_portal_category', array($this,'save_category'));
        // O item do wp-admin exibe somente o botão 'Abrir Intranet'.
    }
    private static function require_request($action, $module='', $operation='', $item=0) {
        if (!is_user_logged_in()) { wp_die('Sessão corporativa obrigatória.','Acesso restrito',array('response'=>403)); }
        check_admin_referer('smtt_portal_' . $action);
        $allowed = $module ? SMTT_Intranet_Policy::can($module,$operation,$item) : SMTT_Intranet_Corporate_Identity::is_admin();
        if (!$allowed) { SMTT_Intranet_Policy::audit('denied_'.$action, $module.':'.$item,'denied'); wp_die('Você não possui esta permissão no Active Directory.','Acesso negado',array('response'=>403)); }
    }
    private static function back($section='', $module='', $message='saved', $mine=false) {
        wp_safe_redirect(self::url(array_filter(array('section'=>$section,'module'=>$module,'msg'=>$message)), $mine)); exit;
    }
    public function save() {
        $m = isset($_POST['module']) ? sanitize_key(wp_unslash($_POST['module'])) : '';
        $id = isset($_POST['item_id']) ? absint($_POST['item_id']) : 0;
        if (!SMTT_Intranet_Policy::post_type($m)) { wp_die('Módulo desconhecido.', '', array('response'=>400)); }
        if ($id && get_post_type($id) !== SMTT_Intranet_Policy::post_type($m)) { wp_die('Registro inválido.', '', array('response'=>400)); }
        self::require_request('save', $m, $id?'edit':'create', $id);
        if ($m==='ramais') {
            $allowed=array('nome','setor','cargo','ramal','telefone_direto','whatsapp','email','unidade','sala','observacao','status');
            $data=array('id'=>$id);
            foreach ($allowed as $key) { $data[$key]=isset($_POST[$key]) ? wp_unslash($_POST[$key]):''; }
            $res=SMTT_Intranet_Ramais_Repository::save($data);
            if (is_wp_error($res)) { wp_die(esc_html($res->get_error_message()),'Não foi possível salvar',array('response'=>400)); }
            // Preserve high-performance ramal cache invalidation.
            foreach (array('smtt_ramais_public_ativos_v1','smtt_ramais_public_ativos_v2','smtt_ramais_public_payload_v3') as $key) { delete_transient($key); }
            SMTT_Intranet_Policy::audit($id?'edit_ramal':'create_ramal',(string)$res);
            self::back('modules',$m,'saved',!SMTT_Intranet_Corporate_Identity::is_admin());
        }
        // Validar uploads e formatos antes de qualquer gravação.
        $upload = $_FILES['smtt_attachment'] ?? null;
        $has_upload = is_array($upload) && (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if (in_array($m,array('documents','knowledge'),true)) {
            $validation=SMTT_Intranet_Content_Files::verify_upload($upload);
            if (is_wp_error($validation)) { wp_die(esc_html($validation->get_error_message()),'Arquivo inválido',array('response'=>400)); }
            if ($has_upload && isset($_POST['storage_private']) && !SMTT_Intranet_Content_Files::private_dir()) {
                wp_die('Configure o diretório privado fora do site antes de enviar arquivos protegidos.','Armazenamento indisponível',array('response'=>400));
            }
        }
        if ($m==='knowledge') {
            $format=sanitize_key(wp_unslash($_POST['content_type']??'artigo'));
            if ($format==='link' && empty($_POST['article_url'])) { wp_die('Informe o link de conhecimento externo.','Campo obrigatório',array('response'=>400)); }
            if ($format==='arquivo' && !$has_upload && !($id && SMTT_Intranet_Content_Files::has_file($id))) { wp_die('Selecione um arquivo para este artigo da Wiki.','Campo obrigatório',array('response'=>400)); }
            if ($format==='artigo' && trim(wp_strip_all_tags(wp_unslash($_POST['content']??'')))==='') { wp_die('Escreva o conteúdo do artigo antes de publicar.','Campo obrigatório',array('response'=>400)); }
        }
        if ($m==='documents') {
            $mode=sanitize_key(wp_unslash($_POST['document_type']??'arquivo'));
            if (in_array($mode,array('link','misto'),true) && empty($_POST['external_url'])) { wp_die('Informe o endereço externo do documento.','Campo obrigatório',array('response'=>400)); }
            if (in_array($mode,array('arquivo','misto'),true) && !$has_upload && empty($_POST['file_url']) && !($id && SMTT_Intranet_Content_Files::has_file($id))) { wp_die('Envie um arquivo ou informe uma URL de arquivo existente.','Campo obrigatório',array('response'=>400)); }
        }
        if ($m==='custom' && empty($_POST['tax_smtt_catalog'])) { wp_die('Selecione o módulo (catálogo) deste item.','Campo obrigatório',array('response'=>400)); }
        // Pré-validar associações ANTES de escrever post/meta. Falhas não podem
        // deixar um registro publicado ou parcialmente modificado.
        $taxonomy_terms = array();
        foreach ((self::taxonomies()[$m] ?? array()) as $tax) {
            if (!taxonomy_exists($tax)) { continue; }
            if (($m==='documents' && $tax==='smtt_document_category') || ($m==='knowledge' && $tax==='smtt_kb_category')) { continue; }
            $multi_sectors = $tax === 'smtt_sector' && in_array($m,array('systems','folders'),true);
            if ($multi_sectors) {
                $raw_terms = isset($_POST['tax_smtt_sector']) ? wp_unslash($_POST['tax_smtt_sector']) : array();
                $terms = array_values(array_unique(array_filter(array_map('absint',is_array($raw_terms)?$raw_terms:array($raw_terms)))));
            } else {
                $term=absint($_POST['tax_' . $tax] ?? 0);
                $terms=$term?array($term):array();
            }
            foreach ($terms as $term) {
                if (!term_exists($term,$tax)) { wp_die('Setor ou categoria inválida.','',array('response'=>400)); }
            }
            if ($tax === 'smtt_sector' && !SMTT_Intranet_Corporate_Identity::is_admin()) {
                $identity=SMTT_Intranet_Corporate_Identity::current();
                $mine=SMTT_Intranet_Policy::sector_ids_for_identity($identity);
                $previous=$id ? wp_get_object_terms($id,'smtt_sector',array('fields'=>'ids')) : array();
                $previous=is_wp_error($previous)?array():array_map('intval',$previous);
                if (!$id && !$terms) { wp_die('Selecione um setor para o novo registro.','',array('response'=>403)); }
                foreach ($terms as $term) {
                    if (!in_array($term,$mine,true) && !in_array($term,$previous,true)) {
                        wp_die('Este setor não está autorizado.','',array('response'=>403));
                    }
                }
            }
            $taxonomy_terms[$tax] = $terms;
        }
        if (in_array($m,array('systems','folders'),true)) {
            $show_center = isset($_POST['show_access_center']) ? '1' : '0';
            if ($show_center === '1' && empty($taxonomy_terms['smtt_sector'])) {
                wp_die('Para publicar na Central de Acessos, associe o registro a pelo menos um setor. Você pode desmarcar Central de Acessos para salvar apenas na navbar.','Setor obrigatório',array('response'=>400));
            }
        }
        $post_data=array('post_type'=>SMTT_Intranet_Policy::post_type($m), 'post_title'=>sanitize_text_field(wp_unslash($_POST['title']??'')),
            'post_content'=>wp_kses_post(wp_unslash($_POST['content']??'')), 'post_status'=>'publish');
        if ($post_data['post_title']==='') { wp_die('Informe o título.','Campo obrigatório',array('response'=>400)); }
        if ($id) { $post_data['ID']=$id; }
        $result=$id ? wp_update_post(wp_slash($post_data),true) : wp_insert_post(wp_slash($post_data),true);
        if (is_wp_error($result) || !$result) { wp_die('Não foi possível salvar o registro.','Erro',array('response'=>400)); }
        if ($has_upload) {
            $res=SMTT_Intranet_Content_Files::upload($result,$upload,!empty($_POST['storage_private']));
            if (is_wp_error($res)) {
                if (!$id) { wp_trash_post($result); }
                wp_die(esc_html($res->get_error_message()),'Não foi possível enviar o arquivo',array('response'=>400));
            }
            SMTT_Intranet_Policy::audit('upload_content_file',$m.':'.$result);
        }
        foreach ((self::fields()[$m]??array()) as $field=>$definition) {
            $type=$definition[1]; $raw=isset($_POST[$field]) ? wp_unslash($_POST[$field]) : '';
            if ($type==='checkbox') { $v=$raw==='1'?'1':'0'; }
            elseif ($type==='number') { $v=(string)intval($raw); }
            elseif ($type==='url') { $v=esc_url_raw((string)$raw,array('http','https')); }
            elseif ($type==='icon') { $v=SMTT_Intranet_Icon_Library::valid_value((string)$raw) ? sanitize_text_field($raw) : (string)get_post_meta($result,'_smtt_icon',true); }
            elseif (strpos($type,'select:')===0) { $allowed=explode('|',substr($type,7)); $v=in_array((string)$raw,$allowed,true) ? $raw:$allowed[0]; }
            elseif ($type==='date' || $type==='time') { $v=preg_match($type==='date'?'/^\d{4}-\d\d-\d\d$/':'/^\d\d:\d\d$/',(string)$raw)?$raw:''; }
            else { $v=$type==='textarea'?sanitize_textarea_field((string)$raw):sanitize_text_field((string)$raw); }
            if ($m==='documents' && $field==='file_url' && $has_upload) { continue; }
            update_post_meta($result,'_smtt_'.$field,$v);
        }
        foreach ($taxonomy_terms as $tax => $term) {
            wp_set_object_terms($result, $term, $tax);
        }
        // The WordPress save_post hook runs before our custom meta is updated.
        (new SMTT_Intranet_Public())->clear_home_cache();
        SMTT_Intranet_Policy::audit($id?'edit_content':'create_content',$m.':'.$result);
        self::back('modules',$m,'saved',!SMTT_Intranet_Corporate_Identity::is_admin());
    }
    public function delete() {
        $m=sanitize_key(wp_unslash($_POST['module']??'')); $id=absint($_POST['item_id']??0);
        if (!$id || !SMTT_Intranet_Policy::post_type($m)) { wp_die('Registro inválido.','',array('response'=>400)); }
        if ($m!=='ramais' && get_post_type($id)!==SMTT_Intranet_Policy::post_type($m)) { wp_die('Registro inválido.','',array('response'=>400)); }
        self::require_request('delete',$m,'delete',$id);
        if ($m==='ramais') {
            $result=SMTT_Intranet_Ramais_Repository::delete($id);
            foreach (array('smtt_ramais_public_ativos_v1','smtt_ramais_public_ativos_v2','smtt_ramais_public_payload_v3') as $key) { delete_transient($key); }
        } else { $result=wp_trash_post($id); }
        if (!$result) { wp_die('Não foi possível excluir.','',array('response'=>400)); }
        SMTT_Intranet_Policy::audit('delete_content',$m.':'.$id);
        self::back('modules',$m,'deleted',!SMTT_Intranet_Corporate_Identity::is_admin());
    }
    public function save_category() {
        self::require_request('category');
        $tax=sanitize_key(wp_unslash($_POST['taxonomy']??''));
        if (!in_array($tax,array('smtt_document_category','smtt_kb_category'),true)) { wp_die('Categoria inválida.','',array('response'=>400)); }
        $id=absint($_POST['term_id']??0);
        $name=sanitize_text_field(wp_unslash($_POST['name']??''));
        $description=sanitize_textarea_field(wp_unslash($_POST['description']??''));
        if ($name==='' || (!$id ? false : !term_exists($id,$tax))) { wp_die('Nome ou categoria inválidos.','',array('response'=>400)); }
        $args=array('name'=>$name,'description'=>$description);
        $result=$id?wp_update_term($id,$tax,$args):wp_insert_term($name,$tax,array('description'=>$description));
        if (is_wp_error($result)) { wp_die(esc_html($result->get_error_message()),'',array('response'=>400)); }
        SMTT_Intranet_Policy::audit($id?'edit_category':'create_category',$tax.':'.(int)$result['term_id']);
        (new SMTT_Intranet_Public())->clear_home_cache();
        wp_safe_redirect(self::url(array('section'=>'categories','taxonomy'=>$tax,'msg'=>'saved'))); exit;
    }
    public function save_sector() {
        self::require_request('term');
        $id=absint($_POST['term_id']??0); $name=sanitize_text_field(wp_unslash($_POST['name']??''));
        $parent=absint($_POST['parent']??0);
        if (!$name || ($parent && !term_exists($parent,'smtt_sector'))) { wp_die('Setor inválido.','',array('response'=>400)); }
        $res=$id?wp_update_term($id,'smtt_sector',array('name'=>$name,'parent'=>$parent)):wp_insert_term($name,'smtt_sector',array('parent'=>$parent));
        if (is_wp_error($res)) { wp_die(esc_html($res->get_error_message()),'',array('response'=>400)); }
        $id=(int)$res['term_id'];
        update_term_meta($id,'_smtt_access_center_short_label',sanitize_text_field(wp_unslash($_POST['short']??'')));
        update_term_meta($id,'_smtt_access_center_visible',isset($_POST['visible'])?'1':'0');
        update_term_meta($id,'_smtt_access_center_order',intval($_POST['order']??0));
        SMTT_Intranet_Policy::audit('save_sector',(string)$id);
        self::back('sectors','','saved');
    }
    public function save_binding() {
        self::require_request('binding');
        $id=absint($_POST['term_id']??0); $sid=sanitize_text_field(wp_unslash($_POST['sid']??''));
        if (!$id || !term_exists($id,'smtt_sector') || ($sid!=='' && !SMTT_Intranet_AD::valid_group_sid($sid))) { wp_die('Vínculo inválido.','',array('response'=>400)); }
        update_term_meta($id,'_smtt_ad_group_sid',$sid);
        (new SMTT_Intranet_Public())->clear_home_cache();
        SMTT_Intranet_Policy::audit('bind_sector',$id.':'.$sid);
        self::back('sectors','','saved');
    }
    public function save_grant() {
        self::require_request('grant');
        $kind=sanitize_key(wp_unslash($_POST['subject_kind']??'')); $value=sanitize_text_field(wp_unslash($_POST['subject_value']??''));
        $module=sanitize_key(wp_unslash($_POST['module']??'')); $op=sanitize_key(wp_unslash($_POST['operation']??''));
        $scope=sanitize_key(wp_unslash($_POST['scope_kind']??'')); $sector=absint($_POST['sector_id']??0);
        if (!in_array($kind,array('user','group','sector'),true)||!isset(SMTT_Intranet_Policy::modules()[$module])||!in_array($op,array('view','create','edit','delete'),true)||!in_array($scope,array('all','own','sector','items'),true)) { wp_die('Regra inválida.','',array('response'=>400)); }
        if (($kind==='user'||$kind==='group')&&!preg_match('/^S-1-[0-9-]+$/',$value)) { wp_die('SID inválido.','',array('response'=>400)); }
        if ($kind==='group'&&!SMTT_Intranet_AD::valid_group_sid($value)) { wp_die('Grupo AD desconhecido.','',array('response'=>400)); }
        if ($kind==='sector'&&(!ctype_digit($value)||!term_exists((int)$value,'smtt_sector'))) { wp_die('Setor inválido.','',array('response'=>400)); }
        if ($scope==='sector'&&(!$sector||!term_exists($sector,'smtt_sector'))) { wp_die('Selecione um setor válido.','',array('response'=>400)); }
        if ($op==='create'&&in_array($scope,array('own','items'),true)) { wp_die('Escopo incompatível com inclusão.','',array('response'=>400)); }
        $items=array_unique(array_filter(array_map('absint',preg_split('/[,\s]+/',(string)wp_unslash($_POST['item_ids']??'')))));
        $expires=sanitize_text_field(wp_unslash($_POST['expires_at']??''));
        $expiry=null;
        if ($expires!=='') {
            $time=strtotime($expires.' UTC');
            if (!$time || $time<=time()) { wp_die('Expiração inválida.','',array('response'=>400)); }
            $expiry=gmdate('Y-m-d H:i:s',$time);
        }
        global $wpdb;
        $ok=$wpdb->insert(SMTT_Intranet_Policy::table(),array('subject_kind'=>$kind,'subject_value'=>$value,'module_key'=>$module,'operation'=>$op,'scope_kind'=>$scope,'sector_id'=>$sector,'item_ids'=>implode(',',$items),'expires_at'=>$expiry,'created_at'=>current_time('mysql',true)),array('%s','%s','%s','%s','%s','%d','%s','%s','%s'));
        if (!$ok) { wp_die('Não foi possível criar a regra.','',array('response'=>500)); }
        SMTT_Intranet_Policy::audit('grant_create',$module.':'.$wpdb->insert_id);
        self::back('permissions','','saved');
    }
    public function delete_grant() {
        self::require_request('delete_grant');
        $id=absint($_POST['grant_id']??0); global $wpdb;
        if (!$id) { wp_die('Regra inválida.','',array('response'=>400)); }
        $wpdb->delete(SMTT_Intranet_Policy::table(),array('id'=>$id),array('%d'));
        SMTT_Intranet_Policy::audit('grant_delete',(string)$id);
        self::back('permissions','','deleted');
    }
    public function save_options() {
        self::require_request('options');
        $type=sanitize_key(wp_unslash($_POST['option_group']??''));
        if ($type==='birthdays') {
            $mode=sanitize_key(wp_unslash($_POST['display_mode']??''));
            if (!in_array($mode,array('cartoes','lista','compacto'),true)) { wp_die('Modo de exibição inválido.', '', array('response'=>400)); }
            update_option(SMTT_Intranet_Birthdays::OPTION_DISPLAY_MODE,$mode);
            foreach (array('show_photo'=>SMTT_Intranet_Birthdays::OPTION_SHOW_PHOTO,'show_sector'=>SMTT_Intranet_Birthdays::OPTION_SHOW_SECTOR,'show_role'=>SMTT_Intranet_Birthdays::OPTION_SHOW_ROLE) as $field=>$opt) { update_option($opt,isset($_POST[$field])?'1':'0'); }
            update_option(SMTT_Intranet_Birthdays::OPTION_MAX_ITEMS,max(1,min(100,absint($_POST['max_items']??10))));
        } elseif ($type==='tour') {
            $input=array('enabled'=>isset($_POST['enabled'])?'1':'0','version'=>absint($_POST['version']??1),'delay'=>absint($_POST['delay']??1000),'welcome_title'=>sanitize_text_field(wp_unslash($_POST['welcome_title']??'')),'welcome_text'=>sanitize_textarea_field(wp_unslash($_POST['welcome_text']??'')),'image_id'=>absint((SMTT_Intranet_Tour::get_settings())['image_id']??0),'steps'=>array());
            foreach (SMTT_Intranet_Tour::get_step_definitions() as $key=>$item) { if (isset($_POST['step_'.$key])) { $input['steps'][$key]='1'; } }
            $tour=new SMTT_Intranet_Tour();
            update_option(SMTT_Intranet_Tour::OPTION_SETTINGS,$tour->sanitize_settings($input));
        } elseif ($type==='modules') {
            $enabled=array(); foreach (SMTT_Intranet_Policy::modules() as $key=>$item) { $enabled[$key]=isset($_POST['mod_'.$key])?'1':'0'; }
            update_option('smtt_portal_module_visibility',$enabled,false);
        } else { wp_die('Configuração inválida.','',array('response'=>400)); }
        SMTT_Intranet_Policy::audit('save_options',$type);
        self::back('settings','','saved');
    }
    public function save_logo() {
        self::require_request('logo');
        $file=$_FILES['smtt_logo']??null;
        if (!$file||!empty($file['error'])||$file['size']>2*1024*1024) { wp_die('Envie uma imagem PNG, WEBP ou JPEG de até 2 MB.','',array('response'=>400)); }
        $type=wp_check_filetype_and_ext($file['tmp_name'],$file['name'],array('png'=>'image/png','webp'=>'image/webp','jpg'=>'image/jpeg','jpeg'=>'image/jpeg'));
        if (!$type['ext'] || !in_array($type['type'],array('image/png','image/webp','image/jpeg'),true) || !@getimagesize($file['tmp_name'])) { wp_die('Imagem inválida. SVG e scripts não são aceitos no upload.','',array('response'=>400)); }
        require_once ABSPATH.'wp-admin/includes/file.php'; require_once ABSPATH.'wp-admin/includes/media.php'; require_once ABSPATH.'wp-admin/includes/image.php';
        $id=media_handle_upload('smtt_logo',0,array(),array('test_form'=>false));
        if (is_wp_error($id)) { wp_die(esc_html($id->get_error_message()),'',array('response'=>400)); }
        update_post_meta($id,'_smtt_portal_logo','1');
        SMTT_Intranet_Policy::audit('upload_logo',(string)$id);
        self::back('icons','','saved');
    }
    public function import() {
        $module=sanitize_key(wp_unslash($_POST['module']??''));
        self::require_request('import',$module,'create');
        if ($module==='ramais') {
            $res=SMTT_Intranet_Ramais_Importer::import_uploaded_file($_FILES['import_file']??array());
            if (is_wp_error($res)) { wp_die(esc_html($res->get_error_message()),'',array('response'=>400)); }
            SMTT_Intranet_Policy::audit('import_ramais',wp_json_encode(array('linhas'=>$res['total_linhas']??0,'inseridos'=>$res['total_inseridos']??0)));
        } elseif ($module==='birthdays') {
            $file=$_FILES['import_file']??array();
            if (!$file || !empty($file['error']) || ($file['size']??0)>5*1024*1024 || strtolower(pathinfo($file['name']??'',PATHINFO_EXTENSION))!=='csv' || !is_uploaded_file($file['tmp_name']??'')) { wp_die('Envie CSV válido de até 5 MB.','',array('response'=>400)); }
            $importer=new SMTT_Intranet_Birthdays();
            $res=$importer->import_from_csv($file['tmp_name']);
            if (empty($res['success'])) { wp_die(esc_html($res['message']??'Falha na importação.'),'',array('response'=>400)); }
            SMTT_Intranet_Policy::audit('import_birthdays',wp_json_encode(array('added'=>$res['inserted']??0,'updated'=>$res['updated']??0)));
        } else { wp_die('Importação não suportada.','',array('response'=>400)); }
        self::back('modules',$module,'imported');
    }
    public function wp_admin_entry_redirect() {
        if (!is_admin() || !isset($_GET['page']) || sanitize_key(wp_unslash($_GET['page']))!=='smtt-intranet') { return; }
        if (!SMTT_Intranet_Corporate_Identity::is_admin()) { return; }
        wp_safe_redirect(home_url('/administracao/')); exit;
    }
}
