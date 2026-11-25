<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Public {

    public function register()
{
    add_shortcode('smtt_intranet_home', array($this, 'render_intranet_home'));
    add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    add_filter('body_class', array($this, 'add_intranet_body_class'));
    add_action('template_redirect', array($this, 'require_home_login'), 0);
    add_action('template_redirect', array($this, 'disable_agenda_query_cache'));
    add_action('wp_head', array($this, 'render_prepaint_theme_script'), 0);


    // Limpa o cache da home quando algum conteúdo importante for salvo.
    add_action('save_post_smtt_sector_folder', array($this, 'clear_home_cache'));
    add_action('save_post_smtt_internal_system', array($this, 'clear_home_cache'));
    add_action('save_post_smtt_document', array($this, 'clear_home_cache'));
    add_action('save_post_smtt_agenda_item', array($this, 'clear_home_cache'));
    add_action('save_post_smtt_kb_article', array($this, 'clear_home_cache'));
    add_action('save_post_smtt_mural_item', array($this, 'clear_home_cache'));
    // v51: invalidação adicional depois que as metas do Mural já foram gravadas.
    add_action('smtt_mural_item_meta_saved', array($this, 'clear_home_cache'));
    add_action('save_post_smtt_birthday_person', array($this, 'clear_home_cache'));
    add_action('save_post_smtt_useful_link', array($this, 'clear_home_cache'));

    // Limpa o cache quando setores/categorias forem alterados.
    add_action('created_smtt_sector', array($this, 'clear_home_cache'));
    add_action('edited_smtt_sector', array($this, 'clear_home_cache'));
    add_action('delete_smtt_sector', array($this, 'clear_home_cache'));

    add_action('created_smtt_kb_category', array($this, 'clear_home_cache'));
    add_action('edited_smtt_kb_category', array($this, 'clear_home_cache'));
    add_action('delete_smtt_kb_category', array($this, 'clear_home_cache'));

    add_action('created_smtt_mural_category', array($this, 'clear_home_cache'));
    add_action('edited_smtt_mural_category', array($this, 'clear_home_cache'));
    add_action('delete_smtt_mural_category', array($this, 'clear_home_cache'));

        // Limpa cache quando categorias/documentos/agenda forem alterados.
    add_action('created_smtt_document_category', array($this, 'clear_home_cache'));
    add_action('edited_smtt_document_category', array($this, 'clear_home_cache'));
    add_action('delete_smtt_document_category', array($this, 'clear_home_cache'));

    add_action('created_smtt_agenda_space', array($this, 'clear_home_cache'));
    add_action('edited_smtt_agenda_space', array($this, 'clear_home_cache'));
    add_action('delete_smtt_agenda_space', array($this, 'clear_home_cache'));

    add_action('created_smtt_agenda_category', array($this, 'clear_home_cache'));
    add_action('edited_smtt_agenda_category', array($this, 'clear_home_cache'));
    add_action('delete_smtt_agenda_category', array($this, 'clear_home_cache'));

    // Limpa cache quando posts forem excluídos, enviados para lixeira ou restaurados.
    add_action('trashed_post', array($this, 'clear_home_cache'));
    add_action('deleted_post', array($this, 'clear_home_cache'));
    add_action('untrashed_post', array($this, 'clear_home_cache'));

    // Limpa cache quando opções dos aniversariantes forem alteradas.
    if (class_exists('SMTT_Intranet_Birthdays')) {
        add_action('update_option_' . SMTT_Intranet_Birthdays::OPTION_DISPLAY_MODE, array($this, 'clear_home_cache'));
        add_action('update_option_' . SMTT_Intranet_Birthdays::OPTION_SHOW_PHOTO, array($this, 'clear_home_cache'));
        add_action('update_option_' . SMTT_Intranet_Birthdays::OPTION_SHOW_SECTOR, array($this, 'clear_home_cache'));
        add_action('update_option_' . SMTT_Intranet_Birthdays::OPTION_SHOW_ROLE, array($this, 'clear_home_cache'));
        add_action('update_option_' . SMTT_Intranet_Birthdays::OPTION_MAX_ITEMS, array($this, 'clear_home_cache'));
    }
}

    public function enqueue_assets()
    {
        wp_register_style(
            'smtt-intranet-public',
            SMTT_INTRANET_URL . 'assets/public/css/public.css',
            array(),
            file_exists(SMTT_INTRANET_PATH . 'assets/public/css/public.css') ? (string) filemtime(SMTT_INTRANET_PATH . 'assets/public/css/public.css') : (defined('SMTT_INTRANET_VERSION') ? SMTT_INTRANET_VERSION : '1.0.0')
        );

        wp_register_script(
            'smtt-intranet-public',
            SMTT_INTRANET_URL . 'assets/public/js/public.js',
            array(),
            file_exists(SMTT_INTRANET_PATH . 'assets/public/js/public.js') ? (string) filemtime(SMTT_INTRANET_PATH . 'assets/public/js/public.js') : (defined('SMTT_INTRANET_VERSION') ? SMTT_INTRANET_VERSION : '1.0.0'),
            true
        );

        /*
         * v35: carrega o CSS antes do wp_head quando a página contém a home da
         * intranet. Antes o enqueue só acontecia durante a renderização do
         * shortcode, tarde demais para o primeiro paint, causando FOUC e a logo
         * aparecer no tamanho intrínseco antes do CSS chegar.
         */
        if ($this->is_intranet_request()) {
            wp_enqueue_style('smtt-intranet-public');
            wp_enqueue_script('smtt-intranet-public');
        }
    }

    public function add_intranet_body_class($classes)
    {
        if ($this->is_intranet_request() && !in_array('smtt-intranet-page', $classes, true)) {
            $classes[] = 'smtt-intranet-page';
        }

        return $classes;
    }

    /**
     * Aplica a preferência de tema antes do primeiro paint.
     * Isso evita o flash claro -> escuro em recarregamentos e navegações de fallback.
     */
    public function render_prepaint_theme_script()
    {
        if (!$this->is_intranet_request()) {
            return;
        }
        ?>
        <script id="smtt-theme-prepaint">(function(){try{if(window.localStorage&&window.localStorage.getItem('smtt-theme')==='dark'){document.documentElement.classList.add('smtt-theme-dark-preload');}}catch(e){}})();</script>
        <?php
    }

    /** A Home contém informações internas e dados por usuário: exigir sessão em qualquer ambiente. */
    public function require_home_login()
    {
        if (!$this->is_intranet_request()) {
            return;
        }
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        nocache_headers();
        if (!is_user_logged_in()) {
            auth_redirect();
            exit;
        }
    }

    public function disable_agenda_query_cache()
    {
        if (!$this->is_intranet_request()) {
            return;
        }

        if (
            isset($_GET['smtt_agenda_year']) ||
            isset($_GET['smtt_agenda_month']) ||
            isset($_GET['smtt_agenda_date'])
        ) {
            if (!defined('DONOTCACHEPAGE')) {
                define('DONOTCACHEPAGE', true);
            }
            nocache_headers();
        }
    }

    private function is_intranet_request()
    {
        if (is_admin()) {
            return false;
        }

        global $post;

        if ($post instanceof WP_Post && has_shortcode((string) $post->post_content, 'smtt_intranet_home')) {
            return true;
        }

        $queried_id = get_queried_object_id();
        if ($queried_id) {
            $queried_post = get_post($queried_id);
            if ($queried_post instanceof WP_Post && has_shortcode((string) $queried_post->post_content, 'smtt_intranet_home')) {
                return true;
            }

            /*
             * Page builders como Elementor podem guardar o shortcode em post_meta,
             * portanto ele não aparece em post_content durante wp_enqueue_scripts.
             * Detectar esse caso garante que o CSS chegue no <head> e evita FOUC.
             */
            $elementor_data = get_post_meta($queried_id, '_elementor_data', true);
            if (is_string($elementor_data) && strpos($elementor_data, 'smtt_intranet_home') !== false) {
                return true;
            }
        }

        /*
         * O próprio módulo de Ramais já considera a página de slug "home" como
         * a home da intranet. Mantemos o mesmo fallback para o carregamento precoce
         * dos assets, inclusive quando o shortcode é inserido pelo tema/template.
         */
        if (is_page('home')) {
            return true;
        }

        return false;
    }

    public function render_intranet_home()
    {
        // Não confiar em HTTP_HOST para permitir visitantes em produção ou em laboratório.
        if (!is_user_logged_in()) {
            return '<div class="smtt-front-login-required">Entre com sua conta corporativa para acessar a Intranet.</div>';
        }

        wp_enqueue_style('smtt-intranet-public');
        wp_enqueue_script('smtt-intranet-public');

        if (class_exists('SMTT_Intranet_Access_Gate')) {
            SMTT_Intranet_Access_Gate::enqueue_detector();
        }

        $current_user = wp_get_current_user();
        $smtt_display_name = is_user_logged_in() ? $current_user->display_name : 'Visitante';
        $smtt_login_url    = wp_login_url(get_permalink());
        $smtt_logout_url   = wp_logout_url(get_permalink());
        $today_label  = date_i18n('d/m/Y');
        $today_full   = date_i18n('l, d \d\e F \d\e Y');
        $today_db     = current_time('Y-m-d');
        $current_hour = (int) current_time('G');
        $smtt_greeting = $current_hour < 12 ? 'Bom dia' : ($current_hour < 18 ? 'Boa tarde' : 'Boa noite');

        $announcements_module = new SMTT_Intranet_Announcements();
$birthdays_module     = new SMTT_Intranet_Birthdays();

// Notícias: a Home lê exclusivamente o snapshot local persistente.
// Qualquer consulta ao portal e download de imagens acontece via WP-Cron/background.
$announcements = $announcements_module->get_latest_posts(3);

// v35: carrega os aniversariantes uma única vez; o cache é invalidado nos hooks de salvamento.
$birthdays_all = $this->smtt_get_cached_home_data(
    'birthdays_all',
    function () use ($birthdays_module) {
        return $birthdays_module->get_month_birthdays();
    },
    15 * MINUTE_IN_SECONDS
);
$birthdays = $birthdays_module->get_front_birthdays(null, $birthdays_all);

$access_folders = $this->smtt_get_cached_home_data(
    'access_folders_sidebar',
    function () {
        return get_posts(array(
            'post_type'              => 'smtt_sector_folder',
            'posts_per_page'         => 50,
            'post_status'            => 'publish',
            'meta_key'               => '_smtt_sort_order',
            'orderby'                => 'meta_value_num',
            'order'                  => 'ASC',
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
            'meta_query'             => array(
                'relation' => 'AND',
                array(
                    'key'     => '_smtt_status',
                    'value'   => 'ativo',
                    'compare' => '=',
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'key'     => '_smtt_show_sidebar',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key'     => '_smtt_show_sidebar',
                        'value'   => '0',
                        'compare' => '!=',
                    ),
                ),
            ),
        ));
    },
    30 * MINUTE_IN_SECONDS
);

$systems = $this->smtt_get_cached_home_data(
    'systems_sidebar',
    function () {
        return get_posts(array(
            'post_type'              => 'smtt_internal_system',
            'posts_per_page'         => 50,
            'post_status'            => 'publish',
            'meta_key'               => '_smtt_sort_order',
            'orderby'                => 'meta_value_num',
            'order'                  => 'ASC',
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
            'meta_query'             => array(
                'relation' => 'AND',
                array(
                    'key'     => '_smtt_status',
                    'value'   => 'ativo',
                    'compare' => '=',
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'key'     => '_smtt_show_sidebar',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key'     => '_smtt_show_sidebar',
                        'value'   => '0',
                        'compare' => '!=',
                    ),
                ),
            ),
        ));
    },
    30 * MINUTE_IN_SECONDS
);

// Central de Acessos: independente da navbar. Sem o meta (registros antigos),
// mantém o comportamento legado. A associação ao setor é obrigatória para aparecer.
$center_systems = $this->smtt_get_cached_home_data(
    'systems_access_center_v1',
    function () {
        return get_posts(array(
            'post_type' => 'smtt_internal_system', 'posts_per_page' => 200,
            'post_status' => 'publish', 'meta_key' => '_smtt_sort_order',
            'orderby' => 'meta_value_num', 'order' => 'ASC',
            'update_post_meta_cache' => true, 'update_post_term_cache' => true,
            'meta_query' => array('relation' => 'AND',
                array('key'=>'_smtt_status','value'=>'ativo'),
                array('relation'=>'OR',
                    array('key'=>'_smtt_show_access_center','compare'=>'NOT EXISTS'),
                    array('key'=>'_smtt_show_access_center','value'=>'1'),
                ),
            ),
        ));
    }, 30 * MINUTE_IN_SECONDS
);
$center_folders = $this->smtt_get_cached_home_data(
    'folders_access_center_v1',
    function () {
        return get_posts(array(
            'post_type' => 'smtt_sector_folder', 'posts_per_page' => 200,
            'post_status' => 'publish', 'meta_key' => '_smtt_sort_order',
            'orderby' => 'meta_value_num', 'order' => 'ASC',
            'update_post_meta_cache' => true, 'update_post_term_cache' => true,
            'meta_query' => array('relation' => 'AND',
                array('key'=>'_smtt_status','value'=>'ativo'),
                array('relation'=>'OR',
                    array('key'=>'_smtt_show_access_center','compare'=>'NOT EXISTS'),
                    array('key'=>'_smtt_show_access_center','value'=>'1'),
                ),
            ),
        ));
    }, 30 * MINUTE_IN_SECONDS
);

$useful_links = $this->smtt_get_cached_home_data(
    'useful_links_home',
    function () {
        return get_posts(array(
            'post_type'              => 'smtt_useful_link',
            'posts_per_page'         => -1,
            'post_status'            => 'publish',
            'meta_key'               => '_smtt_sort_order',
            'orderby'                => 'meta_value_num',
            'order'                  => 'ASC',
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
            'meta_query'             => array(
                array(
                    'key'     => '_smtt_status',
                    'value'   => 'ativo',
                    'compare' => '=',
                ),
            ),
        ));
    },
    30 * MINUTE_IN_SECONDS
);

$access_sectors = $this->smtt_get_cached_home_data(
    'access_sectors_home',
    function () {
        $terms = get_terms(array(
            'taxonomy'   => 'smtt_sector',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ));
        return is_wp_error($terms) ? array() : $terms;
    },
    30 * MINUTE_IN_SECONDS
);

$access_is_manager = class_exists('SMTT_Intranet_Access_Permissions')
    ? SMTT_Intranet_Access_Permissions::user_is_manager()
    : current_user_can('manage_options');
$current_user_sector_id = class_exists('SMTT_Intranet_Access_Permissions')
    ? SMTT_Intranet_Access_Permissions::get_user_sector_id()
    : (int) get_user_meta(get_current_user_id(), 'smtt_user_sector', true);

$documents = $this->smtt_get_cached_home_data(
    'documents_home',
    function () {
        return get_posts(array(
            'post_type'              => 'smtt_document',
            'posts_per_page'         => 5,
            'post_status'            => 'publish',
            'meta_key'               => '_smtt_sort_order',
            'orderby'                => 'meta_value_num',
            'order'                  => 'ASC',
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
        ));
    },
    30 * MINUTE_IN_SECONDS
);

$mural_candidates = get_posts(array(
    'post_type'              => 'smtt_mural_item',
    'posts_per_page'         => 100,
    'post_status'            => 'publish',
    'orderby'                => array('date' => 'DESC', 'ID' => 'DESC'),
    'update_post_meta_cache' => true,
    'update_post_term_cache' => true,
    'no_found_rows'          => true,
    'suppress_filters'       => true,
));

/*
 * v52: o Mural deixa de depender do cache da home e de metas de visibilidade
 * legadas. A regra operacional passa a ser simples e previsível:
 * post publicado + status ativo = aparece no front.
 *
 * Isso evita que _smtt_public_visible/_smtt_public_visibility_set antigos ou
 * períodos gravados em versões anteriores escondam silenciosamente um aviso
 * que o administrador vê como "Ativo" no backend.
 */
$mural_items = array();

foreach ($mural_candidates as $item) {
    $post_id = (int) $item->ID;
    $status  = strtolower(trim((string) get_post_meta($post_id, '_smtt_status', true)));

    // Registros legados sem meta de status também são considerados ativos.
    if ($status !== '' && $status !== 'ativo') {
        continue;
    }

    $mural_items[] = $item;
}

usort($mural_items, function ($a, $b) {
    $order_a = (int) get_post_meta($a->ID, '_smtt_sort_order', true);
    $order_b = (int) get_post_meta($b->ID, '_smtt_sort_order', true);

    if ($order_a !== $order_b) {
        return $order_a <=> $order_b;
    }

    $date_a = (int) get_post_time('U', true, $a->ID);
    $date_b = (int) get_post_time('U', true, $b->ID);

    if ($date_a === $date_b) {
        return (int) $b->ID <=> (int) $a->ID;
    }

    return $date_b <=> $date_a;
});

$mural_items = array_slice($mural_items, 0, 12);

$logo_url = SMTT_INTRANET_URL . 'assets/public/img/logo-smtt.png';
$chat_interno_url = SMTT_Intranet_Chat_Integration::url();
$smtt_first_name = trim($current_user->first_name);
if ($smtt_first_name === '') {
    $smtt_first_name = $smtt_display_name;
}

