<?php
/**
 * Localiza ou cria a página real da Home, sem depender do tema do WordPress.
 * Não modifica uma página inicial estática já escolhida pelo administrador.
 */
if (!defined('ABSPATH')) { exit; }

class SMTT_Intranet_Home_Routing {
    const OPTION_PAGE = 'smtt_intranet_home_page_id';
    const OPTION_SETUP = 'smtt_intranet_home_routing_v20352';
    const META_MANAGED = '_smtt_intranet_managed_home';

    public static function register() {
        // Também executado nas atualizações do plugin, quando a ativação não ocorre novamente.
        add_action('init', array(__CLASS__, 'ensure_home'), 25);
        add_filter('template_include', array(__CLASS__, 'page_template'), 90);
    }

    private static function has_home_shortcode($page_id) {
        $page = get_post((int) $page_id);
        if (!$page || $page->post_type !== 'page' || $page->post_status !== 'publish') {
            return false;
        }
        if (has_shortcode((string) $page->post_content, 'smtt_intranet_home')) {
            return true;
        }
        $elementor = get_post_meta((int) $page->ID, '_elementor_data', true);
        return is_string($elementor) && strpos($elementor, 'smtt_intranet_home') !== false;
    }

    private static function find_existing_home() {
        $front_id = (int) get_option('page_on_front', 0);
        if ($front_id && self::has_home_shortcode($front_id)) {
            return $front_id;
        }

        // Prioriza uma Home/Intranet existente, evitando páginas duplicadas.
        foreach (array('home', 'intranet', 'intranet-smtt') as $slug) {
            $page = get_page_by_path($slug, OBJECT, 'page');
            if ($page && self::has_home_shortcode($page->ID)) {
                return (int) $page->ID;
            }
        }

        // Compatível com instalações onde o shortcode já foi cadastrado em outra página.
        $pages = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'suppress_filters' => true,
        ));
        foreach ($pages as $id) {
            if (self::has_home_shortcode($id)) {
                return (int) $id;
            }
        }
        return 0;
    }

    public static function ensure_home() {
        if (get_option(self::OPTION_SETUP) === 'done') { return; }

        $page_id = self::find_existing_home();
        if (!$page_id) {
            $page_id = wp_insert_post(array(
                'post_type' => 'page',
                'post_status' => 'publish',
                'post_title' => 'Intranet SMTT',
                'post_name' => 'intranet',
                'post_content' => '[smtt_intranet_home]',
                'comment_status' => 'closed',
                'ping_status' => 'closed',
            ), true);
            if (is_wp_error($page_id) || !$page_id) {
                return; // Não marcar sucesso se o WordPress não puder criar a página.
            }
            update_post_meta((int) $page_id, self::META_MANAGED, '1');
        }

        update_option(self::OPTION_PAGE, (int) $page_id, false);

        // Instalação nova com a página padrão "Blog": colocar a Intranet na raiz.
        // Não substituir uma Home estática existente de outro site.
        if (get_option('show_on_front', 'posts') === 'posts') {
            update_option('page_on_front', (int) $page_id);
            update_option('show_on_front', 'page');
        }

        update_option(self::OPTION_SETUP, 'done', false);
    }

    public static function url() {
        $front_id = (int) get_option('page_on_front', 0);
        if (get_option('show_on_front') === 'page' && self::has_home_shortcode($front_id)) {
            return get_permalink($front_id);
        }
        $page_id = (int) get_option(self::OPTION_PAGE, 0);
        if (self::has_home_shortcode($page_id)) {
            return get_permalink($page_id);
        }
        $page_id = self::find_existing_home();
        return $page_id ? get_permalink($page_id) : '';
    }

    /**
     * Detecta a Home real, independentemente do slug escolhido pelo WordPress.
     * Compartilhada por Ramais, botões administrativos e demais recursos.
     */
    public static function is_home_request() {
        if (is_admin() || !is_page()) { return false; }
        $page_id = (int) get_queried_object_id();
        if ($page_id && self::has_home_shortcode($page_id)) { return true; }
        // Compatibilidade com páginas legadas que usam template próprio.
        return is_page('home');
    }

    public static function page_template($template) {
        if (is_admin() || !is_page()) { return $template; }
        $id = (int) get_queried_object_id();
        if (!$id || (string) get_post_meta($id, self::META_MANAGED, true) !== '1') {
            return $template;
        }
        return SMTT_INTRANET_PATH . 'includes/public/intranet-home-template.php';
    }
}
