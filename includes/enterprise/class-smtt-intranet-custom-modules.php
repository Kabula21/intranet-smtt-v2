<?php
/** Construtor seguro de catálogos padronizados (links/cards), sem PHP arbitrário. */
if (!defined('ABSPATH')) { exit; }
final class SMTT_Intranet_Custom_Modules {
    public static function register() {
        add_action('init',array(__CLASS__,'register_types'),10);
        add_action('admin_post_smtt_portal_catalog',array(__CLASS__,'save_catalog'));
        add_action('admin_post_smtt_portal_catalog_delete',array(__CLASS__,'delete_catalog'));
    }
    public static function register_types() {
        register_post_type('smtt_custom_card',array('labels'=>array('name'=>'Recursos de catálogos','singular_name'=>'Recurso'),
            'public'=>false,'show_ui'=>false,'show_in_menu'=>false,'supports'=>array('title','editor'),
            'capability_type'=>'post','map_meta_cap'=>true));
        register_taxonomy('smtt_catalog','smtt_custom_card',array('labels'=>array('name'=>'Catálogos personalizados','singular_name'=>'Catálogo'),
            'public'=>false,'show_ui'=>false,'hierarchical'=>false,'rewrite'=>false));
        register_taxonomy_for_object_type('smtt_sector','smtt_custom_card');
    }
    public static function save_catalog() {
        if (!SMTT_Intranet_Corporate_Identity::is_admin()) { wp_die('Acesso exclusivo do CPD.','',array('response'=>403)); }
        check_admin_referer('smtt_portal_catalog');
        $id=absint($_POST['term_id']??0);
        $name=sanitize_text_field(wp_unslash($_POST['name']??''));
        $description=sanitize_textarea_field(wp_unslash($_POST['description']??''));
        $icon=sanitize_text_field(wp_unslash($_POST['icon']??''));
        if (!$name || strlen($name)>300 || ($id&&!term_exists($id,'smtt_catalog'))) { wp_die('Catálogo inválido.','',array('response'=>400)); }
        if ($icon!==''&&!SMTT_Intranet_Icon_Library::valid_value($icon)) { wp_die('Ícone inválido.','',array('response'=>400)); }
        $res=$id?wp_update_term($id,'smtt_catalog',array('name'=>$name,'description'=>$description)):
            wp_insert_term($name,'smtt_catalog',array('description'=>$description));
        if (is_wp_error($res)) { wp_die(esc_html($res->get_error_message()),'',array('response'=>400)); }
        $id=(int)$res['term_id'];
        update_term_meta($id,'_smtt_portal_icon',$icon);
        // Campo legado preservado em edições: visibilidade agora é gerida pelo editor visual.
        if (!isset($_POST['term_id']) || absint($_POST['term_id']) === 0) { update_term_meta($id,'_smtt_portal_public','0'); }
        update_term_meta($id,'_smtt_portal_position',sanitize_key(wp_unslash($_POST['position']??'bottom'))==='top'?'top':'bottom');
        SMTT_Intranet_Policy::audit('save_custom_catalog',(string)$id);
        (new SMTT_Intranet_Public())->clear_home_cache();
        wp_safe_redirect(SMTT_Intranet_Manager::url(array('section'=>'catalogs','catalog'=>$id,'msg'=>'saved')));exit;
    }
    /** Somente catálogos personalizados; módulos padrão nunca são excluídos. */
    public static function delete_catalog() {
        if (!SMTT_Intranet_Corporate_Identity::is_admin()) {
            wp_die('Acesso exclusivo do CPD.','Acesso negado',array('response'=>403));
        }
        check_admin_referer('smtt_portal_catalog_delete');
        $id=absint($_POST['catalog_id']??0);
        if (!$id || !term_exists($id,'smtt_catalog')) {
            wp_die('Módulo personalizado inválido.','',array('response'=>400));
        }
        // Os itens vão para a lixeira, com chance de recuperação em manutenção.
        // Não remover o termo se um item não puder ser colocado na lixeira.
        $posts=get_posts(array('post_type'=>'smtt_custom_card','post_status'=>'any',
            'posts_per_page'=>-1,'fields'=>'ids',
            'tax_query'=>array(array('taxonomy'=>'smtt_catalog','field'=>'term_id','terms'=>array($id)))));
        foreach ($posts as $post_id) {
            if (!wp_trash_post((int)$post_id)) {
                wp_die('Não foi possível enviar todos os itens à lixeira. O módulo permanece cadastrado.','',array('response'=>500));
            }
        }
        $deleted=wp_delete_term($id,'smtt_catalog');
        if (is_wp_error($deleted) || !$deleted) {
            wp_die('Não foi possível excluir o módulo personalizado.','',array('response'=>500));
        }
        SMTT_Intranet_Policy::audit('delete_custom_catalog',(string)$id);
        (new SMTT_Intranet_Public())->clear_home_cache();
        wp_safe_redirect(SMTT_Intranet_Manager::url(array('section'=>'catalogs','msg'=>'deleted')));
        exit;
    }