$tour_replay_available = false;
if (is_user_logged_in() && class_exists('SMTT_Intranet_Tour')) {
    $tour_settings = SMTT_Intranet_Tour::get_settings();
    $tour_replay_available = isset($tour_settings['enabled']) && $tour_settings['enabled'] === '1';
}

        ob_start();
        ?>
        <div class="smtt-app">
            <header class="smtt-topbar smtt-topbar--modern">
                <div class="smtt-topbar-left">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="SMTT Aracaju" class="smtt-topbar-logo" width="78" height="34" decoding="async">
                    <div class="smtt-topbar-brand">
                        <h1>Intranet SMTT / Aracaju</h1>
                        <span class="smtt-topbar-kicker">Superintendência Municipal de Transportes e Trânsito</span>
                    </div>
                </div>

                <nav class="smtt-topbar-nav" aria-label="Navegação principal">
                    <div class="smtt-topbar-nav-group" data-smtt-nav-group>
                        <button type="button" class="smtt-topbar-nav-item" id="smttSystemsToggle" aria-expanded="false" aria-controls="smttSystemsDropdown">
                            <?php echo $this->sidebar_icon('system'); ?><span>Sistemas</span>
                        </button>
                        <div class="smtt-nav-dropdown smtt-nav-dropdown--systems" id="smttSystemsDropdown" hidden>
                            <div class="smtt-nav-dropdown-head">
                                <div>
                                    <strong>Sistemas</strong>
                                    <small>Acessos institucionais</small>
                                </div>
                            </div>
                            <div class="smtt-nav-dropdown-list">
                                <?php if (!empty($systems)) : ?>
                                    <?php foreach ($systems as $item) : ?>
                                        <?php
                                        $path = get_post_meta($item->ID, '_smtt_access_path', true);
                                        $can_access_system = class_exists('SMTT_Intranet_Access_Permissions')
                                            ? SMTT_Intranet_Access_Permissions::can_access_post($item->ID)
                                            : true;
                                        $system_denied_message = class_exists('SMTT_Intranet_Access_Permissions')
                                            ? SMTT_Intranet_Access_Permissions::denied_message()
                                            : __('Você não possui permissão para acessar este sistema.', 'smtt-intranet');
                                        ?>
                                        <a class="smtt-nav-dropdown-link <?php echo $can_access_system ? '' : 'smtt-restricted-access'; ?>"
                                           href="<?php echo esc_url(($can_access_system && $path) ? $path : '#'); ?>"
                                           <?php if ($can_access_system && $path) : ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>
                                           data-access-allowed="<?php echo $can_access_system ? '1' : '0'; ?>"
                                           data-access-message="<?php echo esc_attr($system_denied_message); ?>">
                                            <span class="smtt-nav-dropdown-icon"><?php echo $this->sidebar_icon('system'); ?></span>
                                            <span><?php echo esc_html(get_the_title($item->ID)); ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <div class="smtt-nav-dropdown-empty">Nenhum sistema cadastrado.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>              

                    <div class="smtt-topbar-nav-group" data-smtt-nav-group>
                        <button type="button" class="smtt-topbar-nav-item" id="smttDocumentsToggle" aria-expanded="false" aria-controls="smttDocumentsDropdown">
                            <?php echo $this->sidebar_icon('document'); ?><span>Documentos</span>
                        </button>
                        <div class="smtt-nav-dropdown smtt-nav-dropdown--documents" id="smttDocumentsDropdown" hidden>
                            <div class="smtt-nav-dropdown-head">
                                <div>
                                    <strong>Documentos</strong>
                                    <small>Modelos e arquivos internos</small>
                                </div>
                            </div>
                            <div class="smtt-nav-doc-search">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.35-4.35" stroke-linecap="round"></path></svg>
                                <input type="search" id="smttNavDocumentSearch" placeholder="Pesquisar documentos..." autocomplete="off">
                            </div>
                            <div class="smtt-nav-dropdown-list smtt-nav-documents-list" id="smttNavDocumentList">
                                <?php if (!empty($documents)) : ?>
                                    <?php foreach ($documents as $item) : ?>
                                        <?php
                                        $doc_description = get_post_meta($item->ID, '_smtt_description', true);
                                        $doc_file_url    = SMTT_Intranet_Content_Files::link($item->ID);
                                        ?>
                                        <a class="smtt-nav-dropdown-link smtt-nav-document-link" href="<?php echo esc_url($doc_file_url ? $doc_file_url : '#'); ?>" target="_blank" rel="noopener noreferrer" data-document-search="<?php echo esc_attr(get_the_title($item->ID) . ' ' . $doc_description); ?>">
                                            <span class="smtt-nav-dropdown-icon"><?php echo $this->sidebar_icon('document'); ?></span>
                                            <span class="smtt-nav-document-copy"><strong><?php echo esc_html(get_the_title($item->ID)); ?></strong><?php if ($doc_description) : ?><small><?php echo esc_html($doc_description); ?></small><?php endif; ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <div class="smtt-nav-dropdown-empty">Nenhum documento disponível.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="smtt-topbar-nav-group" data-smtt-nav-group>
                        <button type="button" class="smtt-topbar-nav-item" id="smttServicesToggle" aria-expanded="false" aria-controls="smttServicesDropdown">
                            <?php echo $this->sidebar_icon('folder'); ?><span>Departamentos</span>
                        </button>
                        <div class="smtt-nav-dropdown smtt-nav-dropdown--services" id="smttServicesDropdown" hidden>
                            <div class="smtt-nav-dropdown-head">
                                <div>
                                    <strong>Departamentos</strong>
                                    <small>Pastas de acesso por setor</small>
                                </div>
                            </div>
                            <div class="smtt-nav-dropdown-list">
                                <?php if (!empty($access_folders)) : ?>
                                    <?php foreach ($access_folders as $item) : ?>
                                        <?php
                                        $path = get_post_meta($item->ID, '_smtt_server_path', true);
                                        $can_access_folder = class_exists('SMTT_Intranet_Access_Permissions')
                                            ? SMTT_Intranet_Access_Permissions::can_access_post($item->ID)
                                            : true;
                                        $folder_denied_message = class_exists('SMTT_Intranet_Access_Permissions')
                                            ? SMTT_Intranet_Access_Permissions::denied_message()
                                            : __('Você não possui permissão para acessar esta pasta.', 'smtt-intranet');
                                        ?>
                                        <a class="smtt-nav-dropdown-link <?php echo $can_access_folder ? '' : 'smtt-restricted-access'; ?>"
                                           href="<?php echo esc_url(($can_access_folder && $path) ? $path : '#'); ?>"
                                           <?php if ($can_access_folder && $path) : ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>
                                           data-access-allowed="<?php echo $can_access_folder ? '1' : '0'; ?>"
                                           data-access-message="<?php echo esc_attr($folder_denied_message); ?>">
                                            <span class="smtt-nav-dropdown-icon"><?php echo $this->sidebar_icon('folder'); ?></span>
                                            <span><?php echo esc_html(get_the_title($item->ID)); ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <div class="smtt-nav-dropdown-empty">Nenhuma pasta cadastrada.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </nav>

                <div class="smtt-topbar-right">
                    <div class="smtt-chat-quick" data-smtt-chat-home>
                        <button type="button" class="smtt-topbar-chat-btn" data-chat-toggle aria-expanded="false" aria-controls="smtt-chat-quick-dropdown" aria-label="Conversas e notificações do Chat SMTT" title="Chat SMTT">
                            <span class="smtt-topbar-chat-icon">
                                <img src="<?php echo esc_url(SMTT_INTRANET_URL . 'assets/chat/chat-mark.svg'); ?>" width="26" height="26" alt="" aria-hidden="true">
                            </span>
                            <span class="smtt-topbar-chat-dot" data-chat-badge hidden></span>
                        </button>
                        <div id="smtt-chat-quick-dropdown" class="smtt-chat-quick-dropdown" data-chat-dropdown hidden>
                            <div class="smtt-chat-quick-header"><img src="<?php echo esc_url(SMTT_INTRANET_URL . 'assets/chat/chat-mark.svg'); ?>" width="32" height="32" alt=""><span><strong>Chat SMTT</strong><small>Conversas recentes</small></span></div>
                            <div class="smtt-chat-quick-items" data-chat-recent><p class="smtt-chat-menu-note">Carregando conversas…</p></div>
                            <a data-chat-full class="smtt-chat-quick-full" href="<?php echo esc_url($chat_interno_url); ?>" target="_blank" rel="noopener noreferrer">Abrir chat completo <span aria-hidden="true">↗</span></a>
                        </div>
                    </div>

                    <button type="button" class="smtt-theme-toggle" id="smttThemeToggle" aria-label="Alternar tema" title="Mudar tema">
                        <span class="smtt-theme-icon smtt-theme-icon-sun"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4V2m0 20v-2m8-8h2M2 12h2m13.66 5.66 1.41 1.41M4.93 4.93l1.41 1.41m11.32-1.41-1.41 1.41M6.34 17.66l-1.41 1.41M12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span class="smtt-theme-icon smtt-theme-icon-moon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 1 0 9.8 9.8z" fill="currentColor"/></svg></span>
                    </button>

                    <?php SMTT_Intranet_Enterprise::home_shortcuts(); ?>

                    <?php echo $this->render_google_apps_panel(); ?>

                    <?php if ($tour_replay_available) : ?>
                        <button
                            type="button"
                            class="smtt-tour-replay smtt-tour-replay--navbar"
                            data-smtt-tour-replay
                            data-tooltip="Rever Tour da Intranet"
                            aria-label="Rever Tour da Intranet V2"
                            title="Rever Tour da Intranet V2"
                        >
                            <span class="smtt-tour-replay-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="m12 3 1.55 3.95L17.5 8.5l-3.95 1.55L12 14l-1.55-3.95L6.5 8.5l3.95-1.55L12 3Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                    <path d="m18.2 14.2.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8.8-2Z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </button>
                    <?php endif; ?>

                    <div class="smtt-modern-user">
                        <span class="smtt-modern-avatar"><?php echo esc_html(strtoupper(substr($smtt_display_name, 0, 1))); ?></span>
                        <div class="smtt-modern-user-copy">
                            <strong><?php echo esc_html($smtt_display_name); ?></strong>
                            <span><?php echo esc_html($today_label); ?></span>
                        </div>
                        <?php if (is_user_logged_in()) : ?>
                            <a href="<?php echo esc_url($smtt_logout_url); ?>" class="smtt-modern-user-action smtt-modern-user-action--logout" title="Sair da conta" aria-label="Sair da conta">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.8v8.1" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M7.2 5.8a8 8 0 1 0 9.6 0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            </a>
                        <?php else : ?>
                            <a href="<?php echo esc_url($smtt_login_url); ?>" class="smtt-modern-user-action smtt-modern-user-action--login" title="Entrar na conta" aria-label="Entrar na conta">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 8l4 4-4 4M14 12H4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <div class="smtt-layout">
                <aside class="smtt-sidebar">
                <div class="smtt-sidebar-section smtt-sidebar-section--nav">
                <div class="smtt-sidebar-title-row">
                    <div class="smtt-sidebar-title">Acessos Rápidos</div>

                    <button type="button" class="smtt-sidebar-toggle" id="smttSidebarToggle" aria-label="Recolher menu lateral" aria-expanded="true">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M15 18l-6-6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </button>
                </div>


                <div class="smtt-sidebar-links">
                            <button type="button" class="smtt-side-action smtt-open-access-center">
                                <span class="smtt-side-action-left">
                                    <?php echo $this->sidebar_icon('star'); ?>
                                    <span>Central de Acessos</span>
                                </span>
                            </button>

                            <div class="smtt-side-accordion">
                                <button type="button" class="smtt-side-accordion-toggle" data-target="#smttSidebarFolders">
                                    <span class="smtt-side-action-left">
                                        <?php echo $this->sidebar_icon('folder'); ?>
                                        <span>Pastas</span>
                                    </span>

                                    <span class="smtt-side-arrow"><?php echo $this->arrow_down_icon(); ?></span>
                                </button>

                                <div class="smtt-side-accordion-content" id="smttSidebarFolders">
                                    <?php if (!empty($access_folders)) : ?>
                                        <?php foreach ($access_folders as $item) : ?>
                                            <?php $path = get_post_meta($item->ID, '_smtt_server_path', true); ?>
                                            <a href="<?php echo esc_url($path ? $path : '#'); ?>" target="_blank" rel="noopener noreferrer" class="smtt-side-subitem">
                                                <?php echo esc_html(get_the_title($item->ID)); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <span class="smtt-side-empty">Nenhuma pasta cadastrada.</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="smtt-side-accordion">
                                <button type="button" class="smtt-side-accordion-toggle" data-target="#smttSidebarSystems">
                                    <span class="smtt-side-action-left">
                                        <?php echo $this->sidebar_icon('system'); ?>
                                        <span>Sistemas</span>
                                    </span>

                                    <span class="smtt-side-arrow"><?php echo $this->arrow_down_icon(); ?></span>
                                </button>

                                <div class="smtt-side-accordion-content" id="smttSidebarSystems">
                                    <?php if (!empty($systems)) : ?>
                                        <?php foreach ($systems as $item) : ?>
                                            <?php $path = get_post_meta($item->ID, '_smtt_access_path', true); ?>
                                            <a href="<?php echo esc_url($path ? $path : '#'); ?>" target="_blank" rel="noopener noreferrer" class="smtt-side-subitem">
                                                <?php echo esc_html(get_the_title($item->ID)); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <span class="smtt-side-empty">Nenhum sistema cadastrado.</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>               
            </div>

                    <div class="smtt-sidebar-section">
                        <div class="smtt-sidebar-title">Navegação</div>
                         <nav class="smtt-sidebar-nav">
                    <a href="#agenda" title="Agenda"><?php echo $this->sidebar_icon('calendar'); ?><span>Agenda</span></a>
                    <a href="#aniversariantes" title="Aniversariantes"><?php echo $this->sidebar_icon('birthday'); ?><span>Aniversariantes</span></a>
                    <a href="#base-interna" title="Wiki SMTT"><?php echo $this->sidebar_icon('knowledge'); ?><span>Wiki SMTT</span></a>
                    <a href="#documentos" title="Documentos"><?php echo $this->sidebar_icon('document'); ?><span>Documentos</span></a>
                    <a href="#mural" title="Mural Interno"><?php echo $this->sidebar_icon('mural'); ?><span>Mural Interno</span></a>
                    <a href="#comunicados" title="Notícias"><?php echo $this->sidebar_icon('news'); ?><span>Notícias</span></a>
                </nav>
                    </div>

    <div class="smtt-sidebar-section">
    <div class="smtt-sidebar-title">Links úteis</div>

    <div class="smtt-sidebar-links">
        <a href="https://aracaju.1doc.com.br/" target="_blank" rel="noopener noreferrer">
            <?php echo $this->link_util_icon('document-check'); ?>
            <span>Aracaju 1Doc</span>
        </a>

        <a href="" target="_blank" rel="noopener noreferrer">
            <?php echo $this->link_util_icon('brain'); ?>
            <span>Aracaju Inteligente</span>
        </a>

        <a href="" target="_blank" rel="noopener noreferrer">
            <?php echo $this->link_util_icon('shopping-cart'); ?>
            <span>Aracaju Compras</span>
        </a>

        <a href="" target="_blank" rel="noopener noreferrer">
            <?php echo $this->link_util_icon('newspaper'); ?>
            <span>D.O Aracaju</span>
        </a>

        <a href="" target="_blank" rel="noopener noreferrer">
            <?php echo $this->link_util_icon('id-card'); ?>
            <span>Portal do Servidor</span>
        </a>

        <a href="" target="_blank" rel="noopener noreferrer">
            <?php echo $this->link_util_icon('settings'); ?>
            <span>SGA Aracaju</span>
        </a>

        <div class="smtt-links-extra" id="smttLinksExtra" style="display:none;">
            <a href="" target="_blank" rel="noopener noreferrer">
                <?php echo $this->link_util_icon('landmark'); ?>
                <span>Portal Prefeitura de Aracaju</span>
            </a>

            <a href="" target="_blank" rel="noopener noreferrer">
                <?php echo $this->link_util_icon('car'); ?>
                <span>Portal Detran</span>
            </a>

            <a href="" target="_blank" rel="noopener noreferrer">
                <?php echo $this->link_util_icon('route'); ?>
                <span>Portal SMTT</span>
            </a>

            <a href="" target="_blank" rel="noopener noreferrer">
                <?php echo $this->link_util_icon('headset'); ?>
                <span>Ouvidoria SMTT</span>
            </a>

            <a href="" target="_blank" rel="noopener noreferrer">
                <?php echo $this->link_util_icon('eye'); ?>
                <span>Portal Transparência</span>
            </a>

            <a href="" target="_blank" rel="noopener noreferrer">
                <?php echo $this->link_util_icon('mail'); ?>
                <span>Webmail</span>
            </a>
        </div>

        <button type="button" class="smtt-links-toggle" id="smttLinksToggle" onclick="
            var el = document.getElementById('smttLinksExtra');
            var btn = document.getElementById('smttLinksToggle');
            var open = el.style.display === 'block';
            el.style.display = open ? 'none' : 'block';
            btn.textContent = open ? 'Mais...' : 'Menos ↑';
        ">Mais...</button> <br>
    </div>
