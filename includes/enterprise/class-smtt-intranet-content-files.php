<?php
/** Upload central para Documentos e Wiki. Arquivos sigilosos exigem storage fora do document root. */
if (!defined('ABSPATH')) { exit; }
final class SMTT_Intranet_Content_Files {
    const MAX_BYTES = 15728640;
    private static function types() {
        return array('pdf'=>'application/pdf','docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
          'xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','pptx'=>'application/vnd.openxmlformats-officedocument.presentationml.presentation',
          'doc'=>'application/msword','xls'=>'application/vnd.ms-excel','ppt'=>'application/vnd.ms-powerpoint',
          'odt'=>'application/vnd.oasis.opendocument.text','ods'=>'application/vnd.oasis.opendocument.spreadsheet',
          'txt'=>'text/plain','csv'=>'text/csv');
    }
    public static function register() {
        add_action('admin_post_smtt_content_download', array(__CLASS__, 'download'));
        add_action('admin_post_smtt_content_read', array(__CLASS__, 'read_article'));
    }
    public static function private_dir() {
        if (!defined('SMTT_INTRANET_PRIVATE_FILES_DIR')) { return ''; }
        $path = (string) SMTT_INTRANET_PRIVATE_FILES_DIR;
        if (!$path || !is_dir($path)) { return ''; }
        $real = realpath($path); $root = realpath(ABSPATH);
        $docroot = !empty($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
        if (!$real || !$root || !is_writable($real)) { return ''; }
        foreach (array($root,$docroot) as $webroot) {
            if ($webroot && ($real === $webroot || strpos($real . DIRECTORY_SEPARATOR, rtrim($webroot,DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) === 0)) { return ''; }
        }
        return $real;
    }
    public static function has_file($post_id) {
        return get_post_meta($post_id,'_smtt_private_file',true) || get_post_meta($post_id,'_smtt_file_id',true) ||
            get_post_meta($post_id,'_smtt_article_file_id',true) || get_post_meta($post_id,'_smtt_file_url',true);
    }
    public static function file_label($post_id) {
        return (string) get_post_meta($post_id,'_smtt_private_name',true) ?: (string)get_post_meta($post_id,'_smtt_uploaded_name',true);
    }
    public static function link($post_id) {
        if (get_post_meta($post_id,'_smtt_private_file',true)) {
            return admin_url('admin-post.php?action=smtt_content_download&item='.(int)$post_id);
        }
        $key = get_post_type($post_id)==='smtt_document' ? '_smtt_file_id' : '_smtt_article_file_id';
        $id = (int) get_post_meta($post_id,$key,true);
        if ($id) { return (string) wp_get_attachment_url($id); }
        $key = get_post_type($post_id)==='smtt_document' ? '_smtt_file_url' : '_smtt_article_url';
        return (string) get_post_meta($post_id,$key,true);
    }
    public static function verify_upload($file) {
        if (!$file || !isset($file['error']) || (int)$file['error'] === UPLOAD_ERR_NO_FILE) { return true; }
        if (!is_array($file) || !isset($file['name'],$file['tmp_name'],$file['size']) || !is_string($file['name']) || !is_string($file['tmp_name']) ||
            (int)$file['error'] !== UPLOAD_ERR_OK || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name']) ||
            empty($file['size']) || $file['size'] > self::MAX_BYTES) {
            return new WP_Error('smtt_upload', 'Envie um arquivo válido de até 15 MB.');
        }
        $ext = strtolower(pathinfo(sanitize_file_name($file['name']), PATHINFO_EXTENSION));
        if (!isset(self::types()[$ext])) { return new WP_Error('smtt_upload_type','Formato não permitido. Use PDF, Word, Excel, PowerPoint, ODF, TXT ou CSV.'); }
        require_once ABSPATH.'wp-admin/includes/file.php';
        $check = wp_check_filetype_and_ext($file['tmp_name'],$file['name'],self::types());
        if (!$check['ext'] || !$check['type'] || $check['ext'] !== $ext) { return new WP_Error('smtt_upload_mime','O conteúdo não corresponde ao formato informado.'); }
        return true;
    }
    public static function upload($post_id, $file, $private) {
        if (!$file || (int)($file['error']??UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) { return true; }
        $valid=self::verify_upload($file); if (is_wp_error($valid)) { return $valid; }
        $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
        if ($private) {
            $dir=self::private_dir();
            if (!$dir) { return new WP_Error('smtt_private_unavailable','Armazenamento privado indisponível. Configure SMTT_INTRANET_PRIVATE_FILES_DIR fora do diretório público do site.'); }
            try { $random=bin2hex(random_bytes(20)); } catch (Exception $e) { return new WP_Error('smtt_random','Falha ao gerar identificador privado.'); }
            $filename=$random.'.'.$ext;
            if (!move_uploaded_file($file['tmp_name'],$dir.DIRECTORY_SEPARATOR.$filename)) { return new WP_Error('smtt_storage','Não foi possível armazenar o arquivo privado.'); }
            @chmod($dir.DIRECTORY_SEPARATOR.$filename,0600);
            // Não apagar versões anteriores automaticamente: podem ser necessárias para auditoria/backup.
            update_post_meta($post_id,'_smtt_private_file',$filename);
            update_post_meta($post_id,'_smtt_private_name',sanitize_file_name($file['name']));
            update_post_meta($post_id,'_smtt_private_type',self::types()[$ext]);
            update_post_meta($post_id,'_smtt_uploaded_name',sanitize_file_name($file['name']));
            delete_post_meta($post_id,'_smtt_file_id'); delete_post_meta($post_id,'_smtt_article_file_id');
            if (get_post_type($post_id)==='smtt_document') { delete_post_meta($post_id,'_smtt_file_url'); }
        } else {
            require_once ABSPATH.'wp-admin/includes/media.php';
            require_once ABSPATH.'wp-admin/includes/image.php';
            $id=media_handle_upload('smtt_attachment',$post_id,array(),array('test_form'=>false,'mimes'=>self::types()));
            if (is_wp_error($id)) { return $id; }
            $key=get_post_type($post_id)==='smtt_document'?'_smtt_file_id':'_smtt_article_file_id';
            update_post_meta($post_id,$key,$id);
            update_post_meta($post_id,'_smtt_uploaded_name',sanitize_file_name($file['name']));
            delete_post_meta($post_id,'_smtt_private_file'); delete_post_meta($post_id,'_smtt_private_name'); delete_post_meta($post_id,'_smtt_private_type');
            if (get_post_type($post_id)==='smtt_document') { update_post_meta($post_id,'_smtt_file_url',(string)wp_get_attachment_url($id)); }
        }
        return true;
    }
    public static function can_read($post_id) {
        if (!is_user_logged_in()) { return false; }
        $post=get_post($post_id);
        if (!$post || $post->post_status!=='publish' || !in_array($post->post_type,array('smtt_document','smtt_kb_article'),true)) { return false; }
        $module=$post->post_type==='smtt_document'?'documents':'knowledge';
        if (SMTT_Intranet_Policy::can($module,'view',$post_id)) { return true; }
        if (get_post_meta($post_id,'_smtt_status',true)==='inativo') { return false; }
        // Respeita ocultação explícita, mantendo registros legados sem metadado de visibilidade.
        if ($module==='knowledge' && get_post_meta($post_id,'_smtt_public_visible',true)==='0') { return false; }
        $sectors=wp_get_object_terms($post_id,'smtt_sector',array('fields'=>'ids'));
        if (is_wp_error($sectors)) { return false; }
        if (!$sectors) { return true; }
        $identity=SMTT_Intranet_Corporate_Identity::current();
        return !is_wp_error($identity) && (bool)array_intersect(array_map('intval',$sectors),SMTT_Intranet_Policy::sector_ids_for_identity($identity));
    }
    public static function download() {
        $id=absint($_GET['item']??0);
        if (!self::can_read($id)) { status_header(403); wp_die('Arquivo indisponível ou sem autorização.','Acesso restrito',array('response'=>403)); }
        $name=(string)get_post_meta($id,'_smtt_private_file',true);
        if (!$name || !preg_match('/^[a-f0-9]{40}\.[a-z0-9]+$/',$name) || !self::private_dir()) { wp_die('Arquivo privado indisponível.','Arquivo não encontrado',array('response'=>404)); }
        $path=self::private_dir().DIRECTORY_SEPARATOR.$name;
        if (!is_file($path) || !is_readable($path)) { wp_die('Arquivo não encontrado.', '',array('response'=>404)); }
        if (function_exists('session_write_close')) { @session_write_close(); }
        nocache_headers(); header('X-Content-Type-Options: nosniff');
        header('Content-Type: '.(string)get_post_meta($id,'_smtt_private_type',true));
        header('Content-Disposition: attachment; filename="'.str_replace(array('"',"\r","\n"),'',basename(self::file_label($id))).'"');
        header('Content-Length: '.filesize($path));
        while (ob_get_level()) { @ob_end_clean(); }
        readfile($path); exit;
    }
    public static function read_article() {
        $id=absint($_GET['item']??0);
        if (!self::can_read($id) || get_post_type($id)!=='smtt_kb_article') { wp_die('Artigo indisponível.','Acesso restrito',array('response'=>403)); }
        $post=get_post($id);
        nocache_headers();
        ?><!DOCTYPE html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo esc_html($post->post_title); ?> — Wiki SMTT</title>
        <style>body{font:16px/1.7 system-ui,sans-serif;background:#f5f8fc;color:#17304b;margin:0}.top{background:#113456;color:white;padding:18px max(22px,calc((100vw - 920px)/2))}.top a{color:white}.sheet{background:white;margin:34px auto;max-width:860px;padding:32px;border:1px solid #dde6ee;border-radius:14px}.sheet h1{line-height:1.3} .sheet article{overflow-wrap:anywhere}.sheet img{max-width:100%}@media(max-width:920px){.sheet{margin:10px;padding:20px}}</style></head><body><div class="top"><a href="<?php echo esc_url(home_url('/#base-interna')); ?>">← Voltar à Wiki SMTT</a></div><main class="sheet"><h1><?php echo esc_html($post->post_title); ?></h1><article><?php echo wp_kses_post(wpautop($post->post_content)); ?></article></main></body></html><?php
        exit;
    }
}