    public static function home_html($position = 'bottom', $preview_id = 0) {
        if (!taxonomy_exists('smtt_catalog') || ($position === 'top' && !$preview_id)) { return ''; }
        $terms=get_terms(array('taxonomy'=>'smtt_catalog','hide_empty'=>false));
        if (is_wp_error($terms) || !$terms) { return ''; }
        $out='';
        $identity=SMTT_Intranet_Corporate_Identity::current();
        $allowed=is_wp_error($identity)?array():SMTT_Intranet_Policy::sector_ids_for_identity($identity);
        foreach ($terms as $term) {
            if ($preview_id && (int)$preview_id !== (int)$term->term_id) { continue; }
            if (!$preview_id && !SMTT_Intranet_Home_Layout::visible('catalog-'.(int)$term->term_id)) { continue; }
            // A posição é controlada pelo layout visual publicado, não por cima/baixo.
            $entries=get_posts(array('post_type'=>'smtt_custom_card','post_status'=>'publish','posts_per_page'=>80,
                'tax_query'=>array(array('taxonomy'=>'smtt_catalog','field'=>'term_id','terms'=>array($term->term_id)))));
            $cards='';
            foreach ($entries as $post) {
                if (get_post_meta($post->ID,'_smtt_status',true)!=='ativo') { continue; }
                $post_sectors=wp_get_object_terms($post->ID,'smtt_sector',array('fields'=>'ids'));
                if (!$preview_id && !is_wp_error($post_sectors) && $post_sectors && !array_intersect(array_map('intval',$post_sectors),$allowed) && !SMTT_Intranet_Corporate_Identity::is_admin()) { continue; }
                $url=esc_url_raw((string)get_post_meta($post->ID,'_smtt_url',true),array('http','https'));
                $icon=get_post_meta($post->ID,'_smtt_icon',true);
                $description=get_post_meta($post->ID,'_smtt_description',true);
                $tag=$url?'a':'div';
                $attrs=$url?' href="'.esc_url($url).'" target="_blank" rel="noopener noreferrer"':'';
                $cards.='<'.$tag.' class="smtt-portal-custom-link"'.$attrs.'>'.
                    SMTT_Intranet_Icon_Library::html($icon).'<span><strong>'.esc_html($post->post_title).'</strong>'.
                    ($description?'<small>'.esc_html($description).'</small>':'').'</span></'.$tag.'>';
            }
            if (!$cards) { $cards='<div class="smtt-portal-empty">Nenhum item publicado neste módulo ainda.</div>'; }
            $out.='<section id="smtt-catalog-'.(int)$term->term_id.'" class="smtt-card smtt-portal-custom-module" aria-label="'.esc_attr($term->name).'">'.
                '<div class="smtt-card-header"><div>'.SMTT_Intranet_Home_Layout::header('catalog-'.(int)$term->term_id,SMTT_Intranet_Icon_Library::html(get_term_meta($term->term_id,'_smtt_portal_icon',true)),$term->name,$term->description).'</div></div>'.
                '<div class="smtt-portal-custom-grid">'.$cards.'</div></section>';
        }
        return $out;
    }
}