</div>
                </aside>

                <main class="smtt-main" id="inicio">
                    <div class="smtt-main-fixed-top">
                        <section class="smtt-banner smtt-banner--search-hero">
                            <div class="smtt-banner-content smtt-banner-content--search-hero">
                                <div class="smtt-banner-hero-left">
                                    <span class="smtt-banner-badge">BEM-VINDO À INTRANET DA SMTT/ARACAJU</span>
                                    <h2><span id="smttTimeGreeting"><?php echo esc_html($smtt_greeting); ?></span>, <?php echo esc_html($smtt_first_name); ?>!</h2>
                                    <p>Encontre rapidamente tudo o que você precisa.</p>
                                </div>

                                <div class="smtt-banner-hero-center">
                                    <div class="smtt-banner-hero-search" role="search">
                                        <span class="smtt-banner-hero-search-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"></circle><path d="m20 20-3.2-3.2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path></svg>
                                        </span>
                                        <input type="search" id="smttHeroSearchInput" class="smtt-banner-hero-search-input" placeholder="Buscar pessoas, ramais, sistemas, documentos..." autocomplete="off" aria-label="Buscar na intranet">
                                        <button type="button" class="smtt-banner-hero-search-button" id="smttHeroSearchButton" aria-label="Buscar na intranet">
                                            <span>Buscar</span>
                                        </button>
                                    </div>
                                    <small class="smtt-banner-hero-tip">Pesquise mais rápido por documentos, sistemas, pessoas, ramais e outros conteúdos da intranet.</small>
                                    <div class="smtt-hero-search-results" id="smttHeroSearchResults" hidden></div>
                                </div>

                                <div class="smtt-banner-hero-right">
                                    <strong>Mobilidade<br>para uma cidade<br>melhor</strong>
                                    <span class="smtt-banner-hero-line"></span>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="smtt-main-scroll">
                        <div class="smtt-dashboard-grid smtt-home-layout-active">
                            <style id="smtt-home-layout-config"><?php echo SMTT_Intranet_Home_Layout::inline_style(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></style>
                            <?php echo SMTT_Intranet_Custom_Modules::home_html('top'); ?>
                            <section class="smtt-card smtt-access-hub-card" id="central-acessos-card">
                                <div class="smtt-card-header">
                                    <div>
                                        <?php echo SMTT_Intranet_Home_Layout::header('access',$this->card_title_icon('system'),'Central de Acessos','Acessos organizados por setor'); ?>
                                    </div>
                                    <?php if (!empty($access_sectors)) : ?>
                                        <span class="smtt-chip"><?php echo count($access_sectors); ?> setores</span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($access_sectors)) : ?>
                                    <div class="smtt-content-access-grid smtt-sector-access-grid">
                                        <?php foreach ($access_sectors as $sector_item) : ?>
                                            <?php
                                            $sector_id = (int) $sector_item->term_id;
                                            $sector_allowed = $access_is_manager || ($current_user_sector_id && $current_user_sector_id === $sector_id);
                                            $denied_message = class_exists('SMTT_Intranet_Access_Permissions')
                                                ? SMTT_Intranet_Access_Permissions::denied_message($sector_item->name)
                                                : __('Você não possui permissão para acessar este setor.', 'smtt-intranet');
                                            ?>
                                            <button type="button"
                                                    class="smtt-access-mini-card smtt-sector-access-button"
                                                    data-smtt-sector-access
                                                    data-sector-id="<?php echo esc_attr($sector_id); ?>"
                                                    data-sector-name="<?php echo esc_attr($sector_item->name); ?>"
                                                    data-access-allowed="<?php echo $sector_allowed ? '1' : '0'; ?>"
                                                    data-access-message="<?php echo esc_attr($denied_message); ?>">
                                                <span class="smtt-access-mini-icon"><?php echo $this->sidebar_icon_svg('sector'); ?></span>
                                                <strong><?php echo esc_html($this->sector_short_label($sector_item->name)); ?></strong>
                                                <small>Setor</small>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else : ?>
                                    <div class="smtt-empty-state">Nenhum setor cadastrado.</div>
                                <?php endif; ?>
                            </section>

                            <section class="smtt-card smtt-useful-links-card" id="links-uteis-card">
                                <div class="smtt-card-header">
                                    <div>
                                        <?php echo SMTT_Intranet_Home_Layout::header('links',$this->card_title_icon('star'),'Links úteis','Portais e serviços externos de uso frequente'); ?>
                                    </div>
                                </div>

                                <div class="smtt-useful-links-grid">
                                    <?php if (!empty($useful_links)) : ?>
                                        <?php foreach ($useful_links as $link_item) : ?>
                                            <?php
                                            $link_url = get_post_meta($link_item->ID, '_smtt_url', true);
                                            $link_icon = get_post_meta($link_item->ID, '_smtt_icon', true);
                                            $link_new_tab = get_post_meta($link_item->ID, '_smtt_new_tab', true) !== '0';
                                            ?>
                                            <a class="smtt-useful-link-item" href="<?php echo esc_url($link_url ? $link_url : '#'); ?>" <?php if ($link_url && $link_new_tab) : ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>>
                                                <?php echo $this->link_util_icon($link_icon ? $link_icon : 'document-check'); ?>
                                                <span><?php echo esc_html(get_the_title($link_item->ID)); ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <div class="smtt-empty-state">Nenhum link útil cadastrado.</div>
                                    <?php endif; ?>
                                </div>
                            </section>

                            <section class="smtt-card" id="comunicados">
                                <div class="smtt-card-header">
                                    <div>
                                        <?php echo SMTT_Intranet_Home_Layout::header('news',$this->card_title_icon('news'),'Notícias','Últimas publicações do portal institucional'); ?>
                                    </div>
                                </div>

                                <div class="smtt-news-column">
                                    <?php if (!empty($announcements['success']) && !empty($announcements['items'])) : ?>
                                        <?php foreach ($announcements['items'] as $item) : ?>
                                            <?php
                                            $news_remote_image = !empty($item['image']) ? esc_url_raw($item['image']) : '';
                                            $news_image = $news_remote_image !== ''
                                                ? $announcements_module->get_news_image_url($news_remote_image)
                                                : (!empty($item['image_local']) ? esc_url_raw($item['image_local']) : '');
                                            $news_fallback_image = ($news_remote_image !== '' && $news_image !== $news_remote_image)
                                                ? $news_remote_image
                                                : '';
                                            ?>
                                            <article class="smtt-news-card-inline <?php echo $news_image ? 'has-image' : 'no-image'; ?>">
                                                <?php if ($news_image) : ?>
                                                    <div class="smtt-news-thumb-inline">
                                                        <img
                                                            src="<?php echo esc_url($news_image); ?>"
                                                            alt=""
                                                            loading="lazy"
                                                            decoding="async"
                                                            fetchpriority="low"
                                                            data-smtt-news-image
                                                            <?php if ($news_fallback_image) : ?>data-smtt-news-fallback="<?php echo esc_url($news_fallback_image); ?>"<?php endif; ?>
                                                        >
                                                    </div>
                                                <?php endif; ?>

                                                <div class="smtt-news-body-inline">
                                                    <?php if (!empty($item['date'])) : ?>
                                                        <span class="smtt-news-date"><?php echo esc_html(date_i18n('d/m/Y', strtotime($item['date']))); ?></span>
                                                    <?php endif; ?>

                                                    <h4><?php echo esc_html($item['title']); ?></h4>
                                                    <?php
                                                    $news_subtitle = !empty($item['subtitle'])
                                                        ? (string) $item['subtitle']
                                                        : (!empty($item['excerpt']) ? (string) $item['excerpt'] : '');
                                                    ?>
                                                    <?php if ($news_subtitle !== '') : ?>
                                                        <p><?php echo esc_html(wp_trim_words($news_subtitle, 18, '...')); ?></p>
                                                    <?php endif; ?>

                                                    <?php if (!empty($item['link'])) : ?>
                                                        <a href="<?php echo esc_url($item['link']); ?>" target="_blank" rel="noopener noreferrer">Ler no portal</a>
                                                    <?php endif; ?>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <div class="smtt-empty-state">Nenhuma notícia disponível.</div>
                                    <?php endif; ?>
                                </div>
                            </section>

                            <section class="smtt-card smtt-mural-section" id="mural">
    <?php
    $mural_groups = array(
        'banner'            => array(),
        'card'              => array(),
        'topo_deslizante'   => array(),
        'rodape_deslizante' => array(),
        'alerta'            => array(),
    );

    foreach ($mural_items as $mural_item) {
        $display_type = get_post_meta($mural_item->ID, '_smtt_display_type', true);
        $display_type = $display_type ? $display_type : 'card';

        if (!isset($mural_groups[$display_type])) {
            $display_type = 'card';
        }

        $mural_groups[$display_type][] = $mural_item;
    }

    $total_novos = count(array_filter($mural_items, function($item) {
        $created = get_post_time('U', false, $item->ID);
        return (time() - $created) < 86400 * 2;
    }));

    $render_mural_item = function($item, $variant = 'card') {
        $subtitle     = get_post_meta($item->ID, '_smtt_subtitle', true);
        $summary      = get_post_meta($item->ID, '_smtt_summary', true);
        $link_url     = get_post_meta($item->ID, '_smtt_link_url', true);
        $priority     = get_post_meta($item->ID, '_smtt_priority', true);
        $display_type = get_post_meta($item->ID, '_smtt_display_type', true);
        $image_id     = (int) get_post_meta($item->ID, '_smtt_image_id', true);
        $image_url    = $image_id ? wp_get_attachment_image_url($image_id, 'large') : '';
        $image_full   = $image_id ? wp_get_attachment_image_url($image_id, 'full') : '';
        $gallery_raw  = (string) get_post_meta($item->ID, '_smtt_gallery_ids', true);
        $gallery_ids  = array_values(array_unique(array_filter(array_map('intval', preg_split('/\s*,\s*/', $gallery_raw, -1, PREG_SPLIT_NO_EMPTY)))));

        if ($image_id) {
            $gallery_ids = array_values(array_filter($gallery_ids, function($gallery_id) use ($image_id) {
                return (int) $gallery_id !== (int) $image_id;
            }));
        }

        $image_orientation = 'none';
        if ($image_id) {
            $image_meta = wp_get_attachment_metadata($image_id);
            $image_width = !empty($image_meta['width']) ? (int) $image_meta['width'] : 0;
            $image_height = !empty($image_meta['height']) ? (int) $image_meta['height'] : 0;

            if ($image_width > 0 && $image_height > 0) {
                if ($image_height > ($image_width * 1.12)) {
                    $image_orientation = 'portrait';
                } elseif ($image_width > ($image_height * 1.12)) {
                    $image_orientation = 'landscape';
                } else {
                    $image_orientation = 'square';
                }
            }
        }

        $content_html  = apply_filters('the_content', $item->post_content);
        $content_plain = wp_strip_all_tags($content_html);
        $preview_text  = $summary ? $summary : wp_trim_words($content_plain, 22, '...');

        $date_fmt = get_the_date('d/m/Y', $item->ID);
        $is_new   = (time() - get_post_time('U', false, $item->ID)) < 86400 * 2;

        $sector_terms = wp_get_object_terms($item->ID, 'smtt_sector', array('fields' => 'names'));
        $sector_name  = (!empty($sector_terms) && !is_wp_error($sector_terms)) ? $sector_terms[0] : '';

        $category_terms = wp_get_object_terms($item->ID, 'smtt_mural_category', array('fields' => 'names'));
        $category_name  = (!empty($category_terms) && !is_wp_error($category_terms)) ? $category_terms[0] : '';

        $priority_map = array(
            'alta'  => array('label' => 'Urgente', 'class' => 'smtt-mural-priority--alta'),
            'media' => array('label' => 'Aviso',   'class' => 'smtt-mural-priority--media'),
            'baixa' => array('label' => 'Info',    'class' => 'smtt-mural-priority--baixa'),
        );

        $priority_data = isset($priority_map[$priority]) ? $priority_map[$priority] : $priority_map['media'];
        $label         = $category_name ? $category_name : $priority_data['label'];

        $classes = array(
            'smtt-mural-item',
            'smtt-mural-open-item',
            'smtt-mural-item--' . $variant,
            $priority_data['class'],
        );

        if ($is_new) {
            $classes[] = 'smtt-mural-item--new';
        }

        if ($image_orientation !== 'none') {
            $classes[] = 'smtt-mural-media--' . $image_orientation;
        }
        ?>

        <article
            class="<?php echo esc_attr(implode(' ', $classes)); ?>"
            role="button"
            tabindex="0"
            data-title="<?php echo esc_attr(get_the_title($item->ID)); ?>"
            data-subtitle="<?php echo esc_attr($subtitle); ?>"
            data-date="<?php echo esc_attr($date_fmt . ($sector_name ? ' · ' . $sector_name : '')); ?>"
            data-label="<?php echo esc_attr($label); ?>"
            data-image-orientation="<?php echo esc_attr($image_orientation); ?>"
        >
            <?php if ($image_url && $variant === 'banner') : ?>
                <div class="smtt-mural-item-image">
                    <img src="<?php echo esc_url($image_url); ?>" alt="" loading="lazy" decoding="async">
                </div>
            <?php endif; ?>

            <div class="smtt-mural-item-body">
                <div class="smtt-mural-item-top">
                    <span class="smtt-mural-tag"><?php echo esc_html($label); ?></span>

                    <?php if ($is_new) : ?>
                        <span class="smtt-mural-new">Novo</span>
                    <?php endif; ?>
                </div>

                <strong class="smtt-mural-item-title">
                    <?php echo esc_html(get_the_title($item->ID)); ?>
                </strong>

                <?php if ($subtitle) : ?>
                    <span class="smtt-mural-item-subtitle">
                        <?php echo esc_html($subtitle); ?>
                    </span>
                <?php endif; ?>

                <?php if ($preview_text) : ?>
                    <p class="smtt-mural-item-text">
                        <?php echo esc_html($preview_text); ?>
                    </p>
                <?php endif; ?>

                <div class="smtt-mural-item-footer">
                    <span><?php echo esc_html($date_fmt); ?><?php echo $sector_name ? ' · ' . esc_html($sector_name) : ''; ?></span>
                    <small>Ver aviso</small>
                </div>
            </div>

            <div class="smtt-mural-full-content" hidden>
                <div class="smtt-mural-modal-layout smtt-mural-modal-layout--<?php echo esc_attr($image_orientation); ?>">
                    <?php if ($image_url) : ?>
                        <div class="smtt-mural-modal-media">
                            <button
                                type="button"
                                class="smtt-mural-modal-image smtt-mural-lightbox-trigger"
                                data-full-image="<?php echo esc_url($image_full ? $image_full : $image_url); ?>"
                                aria-label="Ampliar imagem principal"
                            >
                                <img src="<?php echo esc_url($image_url); ?>" alt="" loading="lazy" decoding="async">
                            </button>

                            <?php if (!empty($gallery_ids)) : ?>
                                <div class="smtt-mural-modal-gallery" aria-label="Galeria de imagens do aviso">
                                    <?php foreach ($gallery_ids as $gallery_id) : ?>
                                        <?php
                                        $gallery_thumb = wp_get_attachment_image_url($gallery_id, 'thumbnail');
                                        $gallery_full  = wp_get_attachment_image_url($gallery_id, 'full');
                                        if (!$gallery_thumb || !$gallery_full) {
                                            continue;
                                        }
                                        ?>
                                        <button
                                            type="button"
                                            class="smtt-mural-gallery-thumb smtt-mural-lightbox-trigger"
                                            data-full-image="<?php echo esc_url($gallery_full); ?>"
                                            aria-label="Ampliar imagem da galeria"
                                        >
                                            <img src="<?php echo esc_url($gallery_thumb); ?>" alt="" loading="lazy" decoding="async">
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="smtt-mural-modal-copy">
                        <?php if ($subtitle) : ?>
                            <p class="smtt-mural-modal-subtitle-text">
                                <?php echo esc_html($subtitle); ?>
                            </p>
                        <?php endif; ?>

                        <div class="smtt-mural-modal-richtext">
                            <?php echo wp_kses_post($content_html); ?>
                        </div>

                        <?php if ($link_url) : ?>
                            <a class="smtt-mural-modal-link" href="<?php echo esc_url($link_url); ?>" target="_blank" rel="noopener noreferrer">
                                Acessar link relacionado
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </article>

        <?php
    };
    ?>

    <div class="smtt-card-header">
        <div>
            <?php echo SMTT_Intranet_Home_Layout::header('mural',$this->card_title_icon('mural'),'Mural Interno','Avisos e destaques internos'); ?>
        </div>

        <?php if ($total_novos > 0) : ?>
            <span class="smtt-mural-badge">
                <?php echo (int) $total_novos; ?> novo<?php echo $total_novos > 1 ? 's' : ''; ?>
            </span>
        <?php endif; ?>
    </div>

    <?php if (!empty($mural_items)) : ?>

        <?php if (!empty($mural_groups['topo_deslizante'])) : ?>
            <div class="smtt-mural-strip smtt-mural-strip--top">
                <div class="smtt-mural-strip-track">
                    <?php foreach ($mural_groups['topo_deslizante'] as $item) : ?>
                        <?php $render_mural_item($item, 'strip'); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($mural_groups['banner'])) : ?>
            <div class="smtt-mural-feature-area">
                <?php foreach ($mural_groups['banner'] as $item) : ?>
                    <?php $render_mural_item($item, 'banner'); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($mural_groups['alerta'])) : ?>
            <div class="smtt-mural-alert-area">
                <?php foreach ($mural_groups['alerta'] as $item) : ?>
                    <?php $render_mural_item($item, 'alerta'); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($mural_groups['card'])) : ?>
            <div class="smtt-mural-grid">
                <?php foreach ($mural_groups['card'] as $item) : ?>
                    <?php $render_mural_item($item, 'card'); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($mural_groups['rodape_deslizante'])) : ?>
            <div class="smtt-mural-strip smtt-mural-strip--bottom">
                <div class="smtt-mural-strip-track">
                    <?php foreach ($mural_groups['rodape_deslizante'] as $item) : ?>
                        <?php $render_mural_item($item, 'strip'); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php else : ?>
        <div class="smtt-empty-state">Nenhum aviso interno disponível.</div>
    <?php endif; ?>

    <div class="smtt-mural-modal-backdrop" id="smttMuralModalBackdrop" hidden></div>

    <div class="smtt-mural-modal" id="smttMuralModal" role="dialog" aria-modal="true" aria-labelledby="smttMuralModalTitle" hidden>
        <div class="smtt-mural-modal-panel">
            <div class="smtt-mural-modal-header">
                <div>
                    <span class="smtt-mural-modal-label" id="smttMuralModalLabel"></span>
                    <h3 id="smttMuralModalTitle"></h3>
                    <p id="smttMuralModalMeta"></p>
                </div>

                <button type="button" class="smtt-mural-modal-close" id="smttMuralModalClose" aria-label="Fechar aviso">
                    ×
                </button>
            </div>

            <div class="smtt-mural-modal-body" id="smttMuralModalBody"></div>
        </div>
    </div>

    <div class="smtt-mural-lightbox" id="smttMuralLightbox" role="dialog" aria-modal="true" aria-label="Imagem ampliada" hidden>
        <div class="smtt-mural-lightbox-backdrop" data-smtt-mural-lightbox-close></div>
        <div class="smtt-mural-lightbox-stage">
            <button type="button" class="smtt-mural-lightbox-close" id="smttMuralLightboxClose" aria-label="Fechar imagem ampliada">×</button>
            <img id="smttMuralLightboxImage" src="" alt="Imagem ampliada do aviso">
        </div>
    </div>

    <script>
    (function() {
        var modal = document.getElementById('smttMuralModal');
        var backdrop = document.getElementById('smttMuralModalBackdrop');
        var closeBtn = document.getElementById('smttMuralModalClose');
        var title = document.getElementById('smttMuralModalTitle');
        var meta = document.getElementById('smttMuralModalMeta');
        var label = document.getElementById('smttMuralModalLabel');
        var body = document.getElementById('smttMuralModalBody');
        var lightbox = document.getElementById('smttMuralLightbox');
        var lightboxImage = document.getElementById('smttMuralLightboxImage');
        var lightboxClose = document.getElementById('smttMuralLightboxClose');

        if (!modal || !backdrop || !closeBtn || !title || !meta || !label || !body) {
            return;
        }

        function openLightbox(url) {
            if (!lightbox || !lightboxImage || !url) {
                return;
            }

            lightboxImage.src = url;
            lightbox.hidden = false;
            requestAnimationFrame(function() {
                lightbox.classList.add('is-open');
            });
        }

        function closeLightbox() {
            if (!lightbox || !lightboxImage) {
                return;
            }

            lightbox.classList.remove('is-open');
            setTimeout(function() {
                lightbox.hidden = true;
                lightboxImage.removeAttribute('src');
            }, 160);
        }

        function openModal(card) {
            var content = card.querySelector('.smtt-mural-full-content');

            title.textContent = card.getAttribute('data-title') || 'Aviso interno';
            meta.textContent = card.getAttribute('data-date') || '';
            label.textContent = card.getAttribute('data-label') || 'Aviso';

            modal.classList.remove('smtt-mural-modal--portrait', 'smtt-mural-modal--landscape', 'smtt-mural-modal--square', 'smtt-mural-modal--none');
            modal.classList.add('smtt-mural-modal--' + (card.getAttribute('data-image-orientation') || 'none'));

            body.innerHTML = content ? content.innerHTML : '<p>Conteúdo não disponível.</p>';

            modal.hidden = false;
            backdrop.hidden = false;
            document.body.classList.add('smtt-mural-modal-open');

            requestAnimationFrame(function() {
                modal.classList.add('is-open');
                backdrop.classList.add('is-open');
            });
        }

        body.addEventListener('click', function(event) {
            var trigger = event.target.closest('.smtt-mural-lightbox-trigger');
            if (!trigger || !body.contains(trigger)) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            openLightbox(trigger.getAttribute('data-full-image'));
        });

        function closeModal() {
            closeLightbox();
            modal.classList.remove('is-open');
            backdrop.classList.remove('is-open');
            document.body.classList.remove('smtt-mural-modal-open');

            setTimeout(function() {
                modal.hidden = true;
                backdrop.hidden = true;
                body.innerHTML = '';
            }, 180);
        }

        document.querySelectorAll('.smtt-mural-open-item').forEach(function(card) {
            card.addEventListener('click', function() {
                openModal(card);
            });

            card.addEventListener('keydown', function(event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openModal(card);
                }
            });
        });

        closeBtn.addEventListener('click', closeModal);
        backdrop.addEventListener('click', closeModal);

        if (lightboxClose) {
            lightboxClose.addEventListener('click', closeLightbox);
        }

        if (lightbox) {
            lightbox.querySelectorAll('[data-smtt-mural-lightbox-close]').forEach(function(element) {
                element.addEventListener('click', closeLightbox);
            });
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && lightbox && !lightbox.hidden) {
                closeLightbox();
                return;
            }

            if (event.key === 'Escape' && !modal.hidden) {
                closeModal();
            }
        });
    })();
    </script>
