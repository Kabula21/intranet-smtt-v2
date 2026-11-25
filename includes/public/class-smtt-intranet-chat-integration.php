<?php
/** Navegação e ativos do Chat SMTT nativo — sem dependência de outros plugins. */
if (!defined('ABSPATH')) { exit; }

class SMTT_Intranet_Chat_Integration {
    const OPTION_PAGE = 'smtt_intranet_chat_page_id';
    const META_MANAGED = '_smtt_intranet_managed_chat';

    public static function register() {
        add_action('init', array(__CLASS__, 'ensure_page'), 26);
        add_filter('template_include', array(__CLASS__, 'page_template'), 91);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'assets'));
        add_filter('show_admin_bar', array(__CLASS__, 'hide_admin_bar'));
        add_action('template_redirect', array(__CLASS__, 'protect_page'), 2);
        add_shortcode('smtt_chat_area', array(__CLASS__, 'render_area'));
    }

    public static function page_id() {
        $id = (int)get_option(self::OPTION_PAGE, 0);
        $page = $id ? get_post($id) : null;
        if ($page && $page->post_type === 'page' && $page->post_status === 'publish') { return $id; }
        $page = get_page_by_path('chat-smtt', OBJECT, 'page');
        return ($page && $page->post_status === 'publish') ? (int)$page->ID : 0;
    }

    public static function url() {
        $id = self::page_id();
        return $id ? get_permalink($id) : home_url('/chat-smtt/');
    }

    public static function ensure_page() {
        if (self::page_id() || get_option('smtt_intranet_chat_page_setup') === 'created') { return; }
        $id = wp_insert_post(array(
            'post_type'=>'page', 'post_status'=>'publish', 'post_title'=>'Chat SMTT',
            'post_name'=>'chat-smtt', 'post_content'=>'[smtt_chat_area]',
            'comment_status'=>'closed', 'ping_status'=>'closed',
        ), true);
        if (is_wp_error($id) || !$id) { return; }
        update_post_meta((int)$id, self::META_MANAGED, '1');
        update_option(self::OPTION_PAGE, (int)$id, false);
        update_option('smtt_intranet_chat_page_setup', 'created', false);
    }

    public static function is_chat_page() {
        return !is_admin() && (is_page('chat-smtt') || ((int)get_queried_object_id() > 0 && (int)get_queried_object_id() === self::page_id()));
    }

    public static function hide_admin_bar($show) { return self::is_chat_page() ? false : $show; }

    public static function protect_page() {
        if (!self::is_chat_page()) { return; }
        if (!defined('DONOTCACHEPAGE')) { define('DONOTCACHEPAGE', true); }
        nocache_headers();
        if (!is_user_logged_in()) { auth_redirect(); }
    }

    public static function assets() {
        if (self::is_chat_page()) {
            wp_enqueue_style('smtt-native-chat', SMTT_INTRANET_URL . 'assets/chat/chat.css', array(), SMTT_INTRANET_VERSION);
            wp_enqueue_script('smtt-native-chat', SMTT_INTRANET_URL . 'assets/chat/chat.js', array(), SMTT_INTRANET_VERSION, true);
            $settings = SMTT_Intranet_Native_Chat::settings();
            wp_localize_script('smtt-native-chat', 'smttChatConfig', array(
                'rest' => esc_url_raw(rest_url('smtt-intranet/v1/chat/')),
                'nonce' => wp_create_nonce('wp_rest'),
                'userId' => get_current_user_id(),
                'maxLength' => (int)$settings['max_length'],
                'allowGroups' => false,
                'enabled' => SMTT_Intranet_Native_Chat::enabled(),
            ));
        }
        if (SMTT_Intranet_Home_Routing::is_home_request()) {
            wp_enqueue_style('smtt-home-chat', SMTT_INTRANET_URL . 'assets/chat/home-chat.css', array(), SMTT_INTRANET_VERSION);
            wp_enqueue_script('smtt-home-chat', SMTT_INTRANET_URL . 'assets/chat/home-chat.js', array(), SMTT_INTRANET_VERSION, true);
            wp_localize_script('smtt-home-chat', 'smttChatHomeConfig', array(
                'rest' => esc_url_raw(rest_url('smtt-intranet/v1/chat/')),
                'nonce' => wp_create_nonce('wp_rest'),
                'url' => self::url(),
                'userId' => get_current_user_id(),
                'loggedIn' => is_user_logged_in(),
                'enabled' => SMTT_Intranet_Native_Chat::enabled(),
                'loginUrl' => wp_login_url(self::url()),
            ));
        }
    }

    public static function render_area() {
        if (!is_user_logged_in()) { return '<p>Faça login para acessar o Chat SMTT.</p>'; }
        return '<p><a href="'.esc_url(self::url()).'">Abrir Chat SMTT</a></p>';
    }

    public static function page_template($template) {
        if (self::is_chat_page()) { return SMTT_INTRANET_PATH . 'includes/public/smtt-chat-template.php'; }
        return $template;
    }
}