</section>

    <section class="smtt-card smtt-agenda-card" id="agenda" data-smtt-agenda-root>
    <?php
    $agenda_today_ts      = current_time('timestamp');
    $agenda_current_year  = (int) date_i18n('Y', $agenda_today_ts);
    $agenda_current_month = (int) date_i18n('m', $agenda_today_ts);
    $agenda_today_day     = (int) date_i18n('j', $agenda_today_ts);

    $cal_year = isset($_GET['smtt_agenda_year'])
        ? absint(wp_unslash($_GET['smtt_agenda_year']))
        : $agenda_current_year;

    $cal_month = isset($_GET['smtt_agenda_month'])
        ? absint(wp_unslash($_GET['smtt_agenda_month']))
        : $agenda_current_month;

    if ($cal_month < 1 || $cal_month > 12) {
        $cal_month = $agenda_current_month;
    }

    if ($cal_year < 2020 || $cal_year > ($agenda_current_year + 5)) {
        $cal_year = $agenda_current_year;
    }

    $year_start_db = sprintf('%04d-01-01', $cal_year);
    $year_end_db   = sprintf('%04d-12-31', $cal_year);
    $agenda_year_data = $this->smtt_get_home_agenda_year_data($cal_year, $year_start_db, $year_end_db);

    $months = array(
        1  => 'Janeiro',
        2  => 'Fevereiro',
        3  => 'Março',
        4  => 'Abril',
        5  => 'Maio',
        6  => 'Junho',
        7  => 'Julho',
        8  => 'Agosto',
        9  => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro',
    );

    $agenda_page_url = get_permalink(get_queried_object_id());
    if (!$agenda_page_url) {
        $agenda_page_url = home_url('/');
    }

    /*
     * v38: todos os 12 meses e os detalhes do ano são pré-renderizados pelo PHP.
     * O JavaScript só alterna painéis já existentes, portanto mês/data abrem sem
     * reload e sem flash. Os links continuam com URL real como fallback sem JS.
     */
    $selected_date       = isset($_GET['smtt_agenda_date']) ? sanitize_text_field(wp_unslash($_GET['smtt_agenda_date'])) : '';
    $selected_day        = 0;
    $selected_day_events = array();

    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $selected_date, $selected_date_parts)) {
        $selected_year_num  = (int) $selected_date_parts[1];
        $selected_month_num = (int) $selected_date_parts[2];
        $selected_day_num   = (int) $selected_date_parts[3];
        $selected_month_events = (!empty($agenda_year_data[$selected_month_num]) && is_array($agenda_year_data[$selected_month_num]))
            ? $agenda_year_data[$selected_month_num]
            : array();

        if (
            $selected_year_num === $cal_year &&
            $selected_month_num === $cal_month &&
            checkdate($selected_month_num, $selected_day_num, $selected_year_num) &&
            !empty($selected_month_events[$selected_day_num]) &&
            is_array($selected_month_events[$selected_day_num])
        ) {
            $selected_day        = $selected_day_num;
            $selected_day_events = $selected_month_events[$selected_day_num];
        }
    }

    $show_agenda_details = $selected_day > 0 && !empty($selected_day_events);
    $selected_count      = $show_agenda_details ? count($selected_day_events) : 0;
    ?>

    <div class="smtt-card-header smtt-agenda-header">
        <div>
            <?php echo SMTT_Intranet_Home_Layout::header('agenda',$this->card_title_icon('calendar'),'Agenda'); ?>

            <form class="smtt-agenda-month-select-form" method="get" action="<?php echo esc_url($agenda_page_url); ?>" data-smtt-agenda-month-form>
                <input type="hidden" name="smtt_agenda_year" value="<?php echo esc_attr($cal_year); ?>">
                <label class="smtt-visually-hidden" for="smttAgendaMonthSelect">Selecionar mês da agenda</label>
                <select
                    id="smttAgendaMonthSelect"
                    class="smtt-agenda-month-select"
                    name="smtt_agenda_month"
                    aria-label="Selecionar mês da agenda"
                    data-smtt-agenda-month-select
                >
                    <?php foreach ($months as $month_number => $month_name) : ?>
                        <option value="<?php echo esc_attr($month_number); ?>" <?php selected($cal_month, $month_number); ?>>
                            <?php echo esc_html($month_name . ' ' . $cal_year); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <noscript><button type="submit" class="smtt-agenda-month-submit">Abrir</button></noscript>
            </form>
        </div>

        <span class="smtt-chip">Hoje, <?php echo esc_html($today_label); ?></span>
    </div>

    <div class="smtt-agenda-stage smtt-agenda-stage--instant" data-smtt-agenda-stage data-year="<?php echo esc_attr($cal_year); ?>">
        <div class="smtt-agenda-screen smtt-agenda-screen-calendar <?php echo $show_agenda_details ? '' : 'is-active'; ?>" data-smtt-agenda-calendar-screen <?php echo $show_agenda_details ? 'hidden' : ''; ?>>
            <?php foreach ($months as $month_number => $month_name) :
                $month_events = (!empty($agenda_year_data[$month_number]) && is_array($agenda_year_data[$month_number]))
                    ? $agenda_year_data[$month_number]
                    : array();
                $first_day_month = mktime(0, 0, 0, $month_number, 1, $cal_year);
                $days_in_month   = (int) date('t', $first_day_month);
                $start_dow       = (int) date('w', $first_day_month);
            ?>
                <div class="smtt-agenda-month-panel" data-smtt-agenda-month-panel="<?php echo esc_attr($month_number); ?>" <?php echo $month_number === $cal_month ? '' : 'hidden'; ?>>
                    <div class="smtt-cal-grid">
                        <?php foreach (array('D', 'S', 'T', 'Q', 'Q', 'S', 'S') as $dl) : ?>
                            <div class="smtt-cal-day-label"><?php echo esc_html($dl); ?></div>
                        <?php endforeach; ?>

                        <?php for ($blank = 0; $blank < $start_dow; $blank++) : ?>
                            <span class="smtt-cal-day smtt-cal-day--empty" aria-hidden="true"></span>
                        <?php endfor; ?>

                        <?php for ($d = 1; $d <= $days_in_month; $d++) :
                            $day_events  = (!empty($month_events[$d]) && is_array($month_events[$d])) ? $month_events[$d] : array();
                            $event_count = count($day_events);
                            $has_event   = $event_count > 0;
                            $date_key    = sprintf('%04d-%02d-%02d', $cal_year, $month_number, $d);
                            $date_label  = sprintf('%02d/%02d/%04d', $d, $month_number, $cal_year);
                            $classes     = 'smtt-cal-day';

                            if (
                                $cal_year === $agenda_current_year &&
                                $month_number === $agenda_current_month &&
                                $d === $agenda_today_day
                            ) {
                                $classes .= ' smtt-cal-day--today';
                            }

                            $classes .= $has_event ? ' smtt-cal-day--has-event' : ' smtt-cal-day--no-event';

                            if ($has_event) :
                                $day_url = add_query_arg(
                                    array(
                                        'smtt_agenda_year'  => $cal_year,
                                        'smtt_agenda_month' => $month_number,
                                        'smtt_agenda_date'  => $date_key,
                                    ),
                                    $agenda_page_url
                                ) . '#agenda';
                                ?>
                                <a
                                    href="<?php echo esc_url($day_url); ?>"
                                    class="<?php echo esc_attr($classes); ?>"
                                    data-smtt-agenda-day
                                    data-date="<?php echo esc_attr($date_key); ?>"
                                    data-date-label="<?php echo esc_attr($date_label); ?>"
                                    data-event-count="<?php echo esc_attr($event_count); ?>"
                                    aria-label="<?php echo esc_attr(sprintf('%d de %s: %d marcação(ões)', $d, $month_name, $event_count)); ?>"
                                >
                                    <span class="smtt-cal-day-number"><?php echo esc_html($d); ?></span>
                                    <span class="smtt-cal-day-count" aria-hidden="true"><?php echo esc_html($event_count); ?></span>
                                </a>
                            <?php else : ?>
                                <span class="<?php echo esc_attr($classes); ?>" aria-disabled="true">
                                    <span class="smtt-cal-day-number"><?php echo esc_html($d); ?></span>
                                </span>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="smtt-agenda-screen smtt-agenda-screen-details <?php echo $show_agenda_details ? 'is-active' : ''; ?>" data-smtt-agenda-details-screen <?php echo $show_agenda_details ? '' : 'hidden'; ?>>
            <div class="smtt-agenda-details-topbar">
                <button
                    type="button"
                    class="smtt-agenda-back-button smtt-agenda-back-button--compact"
                    aria-label="Voltar para agenda"
                    title="Voltar para agenda"
                    data-smtt-agenda-back
                ><span>←</span></button>

                <div>
                    <h4 data-smtt-agenda-detail-title><?php echo $show_agenda_details ? esc_html('Agenda de ' . sprintf('%02d/%02d/%04d', $selected_day, $cal_month, $cal_year)) : 'Agenda'; ?></h4>
                    <p data-smtt-agenda-detail-summary>
                        <?php if ($show_agenda_details) : ?>
                            <?php echo esc_html(sprintf(_n('%d marcação encontrada.', '%d marcações encontradas.', $selected_count, 'smtt-intranet'), $selected_count)); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div data-smtt-agenda-details-container>
                <?php foreach ($months as $month_number => $month_name) :
                    $month_events = (!empty($agenda_year_data[$month_number]) && is_array($agenda_year_data[$month_number]))
                        ? $agenda_year_data[$month_number]
                        : array();
                    foreach ($month_events as $event_day => $day_event_items) :
                        if (empty($day_event_items) || !is_array($day_event_items)) {
                            continue;
                        }
                        $detail_date_key = sprintf('%04d-%02d-%02d', $cal_year, $month_number, (int) $event_day);
                        $detail_is_selected = $show_agenda_details && $detail_date_key === $selected_date;
                    ?>
                        <div class="smtt-cal-events smtt-cal-events-inside" data-smtt-agenda-detail="<?php echo esc_attr($detail_date_key); ?>" <?php echo $detail_is_selected ? '' : 'hidden'; ?>>
                            <?php foreach ($day_event_items as $event_item) :
                                $event_time = !empty($event_item['time']) ? (string) $event_item['time'] : '--:--';
                                if (!empty($event_item['endTime'])) {
                                    $event_time .= ' - ' . (string) $event_item['endTime'];
                                }
                                ?>
                                <article class="smtt-cal-event-item smtt-cal-event-item--detail">
                                    <div class="smtt-cal-event-time"><?php echo esc_html($event_time); ?></div>
                                    <div class="smtt-cal-event-bar"></div>
                                    <div class="smtt-cal-event-content">
                                        <div class="smtt-cal-event-topline">
                                            <div class="smtt-cal-event-name"><?php echo esc_html(!empty($event_item['title']) ? $event_item['title'] : __('Evento interno', 'smtt-intranet')); ?></div>
                                            <span class="smtt-cal-event-status"><?php echo esc_html(!empty($event_item['status']) ? $event_item['status'] : 'agendado'); ?></span>
                                        </div>
                                        <div class="smtt-cal-event-local"><?php echo esc_html(!empty($event_item['local']) ? $event_item['local'] : __('Evento interno', 'smtt-intranet')); ?></div>

                                        <?php if (!empty($event_item['category'])) : ?>
                                            <div class="smtt-cal-event-meta">Categoria: <?php echo esc_html($event_item['category']); ?></div>
                                        <?php endif; ?>

                                        <?php if (!empty($event_item['responsible'])) : ?>
                                            <div class="smtt-cal-event-meta">Responsável: <?php echo esc_html($event_item['responsible']); ?></div>
                                        <?php endif; ?>

                                        <?php if (!empty($event_item['description'])) : ?>
                                            <p class="smtt-cal-event-description"><?php echo esc_html($event_item['description']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

    <section class="smtt-card" id="base-interna">
    <div class="smtt-card-header">
        <div>
            <?php echo SMTT_Intranet_Home_Layout::header('wiki',$this->card_title_icon('knowledge'),'Wiki SMTT','Procedimentos e conhecimento institucional'); ?>
        </div>
        <?php
            $total_kb = $this->smtt_get_cached_home_data(
                'kb_total_count',
                function () {
                    $count = wp_count_posts('smtt_kb_article');
                    return isset($count->publish) ? (int) $count->publish : 0;
                },
                30 * MINUTE_IN_SECONDS
            );
            ?>
            <span class="smtt-chip"><?php echo (int) $total_kb; ?> artigos</span>
    </div>

    <div class="smtt-wiki-search" id="smttWikiSearchBar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35" stroke-linecap="round"/>
        </svg>
        <input type="text" placeholder="Pesquisar procedimentos, normas..." id="smttWikiSearch" autocomplete="off">
    </div>

    <?php
    // Categorias legadas continuam salvas no banco, mas não são mais exigidas nem exibidas na Wiki.
    $all_kb = $this->smtt_get_cached_home_data(
    'kb_articles_home',
    function () {
        return get_posts(array(
            'post_type'              => 'smtt_kb_article',
            'posts_per_page'         => 20,
            'post_status'            => 'publish',
            'meta_key'               => '_smtt_sort_order',
            'orderby'                => 'meta_value_num',
            'order'                  => 'ASC',
            'update_post_meta_cache' => true,
            'update_post_term_cache' => true,
        ));
    },
    30 * MINUTE_IN_SECONDS
);

    $icon_map = array(
        'convivencia'   => array('bg' => '#eff6ff', 'color' => '#2563eb', 'svg' => '<circle cx="12" cy="8" r="4" fill="currentColor"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" fill="currentColor"/>'),
        'procedimentos' => array('bg' => '#f0fdf4', 'color' => '#16a34a', 'svg' => '<path d="M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>'),
        'ti'            => array('bg' => '#fefce8', 'color' => '#ca8a04', 'svg' => '<rect x="2" y="3" width="20" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M8 21h8M12 17v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>'),
        'rh'            => array('bg' => '#fdf4ff', 'color' => '#9333ea', 'svg' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="9" cy="7" r="4" fill="none" stroke="currentColor" stroke-width="2"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>'),
        'juridico'      => array('bg' => '#fff7ed', 'color' => '#ea580c', 'svg' => '<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'),
    );
    $default_icon = array('bg' => '#f1f5f9', 'color' => '#64748b', 'svg' => '<path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>');
    ?>

    <div class="smtt-wiki-list" id="smttWikiList">
        <?php foreach ($all_kb as $item) :
            if (!SMTT_Intranet_Content_Files::can_read($item->ID)) { continue; }
            $summary  = get_post_meta($item->ID, '_smtt_summary', true);
            $views    = (int) get_post_meta($item->ID, '_smtt_views', true);
            $updated  = get_the_modified_date('d/m/Y', $item->ID);
            $kb_terms = get_the_terms($item->ID, 'smtt_kb_category');
            $kb_term  = (!empty($kb_terms) && !is_wp_error($kb_terms)) ? reset($kb_terms) : null;
            $cat_slug = $kb_term ? (string) $kb_term->slug : '';
            $cat_name = $kb_term ? (string) $kb_term->name : '';
            $icon = isset($icon_map[$cat_slug]) ? $icon_map[$cat_slug] : $default_icon;
            $format=(string)get_post_meta($item->ID,'_smtt_content_type',true);
            $link=($format==='arquivo')?SMTT_Intranet_Content_Files::link($item->ID):
                (($format==='link' || ($format==='' && get_post_meta($item->ID,'_smtt_article_url',true)))?
                (string)get_post_meta($item->ID,'_smtt_article_url',true):
                admin_url('admin-post.php?action=smtt_content_read&item='.(int)$item->ID));
            if ($format==='link' && !$link) { $link='#'; }
        ?>
            <a class="smtt-wiki-article" data-cat="<?php echo esc_attr($cat_slug); ?>"
               href="<?php echo esc_url($link ?: '#'); ?>" target="_blank" rel="noopener noreferrer">
                <div class="smtt-wiki-article-icon" style="background:<?php echo esc_attr($icon['bg']); ?>;color:<?php echo esc_attr($icon['color']); ?>">
                    <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><?php echo $icon['svg']; ?></svg>
                </div>
                <div class="smtt-wiki-article-body">
                    <div class="smtt-wiki-article-title"><?php echo esc_html(get_the_title($item->ID)); ?></div>
                    <?php if ($summary) : ?>
                        <div class="smtt-wiki-article-desc"><?php echo esc_html($summary); ?></div>
                    <?php endif; ?>
                    <div class="smtt-wiki-article-meta">
                        Atualizado em <?php echo esc_html($updated); ?>
                    </div>
                </div>
                <?php if ($views) : ?>
                    <div class="smtt-wiki-views"><?php echo number_format($views); ?> leituras</div>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <script>
    (function() {
        var search = document.getElementById('smttWikiSearch');
        var cats   = document.querySelectorAll('.smtt-wiki-cat');
        var items  = document.querySelectorAll('.smtt-wiki-article');
        var activeCat = '';

        function filter() {
            var q = search ? search.value.toLowerCase() : '';
            items.forEach(function(el) {
                var matchCat  = !activeCat || el.getAttribute('data-cat') === activeCat;
                var matchText = !q || el.textContent.toLowerCase().indexOf(q) !== -1;
                el.style.display = (matchCat && matchText) ? '' : 'none';
            });
        }

        cats.forEach(function(btn) {
            btn.addEventListener('click', function() {
                cats.forEach(function(b) { b.classList.remove('smtt-wiki-cat--active'); });
                btn.classList.add('smtt-wiki-cat--active');
                activeCat = btn.getAttribute('data-cat');
                filter();
            });
        });

        if (search) search.addEventListener('input', filter);
    })();
    </script>
</section>

    <section class="smtt-card smtt-birthdays-section" id="aniversariantes">
    <?php
    $birthdays_display_raw = strtolower(sanitize_key(get_option(SMTT_Intranet_Birthdays::OPTION_DISPLAY_MODE, 'cartoes')));

    $birthdays_display_map = array(
        'card'      => 'cartoes',
        'cards'     => 'cartoes',
        'cartao'    => 'cartoes',
        'cartoes'   => 'cartoes',
        'grid'      => 'cartoes',

        'lista'     => 'lista',
        'list'      => 'lista',
        'vertical'  => 'lista',

        'compacto'  => 'compacto',
        'compact'   => 'compacto',
        'avatar'    => 'compacto',
        'avatares'  => 'compacto',
        'carrossel' => 'compacto',
        'scroll'    => 'compacto',
    );

    $birthdays_display_mode = isset($birthdays_display_map[$birthdays_display_raw])
        ? $birthdays_display_map[$birthdays_display_raw]
        : 'cartoes';

    $birthdays_show_photo  = get_option(SMTT_Intranet_Birthdays::OPTION_SHOW_PHOTO, '1') !== '0';
    $birthdays_show_sector = get_option(SMTT_Intranet_Birthdays::OPTION_SHOW_SECTOR, '1') !== '0';
    $birthdays_show_role   = get_option(SMTT_Intranet_Birthdays::OPTION_SHOW_ROLE, '1') !== '0';
    $birthdays_max_items   = absint(get_option(SMTT_Intranet_Birthdays::OPTION_MAX_ITEMS, 30));

    $birthdays_view = $birthdays;
    if ($birthdays_max_items > 0) {
        $birthdays_view = array_slice($birthdays_view, 0, $birthdays_max_items);
    }

    $today_md = current_time('m-d');
    $hoje = array_values(array_filter($birthdays_all, function($item) use ($today_md) {
        $d = get_post_meta($item->ID, '_smtt_birth_date', true);
        return $d ? date('m-d', strtotime($d)) === $today_md : false;
    }));
    $total_hoje = count($hoje);

    $birthday_timeline_today = array();
    $birthday_timeline_upcoming = array();
    $birthday_timeline_recent = array();

    foreach ($birthdays_all as $timeline_item) {
        $timeline_date = get_post_meta($timeline_item->ID, '_smtt_birth_date', true);
        if (!$timeline_date || !strtotime($timeline_date)) {
            continue;
        }
        $timeline_md = date('m-d', strtotime($timeline_date));
        if ($timeline_md === $today_md) {
            $birthday_timeline_today[] = $timeline_item;
        } elseif ($timeline_md > $today_md) {
            $birthday_timeline_upcoming[] = $timeline_item;
        } else {
            $birthday_timeline_recent[] = $timeline_item;
        }
    }

    usort($birthday_timeline_upcoming, function($a, $b) {
        return strcmp(date('m-d', strtotime(get_post_meta($a->ID, '_smtt_birth_date', true))), date('m-d', strtotime(get_post_meta($b->ID, '_smtt_birth_date', true))));
    });
    usort($birthday_timeline_recent, function($a, $b) {
        return strcmp(date('m-d', strtotime(get_post_meta($b->ID, '_smtt_birth_date', true))), date('m-d', strtotime(get_post_meta($a->ID, '_smtt_birth_date', true))));
    });

    // Resumo do card: exibe somente os próximos aniversariantes do mês vigente.
    // Aniversariantes de hoje ficam destacados pela estrela/barra inferior e não
    // são duplicados nesta lista. Não avança para o mês seguinte e não completa
    // com aniversários que já passaram.
    $current_month = current_time('m');
    $current_day   = (int) current_time('d');
    $birthdays_current_month_upcoming = array();

    foreach ($birthdays_all as $card_item) {
        $card_birth_date = get_post_meta($card_item->ID, '_smtt_birth_date', true);
        if (!$card_birth_date || !strtotime($card_birth_date)) {
            continue;
        }

        $card_month = date('m', strtotime($card_birth_date));
        $card_day   = (int) date('d', strtotime($card_birth_date));

        if ($card_month === $current_month && $card_day > $current_day) {
            $birthdays_current_month_upcoming[] = $card_item;
        }
    }

    usort($birthdays_current_month_upcoming, function($a, $b) {
        $a_date = get_post_meta($a->ID, '_smtt_birth_date', true);
        $b_date = get_post_meta($b->ID, '_smtt_birth_date', true);
        return strcmp(date('m-d', strtotime($a_date)), date('m-d', strtotime($b_date)));
    });

    $birthdays_card_items = array_slice($birthdays_current_month_upcoming, 0, 3);
    ?>

    <?php
    $avatar_colors = array(
        array('bg' => '#fef3c7', 'color' => '#92400e'),
        array('bg' => '#ede9fe', 'color' => '#5b21b6'),
        array('bg' => '#dcfce7', 'color' => '#15803d'),
        array('bg' => '#e0f2fe', 'color' => '#0369a1'),
        array('bg' => '#fce7f3', 'color' => '#9d174d'),
        array('bg' => '#fff7ed', 'color' => '#c2410c'),
        array('bg' => '#f0fdf4', 'color' => '#166534'),
        array('bg' => '#fdf2f8', 'color' => '#86198f'),
    );
    ?>

    <div class="smtt-card-header">
        <div>
            <?php echo SMTT_Intranet_Home_Layout::header('birthdays',$this->card_title_icon('birthday'),'Aniversariantes','Lista geral de colaboradores'); ?>
        </div>

        <div class="smtt-bday-header-actions">
    <?php if (!empty($birthdays_all)) : ?>
                <span class="smtt-chip">
                    <?php echo count($birthdays_all); ?> colaborador<?php echo count($birthdays_all) !== 1 ? 'es' : ''; ?>
                </span>
                <?php if (!empty($hoje)) : ?>
                    <?php if ($total_hoje === 1) : ?>
                        <?php
                        $today_focus      = $hoje[0];
                        $today_focus_name = get_the_title($today_focus->ID);
                        $today_focus_date = get_post_meta($today_focus->ID, '_smtt_birth_date', true);
                        $today_focus_role = get_post_meta($today_focus->ID, '_smtt_role', true);
                        $today_focus_photo_id  = get_post_meta($today_focus->ID, '_smtt_photo_id', true);
                        $today_focus_photo_url = $today_focus_photo_id ? wp_get_attachment_image_url((int) $today_focus_photo_id, 'thumbnail') : '';
                        $today_focus_initial   = function_exists('mb_substr') ? mb_strtoupper(mb_substr($today_focus_name, 0, 1)) : strtoupper(substr($today_focus_name, 0, 1));
                        $today_focus_color     = $avatar_colors[0];
                        $today_focus_short     = $today_focus_date ? date_i18n('d/m', strtotime($today_focus_date)) : '';
                        $today_focus_full      = $today_focus_short;
                        $today_focus_terms = wp_get_object_terms($today_focus->ID, 'smtt_sector', array('fields' => 'names'));
                        $today_focus_sector = (!empty($today_focus_terms) && !is_wp_error($today_focus_terms)) ? $today_focus_terms[0] : '';
                        ?>
                        <button
                            type="button"
                            class="smtt-bday-today-signal smtt-bday-modal-trigger"
                            data-name="<?php echo esc_attr($today_focus_name); ?>"
                            data-date="<?php echo esc_attr($today_focus_short); ?>"
                            data-date-full="<?php echo esc_attr($today_focus_full); ?>"
                            data-sector="<?php echo esc_attr($today_focus_sector); ?>"
                            data-role="<?php echo esc_attr($today_focus_role); ?>"
                            data-photo="<?php echo esc_url($birthdays_show_photo ? $today_focus_photo_url : ''); ?>"
                            data-initial="<?php echo esc_attr($today_focus_initial); ?>"
                            data-color-bg="<?php echo esc_attr($today_focus_color['bg']); ?>"
                            data-color-text="<?php echo esc_attr($today_focus_color['color']); ?>"
                            data-is-today="1"
                            aria-label="Abrir aniversariante de hoje: <?php echo esc_attr($today_focus_name); ?>"
                            title="Aniversariante de hoje"
                        >
                            <span class="smtt-bday-today-signal-star" aria-hidden="true">★</span>
                        </button>
                    <?php else : ?>
                        <div class="smtt-bday-today-signal-wrap">
                            <button
                                type="button"
                                class="smtt-bday-today-signal smtt-bday-today-menu-toggle"
                                aria-expanded="false"
                                aria-controls="smttBdayTodayMenu"
                                title="<?php echo esc_attr($total_hoje); ?> aniversariantes hoje"
                            >
                                <span class="smtt-bday-today-signal-star" aria-hidden="true">★</span>
                                <span class="smtt-bday-today-signal-count"><?php echo esc_html($total_hoje); ?></span>
                            </button>
                            <div class="smtt-bday-today-menu" id="smttBdayTodayMenu" hidden>
                                <div class="smtt-bday-today-menu-title">Aniversariantes de hoje</div>
                                <?php foreach ($hoje as $today_menu_index => $today_menu_item) : ?>
                                    <?php
                                    $today_menu_name = get_the_title($today_menu_item->ID);
                                    $today_menu_date = get_post_meta($today_menu_item->ID, '_smtt_birth_date', true);
                                    $today_menu_role = get_post_meta($today_menu_item->ID, '_smtt_role', true);
                                    $today_menu_photo_id = get_post_meta($today_menu_item->ID, '_smtt_photo_id', true);
                                    $today_menu_photo_url = $today_menu_photo_id ? wp_get_attachment_image_url((int) $today_menu_photo_id, 'thumbnail') : '';
                                    $today_menu_initial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($today_menu_name, 0, 1)) : strtoupper(substr($today_menu_name, 0, 1));
                                    $today_menu_color = $avatar_colors[$today_menu_index % count($avatar_colors)];
                                    $today_menu_short = $today_menu_date ? date_i18n('d/m', strtotime($today_menu_date)) : '';
                                    $today_menu_full = $today_menu_short;
                                    $today_menu_terms = wp_get_object_terms($today_menu_item->ID, 'smtt_sector', array('fields' => 'names'));
                                    $today_menu_sector = (!empty($today_menu_terms) && !is_wp_error($today_menu_terms)) ? $today_menu_terms[0] : '';
                                    ?>
                                    <button
                                        type="button"
                                        class="smtt-bday-today-menu-item smtt-bday-modal-trigger"
                                        data-name="<?php echo esc_attr($today_menu_name); ?>"
                                        data-date="<?php echo esc_attr($today_menu_short); ?>"
                                        data-date-full="<?php echo esc_attr($today_menu_full); ?>"
                                        data-sector="<?php echo esc_attr($today_menu_sector); ?>"
                                        data-role="<?php echo esc_attr($today_menu_role); ?>"
                                        data-photo="<?php echo esc_url($birthdays_show_photo ? $today_menu_photo_url : ''); ?>"
                                        data-initial="<?php echo esc_attr($today_menu_initial); ?>"
                                        data-color-bg="<?php echo esc_attr($today_menu_color['bg']); ?>"
                                        data-color-text="<?php echo esc_attr($today_menu_color['color']); ?>"
                                        data-is-today="1"
                                    >
                                        <span class="smtt-bday-today-menu-avatar" style="background:<?php echo esc_attr($today_menu_color['bg']); ?>;color:<?php echo esc_attr($today_menu_color['color']); ?>"><?php echo esc_html($today_menu_initial); ?></span>
                                        <span><strong><?php echo esc_html($today_menu_name); ?></strong><?php if ($today_menu_sector) : ?><small><?php echo esc_html($today_menu_sector); ?></small><?php endif; ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <button type="button" class="smtt-bday-timeline-open" id="smttBdayTimelineOpen">Lista completa</button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($birthdays_all)) : ?>

         <div class="smtt-bday-list smtt-bday-list--<?php echo esc_attr($birthdays_display_mode); ?>">
            <?php if (empty($birthdays_card_items)) : ?>
                <div class="smtt-bday-month-empty">Nenhum próximo aniversariante neste mês.</div>
            <?php else : ?>
            <?php
            foreach ($birthdays_card_items as $i => $item) :
                $name         = get_the_title($item->ID);
                $birth_date   = get_post_meta($item->ID, '_smtt_birth_date', true);
                $role         = get_post_meta($item->ID, '_smtt_role', true);
                $photo_id     = get_post_meta($item->ID, '_smtt_photo_id', true);
                $photo_url    = $photo_id ? wp_get_attachment_image_url((int) $photo_id, 'thumbnail') : '';
                $is_today     = $birth_date && date('m-d', strtotime($birth_date)) === $today_md;
                $initials     = function_exists('mb_substr') ? mb_strtoupper(mb_substr($name, 0, 1)) : strtoupper(substr($name, 0, 1));
                $color        = $avatar_colors[$i % count($avatar_colors)];

                $sector_terms = wp_get_object_terms($item->ID, 'smtt_sector', array('fields' => 'names'));
                $sector_name  = (!empty($sector_terms) && !is_wp_error($sector_terms)) ? $sector_terms[0] : '';

                $birth_short = $birth_date ? date_i18n('d/m', strtotime($birth_date)) : '';
                $birth_full  = $birth_short;
            ?>
                <button
                    type="button"
                    class="smtt-bday-person <?php echo $is_today ? 'smtt-bday-person--today' : ''; ?>"
                    data-name="<?php echo esc_attr($name); ?>"
                    data-date="<?php echo esc_attr($birth_short); ?>"
                    data-date-full="<?php echo esc_attr($birth_full); ?>"
                    data-sector="<?php echo esc_attr($sector_name); ?>"
                    data-role="<?php echo esc_attr($role); ?>"
                    data-photo="<?php echo esc_url($birthdays_show_photo ? $photo_url : ''); ?>"
                    data-initial="<?php echo esc_attr($initials); ?>"
                    data-color-bg="<?php echo esc_attr($color['bg']); ?>"
                    data-color-text="<?php echo esc_attr($color['color']); ?>"
                    data-is-today="<?php echo $is_today ? '1' : '0'; ?>"
                    aria-label="Ver informações de <?php echo esc_attr($name); ?>"
                >
                    <span
                        class="smtt-bday-avatar <?php echo $is_today ? 'smtt-bday-avatar--today' : ''; ?>"
                        style="background:<?php echo esc_attr($color['bg']); ?>;color:<?php echo esc_attr($color['color']); ?>"
                    >
                        <?php if ($birthdays_show_photo && $photo_url) : ?>
                            <img src="<?php echo esc_url($photo_url); ?>" alt="" loading="lazy" decoding="async">
                        <?php else : ?>
                            <?php echo esc_html($initials); ?>
                        <?php endif; ?>
                    </span>

                    <span class="smtt-bday-info">
                        <span class="smtt-bday-inline">
                            <strong><?php echo esc_html($name); ?></strong>
                            <?php if ($birthdays_show_sector && $sector_name) : ?>
                                <span class="smtt-bday-separator">—</span>
                                <span class="smtt-bday-inline-meta"><?php echo esc_html($sector_name); ?></span>
                            <?php endif; ?>
                            <?php if ($birthdays_show_role && $role) : ?>
                                <span class="smtt-bday-separator">—</span>
                                <span class="smtt-bday-inline-meta"><?php echo esc_html($role); ?></span>
                            <?php endif; ?>
                            <?php if ($birth_short) : ?>
                                <span class="smtt-bday-separator">•</span>
                                <span class="smtt-bday-date"><?php echo esc_html($birth_short); ?></span>
                            <?php endif; ?>
                            <?php if ($is_today) : ?>
                                <span class="smtt-bday-badge-today">🎉 Hoje</span>
                            <?php endif; ?>
                        </span>
                    </span>

                    <span class="smtt-bday-open-label">Ver</span>
                </button>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

<?php if (!empty($hoje)) : ?>
    <div class="smtt-bday-footerbar" role="region" aria-label="Aniversariantes de hoje">
        <span class="smtt-bday-footerbar-icon" aria-hidden="true"><?php echo $this->card_title_icon('birthday'); ?></span>
        <span class="smtt-bday-footerbar-label">Aniversário hoje:</span>
        <span class="smtt-bday-footerbar-names">
            <?php foreach ($hoje as $today_bar_index => $today_bar_item) : ?>
                <?php
                $today_bar_name = get_the_title($today_bar_item->ID);
                $today_bar_date = get_post_meta($today_bar_item->ID, '_smtt_birth_date', true);
                $today_bar_role = get_post_meta($today_bar_item->ID, '_smtt_role', true);
                $today_bar_photo_id = get_post_meta($today_bar_item->ID, '_smtt_photo_id', true);
                $today_bar_photo_url = $today_bar_photo_id ? wp_get_attachment_image_url((int) $today_bar_photo_id, 'thumbnail') : '';
                $today_bar_initial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($today_bar_name, 0, 1)) : strtoupper(substr($today_bar_name, 0, 1));
                $today_bar_color = $avatar_colors[$today_bar_index % count($avatar_colors)];
                $today_bar_short = $today_bar_date ? date_i18n('d/m', strtotime($today_bar_date)) : '';
                $today_bar_full = $today_bar_short;
                $today_bar_terms = wp_get_object_terms($today_bar_item->ID, 'smtt_sector', array('fields' => 'names'));
                $today_bar_sector = (!empty($today_bar_terms) && !is_wp_error($today_bar_terms)) ? $today_bar_terms[0] : '';
                ?>
                <?php if ($today_bar_index > 0) : ?><span class="smtt-bday-footerbar-separator" aria-hidden="true">•</span><?php endif; ?>
                <button
                    type="button"
                    class="smtt-bday-footerbar-name smtt-bday-modal-trigger"
                    data-name="<?php echo esc_attr($today_bar_name); ?>"
                    data-date="<?php echo esc_attr($today_bar_short); ?>"
                    data-date-full="<?php echo esc_attr($today_bar_full); ?>"
                    data-sector="<?php echo esc_attr($today_bar_sector); ?>"
                    data-role="<?php echo esc_attr($today_bar_role); ?>"
                    data-photo="<?php echo esc_url($birthdays_show_photo ? $today_bar_photo_url : ''); ?>"
                    data-initial="<?php echo esc_attr($today_bar_initial); ?>"
                    data-color-bg="<?php echo esc_attr($today_bar_color['bg']); ?>"
                    data-color-text="<?php echo esc_attr($today_bar_color['color']); ?>"
                    data-is-today="1"
                    aria-label="Abrir aniversariante <?php echo esc_attr($today_bar_name); ?>"
                ><?php echo esc_html($today_bar_name); ?></button>
            <?php endforeach; ?>
        </span>
        <span class="smtt-bday-footerbar-pill"><?php echo esc_html($total_hoje); ?> hoje</span>
    </div>
    <script>document.body.classList.add('smtt-has-bday-footer');</script>
<?php endif; ?>
        <div class="smtt-bday-timeline-backdrop" id="smttBdayTimelineBackdrop" hidden></div>
        <div class="smtt-bday-timeline-modal" id="smttBdayTimelineModal" role="dialog" aria-modal="true" aria-labelledby="smttBdayTimelineTitle" hidden>
            <div class="smtt-bday-timeline-panel">
                <div class="smtt-bday-timeline-head">
                    <div>
                        <span class="smtt-bday-modal-kicker">Calendário de aniversários</span>
                        <h3 id="smttBdayTimelineTitle">Aniversariantes</h3>
                        <p>Veja quem comemora hoje, os próximos aniversários e os mais recentes.</p>
                    </div>
                    <button type="button" class="smtt-bday-timeline-close" id="smttBdayTimelineClose" aria-label="Fechar">×</button>
                </div>

                <div class="smtt-bday-timeline-scroll">
                    <?php
                    $timeline_groups = array(
                        array('class' => 'is-today', 'label' => 'Hoje', 'items' => $birthday_timeline_today),
                        array('class' => 'is-upcoming', 'label' => 'Próximos', 'items' => $birthday_timeline_upcoming),
                        array('class' => 'is-recent', 'label' => 'Recentes', 'items' => $birthday_timeline_recent),
                    );
                    foreach ($timeline_groups as $timeline_group) :
                    ?>
                        <section class="smtt-bday-timeline-section <?php echo esc_attr($timeline_group['class']); ?>">
                            <div class="smtt-bday-timeline-section-title">
                                <strong><?php echo esc_html($timeline_group['label']); ?></strong>
                                <span><?php echo count($timeline_group['items']); ?></span>
                            </div>

                            <?php if (!empty($timeline_group['items'])) : ?>
                                <div class="smtt-bday-timeline-list">
                                    <?php foreach ($timeline_group['items'] as $timeline_index => $timeline_item) :
                                        $timeline_name = get_the_title($timeline_item->ID);
                                        $timeline_birth_date = get_post_meta($timeline_item->ID, '_smtt_birth_date', true);
                                        $timeline_role = get_post_meta($timeline_item->ID, '_smtt_role', true);
                                        $timeline_sector = $this->smtt_get_first_term_name($timeline_item->ID, 'smtt_sector');
                                        $timeline_short = $timeline_birth_date ? date_i18n('d/m', strtotime($timeline_birth_date)) : '';
                                    ?>
                                        <div class="smtt-bday-timeline-item">
                                            <span class="smtt-bday-timeline-marker"></span>
                                            <div class="smtt-bday-timeline-copy">
                                                <strong><?php echo esc_html($timeline_name); ?></strong>
                                                <span>
                                                    <?php echo esc_html($timeline_sector); ?>
                                                    <?php if ($timeline_sector && $timeline_role) : ?> · <?php endif; ?>
                                                    <?php echo esc_html($timeline_role); ?>
                                                </span>
                                            </div>
                                            <time><?php echo esc_html($timeline_short); ?></time>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <div class="smtt-bday-timeline-empty">Nenhum aniversariante nesta faixa.</div>
                            <?php endif; ?>
                        </section>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="smtt-bday-modal-backdrop" id="smttBdayModalBackdrop" hidden></div>

        <div class="smtt-bday-modal" id="smttBdayModal" role="dialog" aria-modal="true" aria-labelledby="smttBdayModalName" hidden>
            <div class="smtt-bday-modal-panel">
                <button type="button" class="smtt-bday-modal-close" id="smttBdayModalClose" aria-label="Fechar">
                    ×
                </button>

                <div class="smtt-bday-modal-avatar" id="smttBdayModalAvatar"></div>

                <div class="smtt-bday-modal-content">
                    <span class="smtt-bday-modal-kicker">Aniversariante</span>
                    <h3 id="smttBdayModalName"></h3>

                    <div class="smtt-bday-modal-details">
                        <div class="smtt-bday-modal-row">
                            <span>Data</span>
                            <strong id="smttBdayModalDate"></strong>
                        </div>

                        <div class="smtt-bday-modal-row" id="smttBdayModalRoleRow">
                            <span>Cargo/Função</span>
                            <strong id="smttBdayModalRole"></strong>
                        </div>

                        <div class="smtt-bday-modal-row" id="smttBdayModalSectorRow">
                            <span>Setor</span>
                            <strong id="smttBdayModalSector"></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        (function() {
            var buttons = document.querySelectorAll('.smtt-bday-person, .smtt-bday-modal-trigger');
            var modal = document.getElementById('smttBdayModal');
            var backdrop = document.getElementById('smttBdayModalBackdrop');
            var closeBtn = document.getElementById('smttBdayModalClose');
            var avatar = document.getElementById('smttBdayModalAvatar');
            var nameEl = document.getElementById('smttBdayModalName');
            var dateEl = document.getElementById('smttBdayModalDate');
            var roleEl = document.getElementById('smttBdayModalRole');
            var sectorEl = document.getElementById('smttBdayModalSector');
            var roleRow = document.getElementById('smttBdayModalRoleRow');
            var sectorRow = document.getElementById('smttBdayModalSectorRow');
            var todayMenuToggle = document.querySelector('.smtt-bday-today-menu-toggle');
            var todayMenu = document.getElementById('smttBdayTodayMenu');

            function closeTodayMenu() {
                if (!todayMenu || !todayMenuToggle) return;
                todayMenu.hidden = true;
                todayMenuToggle.setAttribute('aria-expanded', 'false');
            }

            if (todayMenuToggle && todayMenu) {
                todayMenuToggle.addEventListener('click', function(event) {
                    event.stopPropagation();
                    var willOpen = todayMenu.hidden;
                    todayMenu.hidden = !willOpen;
                    todayMenuToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                });

                document.addEventListener('click', function(event) {
                    if (!todayMenu.hidden && !todayMenu.contains(event.target) && !todayMenuToggle.contains(event.target)) {
                        closeTodayMenu();
                    }
                });
            }

            if (!buttons.length || !modal || !backdrop) {
                return;
            }

            function fillRow(row, field, value) {
                if (!row || !field) return;

                if (value) {
                    row.hidden = false;
                    field.textContent = value;
                } else {
                    row.hidden = true;
                    field.textContent = '';
                }
            }

            function launchBirthdayConfetti() {
                var layer = document.createElement('div');
                layer.className = 'smtt-bday-confetti-layer';
                document.body.appendChild(layer);

                var colors = ['#f59e0b', '#10a5cd', '#42b36d', '#7b5bb8', '#ef4444', '#f4c300', '#22c55e'];

                for (var i = 0; i < 80; i++) {
                    var piece = document.createElement('span');

                    piece.className = 'smtt-bday-confetti-piece';
                    piece.style.left = Math.random() * 100 + 'vw';
                    piece.style.background = colors[Math.floor(Math.random() * colors.length)];
                    piece.style.animationDelay = Math.random() * 0.7 + 's';
                    piece.style.animationDuration = (2.2 + Math.random() * 1.4) + 's';
                    piece.style.transform = 'rotate(' + (Math.random() * 360) + 'deg)';

                    layer.appendChild(piece);
                }

                setTimeout(function() {
                    layer.remove();
                }, 4600);
            }

            function openModal(button) {
                closeTodayMenu();
                var photo = button.getAttribute('data-photo') || '';
                var initial = button.getAttribute('data-initial') || 'A';

                avatar.innerHTML = '';
                avatar.style.background = button.getAttribute('data-color-bg') || '#fef3c7';
                avatar.style.color = button.getAttribute('data-color-text') || '#92400e';

                if (photo) {
                    var img = document.createElement('img');
                    img.src = photo;
                    img.alt = '';
                    avatar.appendChild(img);
                } else {
                    avatar.textContent = initial;
                }

                nameEl.textContent = button.getAttribute('data-name') || '';
                dateEl.textContent = button.getAttribute('data-date') || button.getAttribute('data-date-full') || '';

                fillRow(roleRow, roleEl, button.getAttribute('data-role') || '');
                fillRow(sectorRow, sectorEl, button.getAttribute('data-sector') || '');

                modal.hidden = false;
                backdrop.hidden = false;

                requestAnimationFrame(function() {
                    modal.classList.add('is-open');
                    backdrop.classList.add('is-open');
                    document.body.classList.add('smtt-bday-modal-open');

                    if (button.getAttribute('data-is-today') === '1') {
                        launchBirthdayConfetti();
                    }
                });
            }

            function closeModal() {
                modal.classList.remove('is-open');
                backdrop.classList.remove('is-open');
                document.body.classList.remove('smtt-bday-modal-open');

                setTimeout(function() {
                    modal.hidden = true;
                    backdrop.hidden = true;
                }, 180);
            }

            buttons.forEach(function(button) {
                button.addEventListener('click', function() {
                    openModal(button);
                });
            });

            closeBtn.addEventListener('click', closeModal);
            backdrop.addEventListener('click', closeModal);

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeTodayMenu();
                    if (!modal.hidden) {
                        closeModal();
                    }
                }
            });
        })();
        </script>
    <?php else : ?>
        <div class="smtt-empty-state">Nenhum aniversariante cadastrado.</div>
    <?php endif; ?>
</section>
                            <?php // Os catálogos são cards da MESMA grade e respeitam a ordem publicada. ?>
                            <?php echo SMTT_Intranet_Custom_Modules::home_html('bottom'); ?>
                        </div>

                        <div class="smtt-access-sector-backdrop" id="smttSectorAccessBackdrop" hidden></div>
                        <section class="smtt-access-sector-modal" id="smttSectorAccessModal" role="dialog" aria-modal="true" aria-labelledby="smttSectorAccessTitle" hidden>
                            <div class="smtt-access-sector-modal-head">
                                <div>
                                    <span class="smtt-command-kicker">Central de Acessos</span>
                                    <h2 id="smttSectorAccessTitle">Acessos do setor</h2>
                                    <p id="smttSectorAccessSubtitle">Sistemas e pastas disponíveis para o seu setor.</p>
                                </div>
                                <button type="button" class="smtt-access-sector-modal-close" id="smttSectorAccessClose" aria-label="Fechar">×</button>
                            </div>
                            <div class="smtt-access-sector-modal-body" id="smttSectorAccessBody">
                                <?php foreach ($access_sectors as $sector_item) : ?>
                                    <?php
                                    $sector_id = (int) $sector_item->term_id;
                                    $sector_allowed = $access_is_manager || ($current_user_sector_id && $current_user_sector_id === $sector_id);
                                    if (!$sector_allowed) { continue; }
                                    $sector_systems = array_values(array_filter($center_systems, function($access_item) use ($sector_id) {
                                        return has_term($sector_id, 'smtt_sector', $access_item->ID);
                                    }));
                                    $sector_folders = array_values(array_filter($center_folders, function($access_item) use ($sector_id) {
                                        return has_term($sector_id, 'smtt_sector', $access_item->ID);
                                    }));
                                    ?>
                                    <div class="smtt-access-sector-panel" data-sector-panel="<?php echo esc_attr($sector_id); ?>" hidden>
                                        <div class="smtt-access-sector-columns">
                                            <div class="smtt-access-sector-column">
                                                <h3>Sistemas</h3>
                                                <div class="smtt-access-sector-list">
                                                    <?php if (!empty($sector_systems)) : ?>
                                                        <?php foreach ($sector_systems as $system_item) : ?>
                                                            <?php $system_url = get_post_meta($system_item->ID, '_smtt_access_path', true); ?>
                                                            <?php echo $this->render_access_mini_button(get_the_title($system_item->ID), 'system', $system_url, 'Sistema', get_post_meta($system_item->ID, '_smtt_icon', true)); ?>
                                                        <?php endforeach; ?>
                                                    <?php else : ?>
                                                        <div class="smtt-empty-state">Nenhum sistema vinculado a este setor.</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="smtt-access-sector-column">
                                                <h3>Pastas</h3>
                                                <div class="smtt-access-sector-list">
                                                    <?php if (!empty($sector_folders)) : ?>
                                                        <?php foreach ($sector_folders as $folder_item) : ?>
                                                            <?php $folder_url = get_post_meta($folder_item->ID, '_smtt_server_path', true); ?>
                                                            <?php echo $this->render_access_mini_button(get_the_title($folder_item->ID), 'folder', $folder_url, 'Pasta', get_post_meta($folder_item->ID, '_smtt_icon', true)); ?>
                                                        <?php endforeach; ?>
                                                    <?php else : ?>
                                                        <div class="smtt-empty-state">Nenhuma pasta vinculada a este setor.</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <div class="smtt-access-toast" id="smttAccessToast" role="status" aria-live="polite" hidden></div>
                    </div>
                </main>
            </div>



        </div>
        <?php

        return ob_get_clean();
    }

    private function render_google_apps_panel()
    {
        ob_start();
        ?>
        <div class="smtt-topbar-apps">
            <button type="button" class="smtt-apps-toggle" id="smttAppsToggle" aria-expanded="false" aria-controls="smttAppsPanel" aria-label="Abrir Menu de Aplicativos" title="Menu de Aplicativos">
                <svg viewBox="0 0 24 24" aria-hidden="true" class="smtt-apps-toggle-icon">
                    <circle cx="5" cy="5" r="1.6"></circle>
                    <circle cx="12" cy="5" r="1.6"></circle>
                    <circle cx="19" cy="5" r="1.6"></circle>
                    <circle cx="5" cy="12" r="1.6"></circle>
                    <circle cx="12" cy="12" r="1.6"></circle>
                    <circle cx="19" cy="12" r="1.6"></circle>
                    <circle cx="5" cy="19" r="1.6"></circle>
                    <circle cx="12" cy="19" r="1.6"></circle>
                    <circle cx="19" cy="19" r="1.6"></circle>
                </svg>
            </button>

            <div class="smtt-apps-panel" id="smttAppsPanel">
                <?php $smtt_menu_apps=SMTT_Intranet_App_Launcher::for_home(); foreach ($smtt_menu_apps as $app): ?>
                    <?php
                    $ico = (string)($app['icon']??'');
                    $builtin = array(
                        'builtin:google'=>'icon_google','builtin:gmail'=>'icon_gmail','builtin:drive'=>'icon_drive',
                        'builtin:docs'=>'icon_docs','builtin:sheets'=>'icon_sheets','builtin:slides'=>'icon_slides',
                        'builtin:translate'=>'icon_translate','builtin:wikipedia'=>'icon_wikipedia','builtin:calculator'=>'icon_calculator'
                    );
                    $html = isset($builtin[$ico]) ? $this->{$builtin[$ico]}() : SMTT_Intranet_Icon_Library::html($ico);
                    echo $this->google_app_item($app['url'], $app['title'], $app['subtitle']??'', $html);
                    ?>
                <?php endforeach; ?>
                <?php if (SMTT_Intranet_App_Launcher::is_preview()): ?><p class="smtt-apps-preview-note">Prévia do rascunho — somente CPD</p><?php endif; ?>
                <?php if (!$smtt_menu_apps): ?><p class="smtt-apps-empty">Nenhum aplicativo disponível para este perfil.</p><?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function google_app_item($href, $title, $subtitle, $icon_svg)
    {
        return sprintf(
            '<a href="%s" target="_blank" rel="noopener noreferrer" class="smtt-app-item"><span class="smtt-app-icon">%s</span><strong>%s</strong><span>%s</span></a>',
            esc_url($href),
            $icon_svg,
            esc_html($title),
            esc_html($subtitle)
        );
    }

    private function sidebar_icon($type)
    {
        return '<span class="smtt-side-icon">' . $this->sidebar_icon_svg($type) . '</span>';
    }

    private function card_title_icon($type)
    {
        switch ($type) {
            case 'system':
                return '<span class="smtt-card-title-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v10H4V5zm2 2v6h12V7H6zm-2 10h16v2H4v-2z" fill="currentColor"></path></svg></span>';
            case 'star':
                return '<span class="smtt-card-title-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l2.4 4.9L20 9l-4 3.9.9 5.6L12 16l-4.9 2.5.9-5.6L4 9l5.6-2.1L12 2z" fill="currentColor"></path></svg></span>';
            case 'news':
                return '<span class="smtt-card-title-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v11a3 3 0 0 0 3 3H8a4 4 0 0 1-4-4V5zm4 2v3h7V7H8zm0 5v1.8h7V12H8zm0 3.8v1.2h4v-1.2H8zm10-8v8a1 1 0 0 0 2 0V7h-2z" fill="currentColor"></path></svg></span>';
            case 'mural':
                return '<span class="smtt-card-title-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H9l-5 4V6a2 2 0 0 1 2-2zm3 4v2h8V8H8zm0 4v2h5v-2H8z" fill="currentColor"></path></svg></span>';
            case 'calendar':
                return '<span class="smtt-card-title-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2h2v2h6V2h2v2h3v18H4V4h3V2zm11 8H6v10h12V10z" fill="currentColor"></path></svg></span>';
            case 'knowledge':
                return '<span class="smtt-card-title-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4.5A2.5 2.5 0 0 1 8.5 2H20v17.5a1.5 1.5 0 0 0-1.5-1.5H8.5A2.5 2.5 0 0 0 6 20.5V4.5zm2.5-.5A1.5 1.5 0 0 0 7 5.5v11.76A4.46 4.46 0 0 1 8.5 17H18V4H8.5zM8.5 19H18a1 1 0 0 1 1 1v1H8.5A1.5 1.5 0 0 1 7 19.5v-.26A2.5 2.5 0 0 1 8.5 19z" fill="currentColor"></path><path d="M9 7h7v1.5H9V7zm0 3h7v1.5H9V10zm0 3h5v1.5H9V13z" fill="currentColor"></path></svg></span>';
            case 'document':
                return '<span class="smtt-card-title-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 2h8l4 4v16H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm7 1.5V7h3.5L13 3.5zM8 10v2h8v-2H8zm0 4v2h8v-2H8z" fill="currentColor"></path></svg></span>';
            case 'birthday':
             return '<span class="smtt-card-title-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="10" width="18" height="11" rx="2" stroke="currentColor" stroke-width="1.5"/><rect x="2" y="6" width="20" height="4" rx="1.5" stroke="currentColor" stroke-width="1.5"/><path d="M12 6v15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M12 6C12 6 9 3 7.5 3c-1 0-1.5.8-1.5 1.5C6 6 8.5 6 12 6z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M12 6c0 0 3-3 4.5-3 1 0 1.5.8 1.5 1.5C18 6 15.5 6 12 6z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg></span>';
            default:
                return '';
        }
    }

    private function sidebar_icon_svg($type)
    {
        switch ($type) {
            case 'calendar':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2h2v2h6V2h2v2h3v18H4V4h3V2zm11 8H6v10h12V10z" fill="currentColor"></path></svg>';
            case 'birthday':
                return '<span class="smtt-card-title-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="10" width="18" height="11" rx="2" stroke="currentColor" stroke-width="1.5"/><rect x="2" y="6" width="20" height="4" rx="1.5" stroke="currentColor" stroke-width="1.5"/><path d="M12 6v15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M12 6C12 6 9 3 7.5 3c-1 0-1.5.8-1.5 1.5C6 6 8.5 6 12 6z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M12 6c0 0 3-3 4.5-3 1 0 1.5.8 1.5 1.5C18 6 15.5 6 12 6z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg></span>';
            case 'knowledge':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4.5A2.5 2.5 0 0 1 8.5 2H20v17.5a1.5 1.5 0 0 0-1.5-1.5H8.5A2.5 2.5 0 0 0 6 20.5V4.5zm2.5-.5A1.5 1.5 0 0 0 7 5.5v11.76A4.46 4.46 0 0 1 8.5 17H18V4H8.5zM8.5 19H18a1 1 0 0 1 1 1v1H8.5A1.5 1.5 0 0 1 7 19.5v-.26A2.5 2.5 0 0 1 8.5 19z" fill="currentColor"></path><path d="M9 7h7v1.5H9V7zm0 3h7v1.5H9V10zm0 3h5v1.5H9V13z" fill="currentColor"></path></svg>';
            case 'document':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 2h8l4 4v16H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm7 1.5V7h3.5L13 3.5zM8 10v2h8v-2H8zm0 4v2h8v-2H8z" fill="currentColor"></path></svg>';
            case 'mural':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H9l-5 4V6a2 2 0 0 1 2-2zm3 4v2h8V8H8zm0 4v2h5v-2H8z" fill="currentColor"></path></svg>';
            case 'news':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v11a3 3 0 0 0 3 3H8a4 4 0 0 1-4-4V5zm4 2v3h7V7H8zm0 5v1.8h7V12H8zm0 3.8v1.2h4v-1.2H8zm10-8v8a1 1 0 0 0 2 0V7h-2z" fill="currentColor"></path></svg>';
            case 'star':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l2.4 4.9L20 9l-4 3.9.9 5.6L12 16l-4.9 2.5.9-5.6L4 9l5.6-2.1L12 2z" fill="currentColor"></path></svg>';
            case 'folder':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v1H3V6zm0 4h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-8z" fill="currentColor"></path></svg>';
            case 'system':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v10H4V5zm2 2v6h12V7H6zm-2 10h16v2H4v-2z" fill="currentColor"></path></svg>';
            case 'sector':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v4H4V4zm0 6h7v10H4V10zm9 0h7v10h-7V10z" fill="currentColor"></path></svg>';
            case 'grid':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z" fill="currentColor"></path></svg>';
            default:
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8" fill="currentColor"></circle></svg>';
        }
    }

    private function sector_short_label($name)
{
    $map = array(
        'Assessoria de Comunicação' => 'ASCOM',
        'Junta Administrativa de Recursos de Infrações' => 'JARI',
    );

    return isset($map[$name]) ? $map[$name] : $name;
}

private function render_access_mini_button($label, $icon_type = 'grid', $url = '#', $type_label = '', $icon_value = '')
{
    $url = $this->normalize_public_access_link($url);

    $target = $url !== '#'
        ? ' target="_blank" rel="noopener noreferrer"'
        : '';

    return sprintf(
        '<a href="%s" class="smtt-access-mini-card"%s><span class="smtt-access-mini-icon">%s</span><strong>%s</strong>%s</a>',
        esc_url($url),
        $target,
        SMTT_Intranet_Icon_Library::url($icon_value) ? SMTT_Intranet_Icon_Library::html($icon_value) : $this->sidebar_icon_svg($icon_type),
        esc_html($label),
        $type_label ? '<small>' . esc_html($type_label) . '</small>' : ''
    );
}

private function get_access_center_sectors()
{
    return $this->smtt_get_cached_home_data(
        'access_center_sectors',
        function () {
            $terms = get_terms(array(
                'taxonomy'   => 'smtt_sector',
                'hide_empty' => false,
            ));

            if (empty($terms) || is_wp_error($terms)) {
                return array();
            }

            $terms = array_values(array_filter($terms, function($term) {
                $visible = get_term_meta($term->term_id, '_smtt_access_center_visible', true);

                if ($visible === '') {
                    return true;
                }

                return $visible === '1';
            }));

            usort($terms, function($a, $b) {
                $order_a = (int) get_term_meta($a->term_id, '_smtt_access_center_order', true);
                $order_b = (int) get_term_meta($b->term_id, '_smtt_access_center_order', true);

                if ($order_a === $order_b) {
                    return strcasecmp($a->name, $b->name);
                }

                return $order_a <=> $order_b;
            });

            return $terms;
        },
        30 * MINUTE_IN_SECONDS
    );
}

private function get_access_center_items_by_sector($post_type, $sector_id)
{
    $post_type = sanitize_key($post_type);
    $sector_id = (int) $sector_id;

    return $this->smtt_get_cached_home_data(
        'access_center_' . $post_type . '_' . $sector_id,
        function () use ($post_type, $sector_id) {
            return get_posts(array(
                'post_type'              => $post_type,
                'posts_per_page'         => -1,
                'post_status'            => 'publish',
                'meta_key'               => '_smtt_sort_order',
                'orderby'                => 'meta_value_num',
                'order'                  => 'ASC',
                'update_post_meta_cache' => true,
                'update_post_term_cache' => false,
                'tax_query'              => array(
                    array(
                        'taxonomy' => 'smtt_sector',
                        'field'    => 'term_id',
                        'terms'    => array($sector_id),
                    ),
                ),
                'meta_query'             => array(
                    array(
                        'key'     => '_smtt_status',
                        'value'   => 'ativo',
                        'compare' => '=',
                    ),
                ),
            ));
        },
        30 * MINUTE_IN_SECONDS
    );
}

private function get_access_center_grouped_items_by_sector($post_type)
{
    $post_type = sanitize_key($post_type);

    return $this->smtt_get_cached_home_data(
        'access_center_grouped_' . $post_type,
        function () use ($post_type) {
            $items = get_posts(array(
                'post_type'              => $post_type,
                'posts_per_page'         => -1,
                'post_status'            => 'publish',
                'meta_key'               => '_smtt_sort_order',
                'orderby'                => 'meta_value_num',
                'order'                  => 'ASC',
                'update_post_meta_cache' => true,
                'update_post_term_cache' => true,
                'meta_query'             => array(
                    array(
                        'key'     => '_smtt_status',
                        'value'   => 'ativo',
                        'compare' => '=',
                    ),
                ),
            ));

            $grouped = array();

            foreach ($items as $item) {
                $sector_ids = wp_get_object_terms($item->ID, 'smtt_sector', array(
                    'fields' => 'ids',
                ));

                if (empty($sector_ids) || is_wp_error($sector_ids)) {
                    continue;
                }

                foreach ($sector_ids as $sector_id) {
                    $sector_id = (int) $sector_id;

                    if (!isset($grouped[$sector_id])) {
                        $grouped[$sector_id] = array();
                    }

                    $grouped[$sector_id][] = $item;
                }
            }

            return $grouped;
        },
        30 * MINUTE_IN_SECONDS
    );
}

private function normalize_public_access_link($link)
{
    $link = trim((string) $link);

    if ($link === '') {
        return '#';
    }

    if (preg_match('/^(https?:\/\/|mailto:|tel:|ftp:\/\/|file:\/\/)/i', $link)) {
        return $link;
    }

    return $link;
}

private function sector_panel_title($slug)
{
    $map = array(
        'ascom'  => 'Sistemas da ASCOM',
        'atende' => 'Sistemas do ATENDE',
        'dt'     => 'Sistemas da DT',
        'dtp'    => 'Sistemas da DTP',
        'dps'    => 'Sistemas da DPS',
        'daf'    => 'Sistemas da DAF',
        'jari'   => 'Sistemas da JARI',
    );

    return isset($map[$slug]) ? $map[$slug] : 'Sistemas do setor';
}

    private function arrow_down_icon()
    {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 10l4 4 4-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>';
    }

    private function close_icon()
    {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path></svg>';
    }

    private function icon_google()
    {
        return '<svg viewBox="0 0 48 48" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.6 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3 0 5.7 1.1 7.8 3l5.7-5.7C34.1 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.3-.4-3.5z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15 18.9 12 24 12c3 0 5.7 1.1 7.8 3l5.7-5.7C34.1 6.1 29.3 4 24 4c-7.7 0-14.4 4.3-17.7 10.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 10-2 13.5-5.2l-6.2-5.2C29.3 35.1 26.8 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.6 5.1C9.3 39.6 16.1 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.9 2.6-2.5 4.7-4.8 6.1l.1-.1 6.2 5.2C36.4 39 44 34 44 24c0-1.3-.1-2.3-.4-3.5z"/></svg>';
    }

    private function icon_gmail()
    {
        return '<svg viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 24.7 8 12v24h8V24l8 6 8-6v12h8V12z"/><path fill="#FBBC04" d="M0 12l8 6V12z"/><path fill="#34A853" d="M40 18l8-6v24h-8z"/><path fill="#4285F4" d="M0 12l24 18L48 12 24 0z"/></svg>';
    }

    private function icon_drive()
    {
        return '<svg viewBox="0 0 48 48" aria-hidden="true"><path fill="#0F9D58" d="M16.3 6 4 27.3h9.8L26 6z"/><path fill="#FFC107" d="M31.7 6H16.3L28.5 27.3H44z"/><path fill="#4285F4" d="M18.7 30 6.5 42h24.4l12.1-12z"/></svg>';
    }

    private function icon_docs()
    {
        return '<svg viewBox="0 0 48 48" aria-hidden="true"><path fill="#4285F4" d="M30 4H14c-2.2 0-4 1.8-4 4v32c0 2.2 1.8 4 4 4h20c2.2 0 4-1.8 4-4V12z"/><path fill="#AECBFA" d="M30 4v8h8z"/><path fill="#fff" d="M16 20h16v2H16zm0 6h16v2H16zm0 6h12v2H16z"/></svg>';
    }

    private function icon_sheets()
    {
        return '<svg viewBox="0 0 48 48" aria-hidden="true"><path fill="#0F9D58" d="M28 4H12c-2.2 0-4 1.8-4 4v32c0 2.2 1.8 4 4 4h24c2.2 0 4-1.8 4-4V16z"/><path fill="#87CEAC" d="M28 4v12h12z"/><path fill="#fff" d="M14 20h16v2H14zm0 6h20v2H14zm0 6h20v2H14z"/></svg>';
    }

    private function icon_slides()
    {
        return '<svg viewBox="0 0 48 48" aria-hidden="true"><path fill="#F9AB00" d="M30 4H14c-2.2 0-4 1.8-4 4v32c0 2.2 1.8 4 4 4h20c2.2 0 4-1.8 4-4V12z"/><path fill="#FDD663" d="M30 4v8h8z"/><rect x="16" y="18" width="16" height="12" rx="1.5" fill="#fff"/><rect x="18" y="32" width="12" height="2" rx="1" fill="#fff"/></svg>';
    }

    private function icon_translate()
    {
        return '<svg viewBox="0 0 48 48" aria-hidden="true"><path fill="#4285F4" d="M24 8H10c-2.2 0-4 1.8-4 4v20h4V12h14z"/><path fill="#34A853" d="M38 16H20c-2.2 0-4 1.8-4 4v20h22c2.2 0 4-1.8 4-4V20c0-2.2-1.8-4-4-4z"/><path fill="#fff" d="M28.6 31.4h2.3l-3.8-10.1h-2.2l-3.8 10.1h2.2l.8-2.2h3.7zm-3.8-4.1 1.2-3.4 1.2 3.4z"/><path fill="#fff" d="M13 18h8v2h-3v8h-2v-8h-3z"/></svg>';
    }

    private function icon_wikipedia()
    {
        return '<svg viewBox="0 0 48 48" aria-hidden="true"><circle cx="24" cy="24" r="20" fill="#F1F3F4"/><path fill="#202124" d="M15 16h4l4 12 3-8-1-4h4l4 12 3-12h3l-5 16h-3l-4-11-4 11h-3z"/></svg>';
    }

    private function icon_calculator()
    {
        return '<svg viewBox="0 0 48 48" aria-hidden="true"><rect x="10" y="4" width="28" height="40" rx="4" fill="#5F6368"/><rect x="14" y="8" width="20" height="8" rx="2" fill="#E8EAED"/><circle cx="18" cy="24" r="2.2" fill="#fff"/><circle cx="24" cy="24" r="2.2" fill="#fff"/><circle cx="30" cy="24" r="2.2" fill="#fff"/><circle cx="18" cy="31" r="2.2" fill="#fff"/><circle cx="24" cy="31" r="2.2" fill="#fff"/><circle cx="30" cy="31" r="2.2" fill="#fff"/><rect x="16" y="36" width="16" height="3" rx="1.5" fill="#fff"/></svg>';
    }

    private function icon_1doc()
    {
        return '<span class="smtt-side-icon"><svg viewBox="0 0 48 48" aria-hidden="true"><rect x="6" y="6" width="36" height="36" rx="8" fill="#10A5CD"></rect><path d="M18 16h12v4H26v12h-4V20h-4z" fill="#ffffff"></path></svg></span>';
    }

    private function link_util_icon($icon)
{
    if (SMTT_Intranet_Icon_Library::url($icon)) { return '<span class="smtt-side-icon smtt-side-icon--util">' . SMTT_Intranet_Icon_Library::html($icon) . '</span>'; }
    $icons = array(
        'document-check' => '
            <path d="M14 3h8l6 6v22a3 3 0 0 1-3 3H14a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3z"></path>
            <path d="M21 3v7h7"></path>
            <path d="M16 22l4 4 8-9"></path>
        ',

        'brain' => '
            <path d="M17 8a5 5 0 0 0-5 5 5 5 0 0 0-4 5 5 5 0 0 0 4 5 6 6 0 0 0 11 3"></path>
            <path d="M31 8a5 5 0 0 1 5 5 5 5 0 0 1 4 5 5 5 0 0 1-4 5 6 6 0 0 1-11 3"></path>
            <path d="M24 8v23"></path>
            <path d="M16 15h5"></path>
            <path d="M27 15h5"></path>
            <path d="M15 23h6"></path>
            <path d="M27 23h6"></path>
        ',

        'shopping-cart' => '
            <path d="M8 9h4l3 16h18l4-11H15"></path>
            <circle cx="18" cy="34" r="2.5"></circle>
            <circle cx="31" cy="34" r="2.5"></circle>
        ',

        'newspaper' => '
            <path d="M9 10h27a3 3 0 0 1 3 3v22H12a3 3 0 0 1-3-3V10z"></path>
            <path d="M14 16h10"></path>
            <path d="M14 22h20"></path>
            <path d="M14 28h20"></path>
            <path d="M28 16h6"></path>
        ',

        'id-card' => '
            <rect x="8" y="11" width="32" height="25" rx="4"></rect>
            <circle cx="18" cy="23" r="4"></circle>
            <path d="M13 31c1.4-3 8.6-3 10 0"></path>
            <path d="M28 20h7"></path>
            <path d="M28 26h7"></path>
        ',

        'settings' => '
            <circle cx="24" cy="24" r="4"></circle>
            <path d="M24 7v5"></path>
            <path d="M24 36v5"></path>
            <path d="M7 24h5"></path>
            <path d="M36 24h5"></path>
            <path d="M12.2 12.2l3.5 3.5"></path>
            <path d="M32.3 32.3l3.5 3.5"></path>
            <path d="M35.8 12.2l-3.5 3.5"></path>
            <path d="M15.7 32.3l-3.5 3.5"></path>
        ',

        'landmark' => '
            <path d="M24 7l16 8H8l16-8z"></path>
            <path d="M12 19v14"></path>
            <path d="M20 19v14"></path>
            <path d="M28 19v14"></path>
            <path d="M36 19v14"></path>
            <path d="M8 37h32"></path>
        ',

        'car' => '
            <path d="M12 28h24l-3-10a4 4 0 0 0-4-3H19a4 4 0 0 0-4 3l-3 10z"></path>
            <path d="M10 28v7"></path>
            <path d="M38 28v7"></path>
            <circle cx="16" cy="33" r="2"></circle>
            <circle cx="32" cy="33" r="2"></circle>
        ',

        'route' => '
            <circle cx="14" cy="14" r="4"></circle>
            <circle cx="34" cy="34" r="4"></circle>
            <path d="M18 14h8a6 6 0 0 1 0 12h-4a6 6 0 0 0 0 12h8"></path>
        ',

        'headset' => '
            <path d="M10 25v-3a14 14 0 0 1 28 0v3"></path>
            <path d="M10 25a4 4 0 0 1 4-4h2v10h-2a4 4 0 0 1-4-4v-2z"></path>
            <path d="M38 25a4 4 0 0 0-4-4h-2v10h2a4 4 0 0 0 4-4v-2z"></path>
            <path d="M31 35h-5"></path>
        ',

        'eye' => '
            <path d="M5 24s7-11 19-11 19 11 19 11-7 11-19 11S5 24 5 24z"></path>
            <circle cx="24" cy="24" r="5"></circle>
        ',

        'mail' => '
            <rect x="8" y="12" width="32" height="24" rx="4"></rect>
            <path d="M10 15l14 11 14-11"></path>
        ',
    );

    $svg_paths = isset($icons[$icon]) ? $icons[$icon] : $icons['document-check'];

    return '<span class="smtt-side-icon smtt-side-icon--util">
        <svg viewBox="0 0 48 48" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            ' . $svg_paths . '
        </svg>
    </span>';
}

private function smtt_get_cached_home_data($key, $callback, $expiration = 600)
{
    if (!is_callable($callback)) {
        return array();
    }

    $cache_key = 'smtt_home_' . $this->smtt_get_home_cache_generation() . '_' . sanitize_key($key);

    $cached = get_transient($cache_key);

    if ($cached !== false) {
        return $cached;
    }

    $data = call_user_func($callback);

    set_transient($cache_key, $data, $expiration);

    return $data;
}

private function smtt_get_home_cache_generation()
{
    $generation = (int) get_option('smtt_home_cache_generation', 1);
    return $generation > 0 ? $generation : 1;
}

public function clear_home_cache($post_id = 0)
{
    // Evita limpar cache em salvamentos automáticos e revisões.
    if (!empty($post_id)) {
        if (function_exists('wp_is_post_revision') && wp_is_post_revision($post_id)) {
            return;
        }

        if (function_exists('wp_is_post_autosave') && wp_is_post_autosave($post_id)) {
            return;
        }
    }

    /*
     * A geração torna a invalidação compatível com cache padrão e também com
     * Redis/Memcached, sem executar DELETE com wildcard na tabela wp_options.
     * Os transients da geração anterior expiram naturalmente.
     */
    $next_generation = $this->smtt_get_home_cache_generation() + 1;
    update_option('smtt_home_cache_generation', $next_generation, false);
}

private function smtt_get_home_agenda_year_data($cal_year, $year_start_db, $year_end_db)
{
    $cal_year = (int) $cal_year;
    $cache_key = 'smtt_home_' . $this->smtt_get_home_cache_generation() . '_agenda_year_' . $cal_year;
    $cached = get_transient($cache_key);

    if ($cached !== false && is_array($cached)) {
        return $cached;
    }

    /*
     * v35: a consulta é limitada ao ano selecionado. Antes todos os itens de
     * todos os anos eram carregados e descartados em PHP, o que aumentava tempo
     * de banco e memória a cada abertura da home.
     *
     * O segundo ramo mantém compatibilidade com registros antigos em d/m/Y.
     */
    $all_agenda = get_posts(array(
        'post_type'              => 'smtt_agenda_item',
        'posts_per_page'         => -1,
        'post_status'            => 'publish',
        'meta_key'               => '_smtt_event_date',
        'orderby'                => 'meta_value',
        'order'                  => 'ASC',
        'update_post_meta_cache' => true,
        'update_post_term_cache' => true,
        'no_found_rows'          => true,
        'meta_query'             => array(
            'relation' => 'OR',
            array(
                'key'     => '_smtt_event_date',
                'value'   => array($year_start_db, $year_end_db),
                'compare' => 'BETWEEN',
                'type'    => 'DATE',
            ),
            array(
                'key'     => '_smtt_event_date',
                'value'   => '/' . $cal_year,
                'compare' => 'LIKE',
            ),
        ),
    ));

    $agenda_data = array();

    foreach ($all_agenda as $ev) {
        $raw_date = trim((string) get_post_meta($ev->ID, '_smtt_event_date', true));
        $ev_date  = $this->smtt_normalize_agenda_date($raw_date);

        if ($ev_date === '') {
            continue;
        }

        $date_parts = explode('-', $ev_date);
        if (count($date_parts) !== 3) {
            continue;
        }

        $ev_year  = (int) $date_parts[0];
        $ev_month = (int) $date_parts[1];
        $ev_day   = (int) $date_parts[2];

        if ($ev_year !== $cal_year || !checkdate($ev_month, $ev_day, $ev_year)) {
            continue;
        }

        $start_time       = get_post_meta($ev->ID, '_smtt_start_time', true);
        $end_time         = get_post_meta($ev->ID, '_smtt_end_time', true);
        $description      = get_post_meta($ev->ID, '_smtt_description', true);
        $location_text    = get_post_meta($ev->ID, '_smtt_location_text', true);
        $responsible_name = get_post_meta($ev->ID, '_smtt_responsible_name', true);
        $status           = get_post_meta($ev->ID, '_smtt_status', true);
        $item_type        = get_post_meta($ev->ID, '_smtt_item_type', true);

        if ($status === 'cancelado') {
            continue;
        }

        $space_name    = $this->smtt_get_first_term_name($ev->ID, 'smtt_agenda_space');
        $sector_name   = $this->smtt_get_first_term_name($ev->ID, 'smtt_sector');
        $category_name = $this->smtt_get_first_term_name($ev->ID, 'smtt_agenda_category');

        $local_parts = array_filter(array($space_name, $location_text, $sector_name));

        if (!isset($agenda_data[$ev_month])) {
            $agenda_data[$ev_month] = array();
        }
        if (!isset($agenda_data[$ev_month][$ev_day])) {
            $agenda_data[$ev_month][$ev_day] = array();
        }

        $agenda_data[$ev_month][$ev_day][] = array(
            'id'          => (int) $ev->ID,
            'date'        => $ev_date,
            'title'       => get_the_title($ev->ID),
            'time'        => $start_time ? $start_time : '--:--',
            'endTime'     => $end_time ? $end_time : '',
            'local'       => !empty($local_parts) ? implode(' · ', $local_parts) : 'Evento interno',
            'category'    => $category_name ? $category_name : '',
            'responsible' => $responsible_name ? $responsible_name : '',
            'status'      => $status ? $status : 'agendado',
            'type'        => $item_type ? $item_type : 'evento',
            'description' => $description ? $description : '',
        );
    }

    foreach ($agenda_data as &$month_items) {
        foreach ($month_items as &$day_items) {
            usort($day_items, function ($a, $b) {
                return strcmp((string) $a['time'], (string) $b['time']);
            });
        }
    }
    unset($month_items, $day_items);

    set_transient($cache_key, $agenda_data, 15 * MINUTE_IN_SECONDS);

    return $agenda_data;
}

private function smtt_get_first_term_name($post_id, $taxonomy)
{
    $terms = get_the_terms($post_id, $taxonomy);

    if (empty($terms) || is_wp_error($terms)) {
        return '';
    }

    $first = reset($terms);

    return ($first && isset($first->name)) ? (string) $first->name : '';
}

private function smtt_normalize_agenda_date($raw_date)
{
    $raw_date = trim((string) $raw_date);

    if ($raw_date === '') {
        return '';
    }

    // Formato padrão do input type=date e do cadastro atual.
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw_date, $matches)) {
        $year  = (int) $matches[1];
        $month = (int) $matches[2];
        $day   = (int) $matches[3];

        return checkdate($month, $day, $year)
            ? sprintf('%04d-%02d-%02d', $year, $month, $day)
            : '';
    }

    // Compatibilidade com eventuais registros antigos em d/m/Y.
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $raw_date, $matches)) {
        $day   = (int) $matches[1];
        $month = (int) $matches[2];
        $year  = (int) $matches[3];

        return checkdate($month, $day, $year)
            ? sprintf('%04d-%02d-%02d', $year, $month, $day)
            : '';
    }

    $timestamp = strtotime($raw_date);

    return $timestamp ? date('Y-m-d', $timestamp) : '';
}

}
