<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Admin_Menu {

    public function register() {

        // O menu técnico utiliza um ícone de 20 px; o CSS é restrito ao item SMTT.
        add_action('admin_enqueue_scripts', array($this, 'enqueue_menu_icon_style'));

    $ramais_admin = new SMTT_Intranet_Ramais_Admin();
    $ramais_admin->register_hooks();
        add_menu_page(
            __('SMTT Intranet', 'smtt-intranet'),
            __('SMTT Intranet', 'smtt-intranet'),
            'smtt_access_intranet',
            'smtt-intranet',
            array($this, 'render_dashboard'),
            SMTT_INTRANET_URL . 'assets/admin/smtt-menu-white.png',
            3
        );

        add_submenu_page(
            null,
            __('Dashboard', 'smtt-intranet'),
            __('Dashboard', 'smtt-intranet'),
            'smtt_access_intranet',
            'smtt-intranet',
            array($this, 'render_dashboard')
        );

        // Página administrativa oculta: acessível pelos cards do Dashboard,
        // sem aparecer como submenu lateral do WordPress.
        add_submenu_page(
            null,
            __('Central de Acessos', 'smtt-intranet'),
            __('Central de Acessos', 'smtt-intranet'),
            'smtt_manage_access_center',
            'smtt-intranet-access-center',
            array($this, 'render_access_center')
        );

        add_submenu_page(
            null,
            __('Comunicados', 'smtt-intranet'),
            __('Comunicados', 'smtt-intranet'),
            'smtt_manage_announcements',
            'smtt-intranet-announcements',
            array($this, 'render_announcements_placeholder')
        );

        add_submenu_page(
            null,
            __('Documentos', 'smtt-intranet'),
            __('Documentos', 'smtt-intranet'),
            'smtt_manage_documents',
            'smtt-intranet-documents',
            array($this, 'render_documents_placeholder')
        );

        add_submenu_page(
            null,
            __('Ramais', 'smtt-intranet'),
            __('Ramais', 'smtt-intranet'),
            'smtt_manage_directory',
            'smtt-intranet-directory',
            array($this, 'render_ramais_page')
        );

        add_submenu_page(
            null,
            __('Agenda', 'smtt-intranet'),
            __('Agenda', 'smtt-intranet'),
            'smtt_manage_calendar',
            'smtt-intranet-calendar',
            array($this, 'render_calendar_placeholder')
        );

        add_submenu_page(
            null,
            __('Mural Interno', 'smtt-intranet'),
            __('Mural Interno', 'smtt-intranet'),
            'smtt_manage_mural',
            'smtt-intranet-mural',
            array($this, 'render_mural_page')
        );

        add_submenu_page(
            null,
            __('Base Interna', 'smtt-intranet'),
            __('Base Interna', 'smtt-intranet'),
            'smtt_manage_knowledge_base',
            'smtt-intranet-knowledge-base',
            array($this, 'render_knowledge_base_placeholder')
        );
        add_submenu_page(
            null,
            __('Aniversariantes', 'smtt-intranet'),
            __('Aniversariantes', 'smtt-intranet'),
            'smtt_manage_birthdays',
            'smtt-intranet-birthdays',
            array($this, 'render_birthdays_page')
        );

        add_submenu_page(
            null,
            __('Tour da Intranet V2', 'smtt-intranet'),
            __('Tour da Intranet V2', 'smtt-intranet'),
            SMTT_Intranet_Tour::CAPABILITY,
            'smtt-intranet-tour',
            array($this, 'render_tour_page')
        );

        // Sem avisos de navegação para gerenciadores legados no wp-admin.
    }

    /** Mantém a marca pequena e centralizada no menu nativo do WordPress. */
    public function enqueue_menu_icon_style() {
        wp_register_style('smtt-admin-menu-icon', false, array(), SMTT_INTRANET_VERSION);
        wp_enqueue_style('smtt-admin-menu-icon');
        wp_add_inline_style('smtt-admin-menu-icon',
            '#adminmenu #toplevel_page_smtt-intranet .wp-menu-image img{' .
            'display:block!important;box-sizing:content-box!important;width:20px!important;height:20px!important;' .
            'max-width:20px!important;max-height:20px!important;object-fit:contain!important;' .
            'padding:7px 0 0!important;margin:0 auto!important;opacity:.9!important;}' .
            '#adminmenu #toplevel_page_smtt-intranet:hover .wp-menu-image img,' .
            '#adminmenu #toplevel_page_smtt-intranet.wp-has-current-submenu .wp-menu-image img{opacity:1!important;}'
        );
    }

    /**
     * O wp-admin é reservado à manutenção técnica do WordPress.
     * Toda a administração funcional da Intranet pertence ao portal frontend.
     * Não listar módulos, cadastros ou ações administrativos nesta tela.
     */
    public function render_dashboard() {
        if (!current_user_can('smtt_access_intranet')) {
            wp_die(esc_html__('Você não tem permissão para acessar esta página.', 'smtt-intranet'));
        }
        $intranet_url = SMTT_Intranet_Home_Routing::url();
        ?>
        <div class="wrap smtt-intranet-launcher" style="max-width:780px;margin-top:32px">
            <div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:32px 36px;box-shadow:0 2px 12px rgba(0,0,0,.04)">
                <h1 style="margin:0 0 12px;font-size:26px;line-height:1.3">
                    <?php echo esc_html__('SMTT Intranet V2', 'smtt-intranet'); ?>
                </h1>
                <p style="font-size:15px;color:#50575e;max-width:600px;margin:0 0 24px">
                    <?php echo esc_html__('A gestão de módulos, conteúdos, ícones, setores e permissões é realizada na própria Intranet, conforme as autorizações corporativas.', 'smtt-intranet'); ?>
                </p>
                <?php if ($intranet_url) : ?>
                    <a class="button button-primary button-hero" href="<?php echo esc_url($intranet_url); ?>" target="_blank" rel="noopener noreferrer">
                        <span class="dashicons dashicons-external" aria-hidden="true" style="margin-top:7px;margin-right:8px"></span>
                        <?php echo esc_html__('Abrir Intranet', 'smtt-intranet'); ?>
                    </a>
                <?php else : ?>
                    <p class="notice notice-error" style="padding:12px">
                        Não foi possível localizar ou criar a página inicial da Intranet. Verifique as permissões de gravação do WordPress.
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function render_tour_page() {
        $tour = new SMTT_Intranet_Tour();
        $tour->render_admin_page();
    }

    public function render_birthdays_page() {
    if (!current_user_can('smtt_manage_birthdays')) {
        wp_die(esc_html__('Você não tem permissão para acessar esta página.', 'smtt-intranet'));
    }

    $birthdays_module = new SMTT_Intranet_Birthdays();
    $import_result = null;

    if (
        isset($_POST['smtt_birthdays_import_nonce']) &&
        wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['smtt_birthdays_import_nonce'])), 'smtt_birthdays_import_csv')
    ) {
        if (!empty($_FILES['smtt_birthdays_csv']['tmp_name'])) {
            $import_result = $birthdays_module->import_from_csv($_FILES['smtt_birthdays_csv']['tmp_name']);
        } else {
            $import_result = array(
                'success'  => false,
                'message'  => __('Selecione um arquivo CSV para importar.', 'smtt-intranet'),
                'inserted' => 0,
                'updated'  => 0,
                'errors'   => array(),
            );
        }
    }

    $items = $birthdays_module->get_month_birthdays();

    $display_mode = get_option(SMTT_Intranet_Birthdays::OPTION_DISPLAY_MODE, 'carrossel');
    $show_photo   = get_option(SMTT_Intranet_Birthdays::OPTION_SHOW_PHOTO, '1');
    $show_sector  = get_option(SMTT_Intranet_Birthdays::OPTION_SHOW_SECTOR, '1');
    $show_role    = get_option(SMTT_Intranet_Birthdays::OPTION_SHOW_ROLE, '1');
    $max_items    = get_option(SMTT_Intranet_Birthdays::OPTION_MAX_ITEMS, 12);
    ?>
    <div class="wrap smtt-intranet-admin">
        <?php $this->render_dashboard_back_button(); ?>
        <div class="smtt-topbar">
            <div>
                <h1><?php echo esc_html__('Aniversariantes', 'smtt-intranet'); ?></h1>
                <p class="smtt-subtitle"><?php echo esc_html__('Base de colaboradores e configuração do bloco de aniversariantes para o front interno.', 'smtt-intranet'); ?></p>
            </div>
        </div>

        <?php if (is_array($import_result)) : ?>
            <div class="notice <?php echo !empty($import_result['success']) ? 'notice-success' : 'notice-error'; ?> is-dismissible">
                <p>
                    <?php
                    echo esc_html($import_result['message']);

                    if (!empty($import_result['success'])) {
                        echo ' ' . esc_html(sprintf(
                            __('Inseridos: %d | Atualizados: %d', 'smtt-intranet'),
                            (int) $import_result['inserted'],
                            (int) $import_result['updated']
                        ));
                    }
                    ?>
                </p>

                <?php if (!empty($import_result['errors'])) : ?>
                    <ul style="margin-left:20px;">
                        <?php foreach ($import_result['errors'] as $error) : ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="smtt-main-grid smtt-main-grid-single">
            <section class="smtt-panel">
                <div class="smtt-panel-header">
                    <h2><?php echo esc_html__('Importação por CSV', 'smtt-intranet'); ?></h2>
                </div>

                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('smtt_birthdays_import_csv', 'smtt_birthdays_import_nonce'); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="smtt_birthdays_csv"><?php echo esc_html__('Arquivo CSV', 'smtt-intranet'); ?></label></th>
                            <td>
                                <input type="file" name="smtt_birthdays_csv" id="smtt_birthdays_csv" accept=".csv">
                                <p class="description">
                                    <?php echo esc_html__('Aceita o formato simples: Nome,Data de Nascimento — ou o formato completo: nome,setor,cargo,data_nascimento,status.', 'smtt-intranet'); ?>
                                </p>
                                <p class="description">
                                    <?php echo esc_html__('Datas aceitas: 12/04/1990, 12/04/90 ou 1990-04-12. CSV com vírgula ou ponto e vírgula é reconhecido automaticamente.', 'smtt-intranet'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <button type="submit" class="button button-primary">
                        <?php echo esc_html__('Importar CSV', 'smtt-intranet'); ?>
                    </button>
                </form>
            </section>
        </div>

        <div class="smtt-main-grid smtt-main-grid-single">
            <section class="smtt-panel">
                <div class="smtt-panel-header">
                    <h2><?php echo esc_html__('Configuração de exibição', 'smtt-intranet'); ?></h2>
                </div>

                <form method="post" action="options.php">
                    <?php settings_fields('smtt_birthdays_settings'); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="smtt_birthdays_display_mode"><?php echo esc_html__('Modelo visual', 'smtt-intranet'); ?></label></th>
                            <td>
                                <select name="<?php echo esc_attr(SMTT_Intranet_Birthdays::OPTION_DISPLAY_MODE); ?>" id="smtt_birthdays_display_mode">
                                    <option value="carrossel" <?php selected($display_mode, 'carrossel'); ?>><?php echo esc_html__('Carrossel', 'smtt-intranet'); ?></option>
                                    <option value="cards" <?php selected($display_mode, 'cards'); ?>><?php echo esc_html__('Cards', 'smtt-intranet'); ?></option>
                                    <option value="faixa" <?php selected($display_mode, 'faixa'); ?>><?php echo esc_html__('Faixa deslizante', 'smtt-intranet'); ?></option>
                                    <option value="destaque_dia" <?php selected($display_mode, 'destaque_dia'); ?>><?php echo esc_html__('Destaque do dia', 'smtt-intranet'); ?></option>
                                </select>
                                <p class="description"><?php echo esc_html__('O front aplicará o efeito automaticamente conforme o modelo escolhido.', 'smtt-intranet'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php echo esc_html__('Mostrar foto', 'smtt-intranet'); ?></th>
                            <td>
                                <label><input type="checkbox" name="<?php echo esc_attr(SMTT_Intranet_Birthdays::OPTION_SHOW_PHOTO); ?>" value="1" <?php checked($show_photo, '1'); ?>> <?php echo esc_html__('Exibir foto do colaborador', 'smtt-intranet'); ?></label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php echo esc_html__('Mostrar setor', 'smtt-intranet'); ?></th>
                            <td>
                                <label><input type="checkbox" name="<?php echo esc_attr(SMTT_Intranet_Birthdays::OPTION_SHOW_SECTOR); ?>" value="1" <?php checked($show_sector, '1'); ?>> <?php echo esc_html__('Exibir setor', 'smtt-intranet'); ?></label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php echo esc_html__('Mostrar função/cargo', 'smtt-intranet'); ?></th>
                            <td>
                                <label><input type="checkbox" name="<?php echo esc_attr(SMTT_Intranet_Birthdays::OPTION_SHOW_ROLE); ?>" value="1" <?php checked($show_role, '1'); ?>> <?php echo esc_html__('Exibir função/cargo', 'smtt-intranet'); ?></label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="smtt_birthdays_max_items"><?php echo esc_html__('Quantidade máxima', 'smtt-intranet'); ?></label></th>
                            <td>
                                <input type="number" min="1" max="100" name="<?php echo esc_attr(SMTT_Intranet_Birthdays::OPTION_MAX_ITEMS); ?>" id="smtt_birthdays_max_items" value="<?php echo esc_attr($max_items); ?>" class="small-text">
                            </td>
                        </tr>
                    </table>

                    <?php submit_button(__('Salvar configuração', 'smtt-intranet')); ?>
                </form>
            </section>
        </div>

        <div class="smtt-main-grid smtt-main-grid-single">
            <section class="smtt-panel">
                <div class="smtt-panel-header smtt-panel-header-flex">
                    <h2><?php echo esc_html__('Aniversariantes do mês', 'smtt-intranet'); ?></h2>
                    <a href="<?php echo esc_url(admin_url('post-new.php?post_type=smtt_birthday_person')); ?>" class="button button-primary">
                        <?php echo esc_html__('Adicionar colaborador', 'smtt-intranet'); ?>
                    </a>
                </div>

                <div class="smtt-access-grid">
                    <?php if (!empty($items)) : ?>
                        <?php foreach ($items as $item) : ?>
                            <?php
                            $role         = get_post_meta($item->ID, '_smtt_role', true);
                            $birth_date   = get_post_meta($item->ID, '_smtt_birth_date', true);
                            $photo_id     = get_post_meta($item->ID, '_smtt_photo_id', true);
                            $photo_url    = $photo_id ? wp_get_attachment_image_url((int) $photo_id, 'medium') : '';
                            $sector_terms = wp_get_object_terms($item->ID, 'smtt_sector', array('fields' => 'names'));
                            ?>
                            <div class="smtt-access-card">
                                <div class="smtt-access-card-top">
                                    <div class="smtt-module-icon">
                                        <span class="dashicons dashicons-heart"></span>
                                    </div>
                                    <span class="smtt-module-status"><?php echo esc_html(date_i18n('d/m', strtotime($birth_date))); ?></span>
                                </div>

                                <?php if (!empty($photo_url)) : ?>
                                    <div style="margin-bottom:12px;">
                                        <img src="<?php echo esc_url($photo_url); ?>" style="width:100%;height:auto;border:1px solid #ddd;">
                                    </div>
                                <?php endif; ?>

                                <h3><?php echo esc_html(get_the_title($item->ID)); ?></h3>

                                <?php if (!empty($role)) : ?>
                                    <div class="smtt-meta-line"><strong><?php echo esc_html__('Função:', 'smtt-intranet'); ?></strong> <?php echo esc_html($role); ?></div>
                                <?php endif; ?>

                                <?php if (!is_wp_error($sector_terms) && !empty($sector_terms)) : ?>
                                    <div class="smtt-meta-line"><strong><?php echo esc_html__('Setor:', 'smtt-intranet'); ?></strong> <?php echo esc_html($sector_terms[0]); ?></div>
                                <?php endif; ?>

                                <div class="smtt-access-actions">
                                    <a class="button" href="<?php echo esc_url(get_edit_post_link($item->ID)); ?>">
                                        <?php echo esc_html__('Editar', 'smtt-intranet'); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="smtt-empty-state">
                            <p><?php echo esc_html__('Nenhum aniversariante encontrado para o mês atual.', 'smtt-intranet'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
    <?php
}

    public function render_mural_page() {
        if (!current_user_can('smtt_manage_mural')) {
            wp_die(esc_html__('Você não tem permissão para acessar esta página.', 'smtt-intranet'));
        }

        $search   = isset($_GET['smtt_mural_search']) ? sanitize_text_field(wp_unslash($_GET['smtt_mural_search'])) : '';
        $status   = isset($_GET['smtt_mural_status']) ? sanitize_text_field(wp_unslash($_GET['smtt_mural_status'])) : '';
        $type     = isset($_GET['smtt_mural_type']) ? sanitize_text_field(wp_unslash($_GET['smtt_mural_type'])) : '';
        $sector   = isset($_GET['smtt_mural_sector']) ? intval($_GET['smtt_mural_sector']) : 0;
        $category = isset($_GET['smtt_mural_category']) ? intval($_GET['smtt_mural_category']) : 0;

        $items = get_posts(array(
            'post_type'      => 'smtt_mural_item',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_key'       => '_smtt_sort_order',
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
        ));

        $items = array_values(array_filter($items, function($item) use ($search, $status, $type, $sector, $category) {
            $summary      = (string) get_post_meta($item->ID, '_smtt_summary', true);
            $display_type = (string) get_post_meta($item->ID, '_smtt_display_type', true);
            $item_status  = (string) get_post_meta($item->ID, '_smtt_status', true);

            if ($status && $item_status !== $status) {
                return false;
            }

            if ($type && $display_type !== $type) {
                return false;
            }

            if ($sector > 0) {
                $terms = wp_get_object_terms($item->ID, 'smtt_sector', array('fields' => 'ids'));
                $terms = is_wp_error($terms) ? array() : array_map('intval', $terms);

                if (empty($terms) || !in_array($sector, $terms, true)) {
                    return false;
                }
            }

            if ($category > 0) {
                $terms = wp_get_object_terms($item->ID, 'smtt_mural_category', array('fields' => 'ids'));
                $terms = is_wp_error($terms) ? array() : array_map('intval', $terms);

                if (empty($terms) || !in_array($category, $terms, true)) {
                    return false;
                }
            }

            if ($search) {
                $content = wp_strip_all_tags((string) get_post_field('post_content', $item->ID));
                $haystack = strtolower(get_the_title($item->ID) . ' ' . $summary . ' ' . $content);

                if (strpos($haystack, strtolower($search)) === false) {
                    return false;
                }
            }

            return true;
        }));
        ?>
        <div class="wrap smtt-intranet-admin">
        <?php $this->render_dashboard_back_button(); ?>
            <div class="smtt-topbar">
                <div>
                    <h1><?php echo esc_html__('Mural Interno', 'smtt-intranet'); ?></h1>
                    <p class="smtt-subtitle"><?php echo esc_html__('Banners, cards, faixas e avisos visuais da intranet.', 'smtt-intranet'); ?></p>
                </div>
            </div>

            <form method="get" class="smtt-filter-bar">
                <input type="hidden" name="page" value="smtt-intranet-mural">

                <input type="text" name="smtt_mural_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php echo esc_attr__('Buscar itens do mural...', 'smtt-intranet'); ?>">

                <select name="smtt_mural_status">
                    <option value=""><?php echo esc_html__('Todos os status', 'smtt-intranet'); ?></option>
                    <option value="ativo" <?php selected($status, 'ativo'); ?>><?php echo esc_html__('Ativo', 'smtt-intranet'); ?></option>
                    <option value="inativo" <?php selected($status, 'inativo'); ?>><?php echo esc_html__('Inativo', 'smtt-intranet'); ?></option>
                </select>

                <select name="smtt_mural_type">
                    <option value=""><?php echo esc_html__('Todos os formatos', 'smtt-intranet'); ?></option>
                    <option value="banner" <?php selected($type, 'banner'); ?>><?php echo esc_html__('Banner principal', 'smtt-intranet'); ?></option>
                    <option value="card" <?php selected($type, 'card'); ?>><?php echo esc_html__('Card', 'smtt-intranet'); ?></option>
                    <option value="topo_deslizante" <?php selected($type, 'topo_deslizante'); ?>><?php echo esc_html__('Faixa superior', 'smtt-intranet'); ?></option>
                    <option value="rodape_deslizante" <?php selected($type, 'rodape_deslizante'); ?>><?php echo esc_html__('Faixa inferior', 'smtt-intranet'); ?></option>
                    <option value="alerta" <?php selected($type, 'alerta'); ?>><?php echo esc_html__('Alerta', 'smtt-intranet'); ?></option>
                </select>

                <?php
                wp_dropdown_categories(array(
                    'taxonomy'         => 'smtt_sector',
                    'name'             => 'smtt_mural_sector',
                    'orderby'          => 'name',
                    'show_option_all'  => __('Todos os setores', 'smtt-intranet'),
                    'hide_empty'       => false,
                    'hierarchical'     => true,
                    'selected'         => $sector,
                ));
                ?>

                <?php
                wp_dropdown_categories(array(
                    'taxonomy'         => 'smtt_mural_category',
                    'name'             => 'smtt_mural_category',
                    'orderby'          => 'name',
                    'show_option_all'  => __('Todas as categorias', 'smtt-intranet'),
                    'hide_empty'       => false,
                    'hierarchical'     => true,
                    'selected'         => $category,
                ));
                ?>

                <button type="submit" class="button button-primary"><?php echo esc_html__('Filtrar', 'smtt-intranet'); ?></button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=smtt-intranet-mural')); ?>" class="button"><?php echo esc_html__('Limpar', 'smtt-intranet'); ?></a>
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=smtt_mural_item')); ?>" class="button button-primary"><?php echo esc_html__('Adicionar item', 'smtt-intranet'); ?></a>
                <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=smtt_mural_category&post_type=smtt_mural_item')); ?>" class="button"><?php echo esc_html__('Gerenciar categorias', 'smtt-intranet'); ?></a>
            </form>

            <div class="smtt-main-grid smtt-main-grid-single">
                <section class="smtt-panel">
                    <div class="smtt-access-grid">
                        <?php if (!empty($items)) : ?>
                            <?php foreach ($items as $item) : ?>
                                <?php
                                $subtitle     = get_post_meta($item->ID, '_smtt_subtitle', true);
                                $summary      = get_post_meta($item->ID, '_smtt_summary', true);
                                $display_type = get_post_meta($item->ID, '_smtt_display_type', true);
                                $item_status  = get_post_meta($item->ID, '_smtt_status', true);
                                $image_id     = get_post_meta($item->ID, '_smtt_image_id', true);
                                $thumb        = $image_id ? wp_get_attachment_image_url((int) $image_id, 'medium') : '';

                                $sector_terms   = wp_get_object_terms($item->ID, 'smtt_sector', array('fields' => 'names'));
                                $category_terms = wp_get_object_terms($item->ID, 'smtt_mural_category', array('fields' => 'names'));
                                ?>
                                <div class="smtt-access-card">
                                    <div class="smtt-access-card-top">
                                        <div class="smtt-module-icon">
                                            <span class="dashicons dashicons-format-image"></span>
                                        </div>
                                        <span class="smtt-module-status"><?php echo esc_html($item_status === 'ativo' ? __('Ativo', 'smtt-intranet') : __('Inativo', 'smtt-intranet')); ?></span>
                                    </div>

                                    <?php if ($thumb) : ?>
                                        <div style="margin-bottom:12px;">
                                            <img src="<?php echo esc_url($thumb); ?>" style="width:100%;height:auto;border:1px solid #ddd;">
                                        </div>
                                    <?php endif; ?>

                                    <h3><?php echo esc_html(get_the_title($item->ID)); ?></h3>

                                    <?php if (!empty($subtitle)) : ?>
                                        <div class="smtt-meta-line"><strong><?php echo esc_html($subtitle); ?></strong></div>
                                    <?php endif; ?>

                                    <p><?php echo esc_html($summary); ?></p>

                                    <div class="smtt-meta-line"><strong><?php echo esc_html__('Formato:', 'smtt-intranet'); ?></strong> <?php echo esc_html($this->get_mural_type_label($display_type)); ?></div>

                                    <?php if (!is_wp_error($sector_terms) && !empty($sector_terms)) : ?>
                                        <div class="smtt-meta-line"><strong><?php echo esc_html__('Setor:', 'smtt-intranet'); ?></strong> <?php echo esc_html($sector_terms[0]); ?></div>
                                    <?php endif; ?>

                                    <?php if (!is_wp_error($category_terms) && !empty($category_terms)) : ?>
                                        <div class="smtt-meta-line"><strong><?php echo esc_html__('Categoria:', 'smtt-intranet'); ?></strong> <?php echo esc_html($category_terms[0]); ?></div>
                                    <?php endif; ?>

                                    <div class="smtt-access-actions">
                                        <a class="button" href="<?php echo esc_url(get_edit_post_link($item->ID)); ?>">
                                            <?php echo esc_html__('Editar', 'smtt-intranet'); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="smtt-empty-state">
                                <p><?php echo esc_html__('Nenhum item encontrado no mural interno.', 'smtt-intranet'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
        <?php
    }

    public function render_knowledge_base_placeholder() {
        if (!current_user_can('smtt_manage_knowledge_base')) {
            wp_die(esc_html__('Você não tem permissão para acessar esta página.', 'smtt-intranet'));
        }

        $search   = isset($_GET['smtt_kb_search']) ? sanitize_text_field(wp_unslash($_GET['smtt_kb_search'])) : '';
        $status   = isset($_GET['smtt_kb_status']) ? sanitize_text_field(wp_unslash($_GET['smtt_kb_status'])) : '';
        $sector   = isset($_GET['smtt_kb_sector']) ? intval($_GET['smtt_kb_sector']) : 0;
        $category = isset($_GET['smtt_kb_category']) ? intval($_GET['smtt_kb_category']) : 0;

        $items = get_posts(array(
            'post_type'      => 'smtt_kb_article',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_key'       => '_smtt_sort_order',
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
        ));

        $items = array_values(array_filter($items, function($item) use ($search, $status, $sector, $category) {
            $summary     = (string) get_post_meta($item->ID, '_smtt_summary', true);
            $keywords    = (string) get_post_meta($item->ID, '_smtt_keywords', true);
            $item_status = (string) get_post_meta($item->ID, '_smtt_status', true);

            if ($status && $item_status !== $status) {
                return false;
            }

            if ($sector > 0) {
                $terms = wp_get_object_terms($item->ID, 'smtt_sector', array('fields' => 'ids'));
                $terms = is_wp_error($terms) ? array() : array_map('intval', $terms);

                if (empty($terms) || !in_array($sector, $terms, true)) {
                    return false;
                }
            }

            if ($category > 0) {
                $terms = wp_get_object_terms($item->ID, 'smtt_kb_category', array('fields' => 'ids'));
                $terms = is_wp_error($terms) ? array() : array_map('intval', $terms);

                if (empty($terms) || !in_array($category, $terms, true)) {
                    return false;
                }
            }

            if ($search) {
                $content = wp_strip_all_tags((string) get_post_field('post_content', $item->ID));

                $haystack = strtolower(
                    get_the_title($item->ID) . ' ' .
                    $summary . ' ' .
                    $keywords . ' ' .
                    $content
                );

                if (strpos($haystack, strtolower($search)) === false) {
                    return false;
                }
            }

            return true;
        }));
        ?>
        <div class="wrap smtt-intranet-admin">
        <?php $this->render_dashboard_back_button(); ?>
            <div class="smtt-topbar">
                <div>
                    <h1><?php echo esc_html__('Base Interna', 'smtt-intranet'); ?></h1>
                    <p class="smtt-subtitle"><?php echo esc_html__('Procedimentos, manuais, FAQs e conhecimento institucional da SMTT.', 'smtt-intranet'); ?></p>
                </div>
            </div>

            <form method="get" class="smtt-filter-bar">
                <input type="hidden" name="page" value="smtt-intranet-knowledge-base" />

                <input type="text" name="smtt_kb_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php echo esc_attr__('Buscar artigos...', 'smtt-intranet'); ?>" />

                <select name="smtt_kb_status">
                    <option value=""><?php echo esc_html__('Todos os status', 'smtt-intranet'); ?></option>
                    <option value="ativo" <?php selected($status, 'ativo'); ?>><?php echo esc_html__('Ativo', 'smtt-intranet'); ?></option>
                    <option value="inativo" <?php selected($status, 'inativo'); ?>><?php echo esc_html__('Inativo', 'smtt-intranet'); ?></option>
                </select>

                <?php
                wp_dropdown_categories(array(
                    'taxonomy'         => 'smtt_sector',
                    'name'             => 'smtt_kb_sector',
                    'orderby'          => 'name',
                    'show_option_all'  => __('Todos os setores', 'smtt-intranet'),
                    'hide_empty'       => false,
                    'hierarchical'     => true,
                    'selected'         => $sector,
                ));
                ?>

                <?php
                wp_dropdown_categories(array(
                    'taxonomy'         => 'smtt_kb_category',
                    'name'             => 'smtt_kb_category',
                    'orderby'          => 'name',
                    'show_option_all'  => __('Todas as categorias', 'smtt-intranet'),
                    'hide_empty'       => false,
                    'hierarchical'     => true,
                    'selected'         => $category,
                ));
                ?>

                <button type="submit" class="button button-primary"><?php echo esc_html__('Filtrar', 'smtt-intranet'); ?></button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=smtt-intranet-knowledge-base')); ?>" class="button"><?php echo esc_html__('Limpar', 'smtt-intranet'); ?></a>
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=smtt_kb_article')); ?>" class="button button-primary"><?php echo esc_html__('Adicionar artigo', 'smtt-intranet'); ?></a>
                <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=smtt_kb_category&post_type=smtt_kb_article')); ?>" class="button"><?php echo esc_html__('Gerenciar categorias', 'smtt-intranet'); ?></a>
            </form>

            <div class="smtt-main-grid smtt-main-grid-single">
                <section class="smtt-panel">
                    <div class="smtt-access-grid">
                        <?php if (!empty($items)) : ?>
                            <?php foreach ($items as $item) : ?>
                                <?php
                                $summary     = get_post_meta($item->ID, '_smtt_summary', true);
                                $keywords    = get_post_meta($item->ID, '_smtt_keywords', true);
                                $item_status = get_post_meta($item->ID, '_smtt_status', true);

                                $sector_terms   = wp_get_object_terms($item->ID, 'smtt_sector', array('fields' => 'names'));
                                $category_terms = wp_get_object_terms($item->ID, 'smtt_kb_category', array('fields' => 'names'));
                                ?>
                                <div class="smtt-access-card">
                                    <div class="smtt-access-card-top">
                                        <div class="smtt-module-icon">
                                            <span class="dashicons dashicons-book-alt"></span>
                                        </div>
                                        <span class="smtt-module-status"><?php echo esc_html($item_status === 'ativo' ? __('Ativo', 'smtt-intranet') : __('Inativo', 'smtt-intranet')); ?></span>
                                    </div>

                                    <h3><?php echo esc_html(get_the_title($item->ID)); ?></h3>
                                    <p><?php echo esc_html($summary); ?></p>

                                    <?php if (!is_wp_error($sector_terms) && !empty($sector_terms)) : ?>
                                        <div class="smtt-meta-line"><strong><?php echo esc_html__('Setor:', 'smtt-intranet'); ?></strong> <?php echo esc_html($sector_terms[0]); ?></div>
                                    <?php endif; ?>

                                    <?php if (!is_wp_error($category_terms) && !empty($category_terms)) : ?>
                                        <div class="smtt-meta-line"><strong><?php echo esc_html__('Categoria:', 'smtt-intranet'); ?></strong> <?php echo esc_html($category_terms[0]); ?></div>
                                    <?php endif; ?>

                                    <?php if (!empty($keywords)) : ?>
                                        <div class="smtt-meta-line"><strong><?php echo esc_html__('Palavras-chave:', 'smtt-intranet'); ?></strong> <?php echo esc_html($keywords); ?></div>
                                    <?php endif; ?>

                                    <div class="smtt-access-actions">
                                        <a class="button button-secondary" href="<?php echo esc_url(get_edit_post_link($item->ID)); ?>">
                                            <?php echo esc_html__('Editar', 'smtt-intranet'); ?>
                                        </a>
                                        <a class="button" href="<?php echo esc_url(get_permalink($item->ID)); ?>" target="_blank" rel="noopener noreferrer">
                                            <?php echo esc_html__('Visualizar', 'smtt-intranet'); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="smtt-empty-state">
                                <p><?php echo esc_html__('Nenhum artigo encontrado na base interna.', 'smtt-intranet'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
        <?php
    }

    public function render_access_center() {
        if (!current_user_can('smtt_manage_access_center')) {
            wp_die(esc_html__('Você não tem permissão para gerenciar a Central de Acessos.', 'smtt-intranet'));
        }

        $permission_notice = '';
        $permission_notice_type = 'success';
        $permission_sector = isset($_REQUEST['smtt_permission_sector']) ? intval($_REQUEST['smtt_permission_sector']) : 0;

        if (
            isset($_POST['smtt_save_sector_permissions']) &&
            isset($_POST['smtt_access_permissions_nonce']) &&
            wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['smtt_access_permissions_nonce'])), 'smtt_save_access_permissions')
        ) {
            $permission_sector = isset($_POST['smtt_permission_sector']) ? intval($_POST['smtt_permission_sector']) : 0;

            if (!$permission_sector || !term_exists($permission_sector, 'smtt_sector')) {
                $permission_notice = __('Selecione um setor válido para salvar as permissões.', 'smtt-intranet');
                $permission_notice_type = 'error';
            } else {
                $selected_systems = isset($_POST['smtt_permission_systems']) && is_array($_POST['smtt_permission_systems'])
                    ? array_values(array_filter(array_map('intval', wp_unslash($_POST['smtt_permission_systems']))))
                    : array();
                $selected_folders = isset($_POST['smtt_permission_folders']) && is_array($_POST['smtt_permission_folders'])
                    ? array_values(array_filter(array_map('intval', wp_unslash($_POST['smtt_permission_folders']))))
                    : array();

                $all_system_ids = get_posts(array(
                    'post_type'      => 'smtt_internal_system',
                    'posts_per_page' => -1,
                    'post_status'    => array('publish', 'draft', 'private'),
                    'fields'         => 'ids',
                ));

                $all_folder_ids = get_posts(array(
                    'post_type'      => 'smtt_sector_folder',
                    'posts_per_page' => -1,
                    'post_status'    => array('publish', 'draft', 'private'),
                    'fields'         => 'ids',
                ));

                foreach ($all_system_ids as $item_id) {
                    if (in_array((int) $item_id, $selected_systems, true)) {
                        wp_set_object_terms((int) $item_id, array($permission_sector), 'smtt_sector', true);
                    } else {
                        wp_remove_object_terms((int) $item_id, array($permission_sector), 'smtt_sector');
                    }
                }

                foreach ($all_folder_ids as $item_id) {
                    if (in_array((int) $item_id, $selected_folders, true)) {
                        wp_set_object_terms((int) $item_id, array($permission_sector), 'smtt_sector', true);
                    } else {
                        wp_remove_object_terms((int) $item_id, array($permission_sector), 'smtt_sector');
                    }
                }

                $public_module = new SMTT_Intranet_Public();
                $public_module->clear_home_cache();

                $permission_notice = __('Permissões do setor atualizadas com sucesso.', 'smtt-intranet');
            }
        }

        $current_user_id = get_current_user_id();
        $user_sector_id  = (int) get_user_meta($current_user_id, 'smtt_user_sector', true);
        $is_admin        = current_user_can('smtt_manage_access_center');

        $search = isset($_GET['smtt_search']) ? sanitize_text_field(wp_unslash($_GET['smtt_search'])) : '';
        $status = isset($_GET['smtt_status']) ? sanitize_text_field(wp_unslash($_GET['smtt_status'])) : '';
        $sector = isset($_GET['smtt_sector_filter']) ? intval($_GET['smtt_sector_filter']) : 0;

        if (!$is_admin) {
            $sector = $user_sector_id;
        }

        $sector_folders = get_posts(array(
            'post_type'      => 'smtt_sector_folder',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_key'       => '_smtt_sort_order',
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
        ));

        $internal_systems = get_posts(array(
            'post_type'      => 'smtt_internal_system',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_key'       => '_smtt_sort_order',
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
        ));

        $sector_folders   = $this->filter_access_items($sector_folders, $search, $status, 'folder', $sector, $is_admin, $user_sector_id);
        $internal_systems = $this->filter_access_items($internal_systems, $search, $status, 'system', $sector, $is_admin, $user_sector_id);

        $featured_folders = array_filter($sector_folders, array($this, 'is_featured_item'));
        $featured_systems = array_filter($internal_systems, array($this, 'is_featured_item'));
        ?>
        <div class="wrap smtt-intranet-admin">
        <?php $this->render_dashboard_back_button(); ?>
            <div class="smtt-topbar">
                <div>
                    <h1><?php echo esc_html__('Central de Acessos', 'smtt-intranet'); ?></h1>
                    <p class="smtt-subtitle"><?php echo esc_html__('Cadastre sistemas e pastas e defina, sem alterar código, quais setores podem acessar cada item.', 'smtt-intranet'); ?></p>
                </div>
            </div>

            <?php if ($permission_notice) : ?>
                <div class="notice notice-<?php echo esc_attr($permission_notice_type); ?> is-dismissible"><p><?php echo esc_html($permission_notice); ?></p></div>
            <?php endif; ?>

            <div class="smtt-main-grid smtt-main-grid-single">
                <section class="smtt-panel smtt-access-permissions-panel">
                    <div class="smtt-panel-header smtt-panel-header-flex">
                        <div>
                            <h2><?php echo esc_html__('Permissões por setor', 'smtt-intranet'); ?></h2>
                            <p><?php echo esc_html__('Selecione um setor e marque os sistemas e pastas que ele poderá abrir no front-end.', 'smtt-intranet'); ?></p>
                        </div>
                    </div>

                    <form method="post" class="smtt-access-permissions-form">
                        <?php wp_nonce_field('smtt_save_access_permissions', 'smtt_access_permissions_nonce'); ?>

                        <div class="smtt-permission-sector-row">
                            <label for="smtt_permission_sector"><strong><?php echo esc_html__('Setor', 'smtt-intranet'); ?></strong></label>
                            <?php
                            wp_dropdown_categories(array(
                                'taxonomy'         => 'smtt_sector',
                                'name'             => 'smtt_permission_sector',
                                'id'               => 'smtt_permission_sector',
                                'orderby'          => 'name',
                                'show_option_none' => __('Selecione um setor', 'smtt-intranet'),
                                'option_none_value'=> '0',
                                'hide_empty'       => false,
                                'hierarchical'     => true,
                                'selected'         => $permission_sector,
                            ));
                            ?>
                            <button type="submit" name="smtt_load_sector_permissions" value="1" class="button"><?php echo esc_html__('Carregar', 'smtt-intranet'); ?></button>
                        </div>

                        <?php if ($permission_sector) : ?>
                            <?php
                            $permission_systems = get_posts(array(
                                'post_type'      => 'smtt_internal_system',
                                'posts_per_page' => -1,
                                'post_status'    => 'publish',
                                'meta_key'       => '_smtt_sort_order',
                                'orderby'        => 'meta_value_num',
                                'order'          => 'ASC',
                            ));
                            $permission_folders = get_posts(array(
                                'post_type'      => 'smtt_sector_folder',
                                'posts_per_page' => -1,
                                'post_status'    => 'publish',
                                'meta_key'       => '_smtt_sort_order',
                                'orderby'        => 'meta_value_num',
                                'order'          => 'ASC',
                            ));
                            ?>
                            <div class="smtt-permission-columns">
                                <div class="smtt-permission-box">
                                    <h3><?php echo esc_html__('Sistemas permitidos', 'smtt-intranet'); ?></h3>
                                    <div class="smtt-permission-checklist">
                                        <?php foreach ($permission_systems as $permission_item) : ?>
                                            <?php $has_sector = has_term($permission_sector, 'smtt_sector', $permission_item->ID); ?>
                                            <label>
                                                <input type="checkbox" name="smtt_permission_systems[]" value="<?php echo esc_attr($permission_item->ID); ?>" <?php checked($has_sector); ?>>
                                                <span><?php echo esc_html(get_the_title($permission_item->ID)); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="smtt-permission-box">
                                    <h3><?php echo esc_html__('Pastas permitidas', 'smtt-intranet'); ?></h3>
                                    <div class="smtt-permission-checklist">
                                        <?php foreach ($permission_folders as $permission_item) : ?>
                                            <?php $has_sector = has_term($permission_sector, 'smtt_sector', $permission_item->ID); ?>
                                            <label>
                                                <input type="checkbox" name="smtt_permission_folders[]" value="<?php echo esc_attr($permission_item->ID); ?>" <?php checked($has_sector); ?>>
                                                <span><?php echo esc_html(get_the_title($permission_item->ID)); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <p class="submit"><button type="submit" name="smtt_save_sector_permissions" value="1" class="button button-primary"><?php echo esc_html__('Salvar permissões do setor', 'smtt-intranet'); ?></button></p>
                        <?php else : ?>
                            <p class="description"><?php echo esc_html__('Selecione um setor e clique em Carregar para configurar os acessos.', 'smtt-intranet'); ?></p>
                        <?php endif; ?>
                    </form>
                </section>
            </div>

            <form method="get" class="smtt-filter-bar">
                <input type="hidden" name="page" value="smtt-intranet-access-center" />

                <input type="text" name="smtt_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php echo esc_attr__('Buscar por nome, setor ou descrição...', 'smtt-intranet'); ?>" />

                <select name="smtt_status">
                    <option value=""><?php echo esc_html__('Todos os status', 'smtt-intranet'); ?></option>
                    <option value="ativo" <?php selected($status, 'ativo'); ?>><?php echo esc_html__('Ativo', 'smtt-intranet'); ?></option>
                    <option value="manutencao" <?php selected($status, 'manutencao'); ?>><?php echo esc_html__('Em manutenção', 'smtt-intranet'); ?></option>
                    <option value="inativo" <?php selected($status, 'inativo'); ?>><?php echo esc_html__('Inativo', 'smtt-intranet'); ?></option>
                </select>

                <?php if ($is_admin) : ?>
                    <?php
                    wp_dropdown_categories(array(
                        'taxonomy'         => 'smtt_sector',
                        'name'             => 'smtt_sector_filter',
                        'orderby'          => 'name',
                        'show_option_all'  => __('Todos os setores', 'smtt-intranet'),
                        'hide_empty'       => false,
                        'hierarchical'     => true,
                        'selected'         => $sector,
                    ));
                    ?>
                <?php endif; ?>

                <button type="submit" class="button button-primary"><?php echo esc_html__('Filtrar', 'smtt-intranet'); ?></button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=smtt-intranet-access-center')); ?>" class="button"><?php echo esc_html__('Limpar', 'smtt-intranet'); ?></a>
            </form>

            <?php if (!empty($featured_folders) || !empty($featured_systems)) : ?>
                <div class="smtt-main-grid smtt-main-grid-single">
                    <section class="smtt-panel">
                        <div class="smtt-panel-header">
                            <h2><?php echo esc_html__('Acessos em destaque', 'smtt-intranet'); ?></h2>
                        </div>

                        <div class="smtt-access-grid">
                            <?php foreach ($featured_folders as $item) : ?>
                                <?php $this->render_folder_card($item, true); ?>
                            <?php endforeach; ?>

                            <?php foreach ($featured_systems as $item) : ?>
                                <?php $this->render_system_card($item, true); ?>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
            <?php endif; ?>

            <div class="smtt-main-grid smtt-main-grid-single">
                <section class="smtt-panel">
                    <div class="smtt-panel-header smtt-panel-header-flex">
                        <h2><?php echo esc_html__('Pastas dos Setores', 'smtt-intranet'); ?></h2>
                        <a href="<?php echo esc_url(admin_url('post-new.php?post_type=smtt_sector_folder')); ?>" class="button button-primary">
                            <?php echo esc_html__('Adicionar pasta', 'smtt-intranet'); ?>
                        </a>
                    </div>

                    <div class="smtt-access-grid">
                        <?php if (!empty($sector_folders)) : ?>
                            <?php foreach ($sector_folders as $item) : ?>
                                <?php $this->render_folder_card($item); ?>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="smtt-empty-state">
                                <p><?php echo esc_html__('Nenhuma pasta de setor encontrada.', 'smtt-intranet'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <div class="smtt-main-grid smtt-main-grid-single">
                <section class="smtt-panel">
                    <div class="smtt-panel-header smtt-panel-header-flex">
                        <h2><?php echo esc_html__('Sistemas Internos', 'smtt-intranet'); ?></h2>
                        <a href="<?php echo esc_url(admin_url('post-new.php?post_type=smtt_internal_system')); ?>" class="button button-primary">
                            <?php echo esc_html__('Adicionar sistema', 'smtt-intranet'); ?>
                        </a>
                    </div>

                    <div class="smtt-access-grid">
                        <?php if (!empty($internal_systems)) : ?>
                            <?php foreach ($internal_systems as $item) : ?>
                                <?php $this->render_system_card($item); ?>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="smtt-empty-state">
                                <p><?php echo esc_html__('Nenhum sistema interno encontrado.', 'smtt-intranet'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
        <?php
    }

    public function render_announcements_placeholder() {
    if (!current_user_can('smtt_manage_announcements')) {
        wp_die(esc_html__('Você não tem permissão para acessar esta página.', 'smtt-intranet'));
    }

    $announcements = new SMTT_Intranet_Announcements();

    $refresh_notice = '';
    $refresh_notice_type = 'success';

    if (
        isset($_GET['smtt_refresh_announcements']) &&
        $_GET['smtt_refresh_announcements'] === '1'
    ) {
        check_admin_referer('smtt_refresh_announcements');
        $announcements->request_refresh_now(1);
        $refresh_notice = __('Sincronização solicitada em segundo plano. Você pode continuar usando o painel; atualize esta tela em alguns segundos para conferir o resultado.', 'smtt-intranet');
        $refresh_notice_type = 'info';
    }

    $result = $announcements->get_latest_posts(3);
    $site_url = $announcements->get_site_url();
    $refresh_status = $announcements->get_refresh_status();
    ?>
    <div class="wrap smtt-intranet-admin">
        <?php $this->render_dashboard_back_button(); ?>
        <?php if ($refresh_notice) : ?>
            <div class="notice notice-<?php echo esc_attr($refresh_notice_type); ?> is-dismissible"><p><?php echo esc_html($refresh_notice); ?></p></div>
        <?php endif; ?>
        <div class="smtt-topbar">
            <div>
                <h1><?php echo esc_html__('Notícias', 'smtt-intranet'); ?></h1>
                <p class="smtt-subtitle"><?php echo esc_html__('Integração com o site institucional externo para exibir as 3 notícias mais recentes.', 'smtt-intranet'); ?></p>
            </div>
        </div>

        <div class="smtt-main-grid smtt-main-grid-single">
            <section class="smtt-panel">
                <div class="smtt-panel-header">
                    <h2><?php echo esc_html__('Configuração da origem', 'smtt-intranet'); ?></h2>
                </div>

                <form method="post" action="options.php">
                    <?php settings_fields('smtt_announcements_settings'); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="smtt_announcements_site_url"><?php echo esc_html__('URL do site externo', 'smtt-intranet'); ?></label>
                            </th>
                            <td>
                                <input
                                    type="url"
                                    name="<?php echo esc_attr(SMTT_Intranet_Announcements::OPTION_SITE_URL); ?>"
                                    id="smtt_announcements_site_url"
                                    class="regular-text"
                                    value="<?php echo esc_attr($site_url); ?>"
                                    placeholder="https://smtt.aracaju.se.gov.br"
                                />
                                <p class="description">
                                    <?php echo esc_html__('Informe a URL base do portal. Ex.: https://smtt.aracaju.se.gov.br. O plugin usa a página pública de Notícias como fonte principal e mantém REST/RSS como fallback.', 'smtt-intranet'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button(__('Salvar configuração', 'smtt-intranet')); ?>
                </form>
            </section>
        </div>

        <div class="smtt-main-grid smtt-main-grid-single">
            <section class="smtt-panel">
                <div class="smtt-panel-header smtt-panel-header-flex">
                    <h2><?php echo esc_html__('Pré-visualização das 3 notícias', 'smtt-intranet'); ?></h2>
                    <a
                        href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=smtt-intranet-announcements&smtt_refresh_announcements=1'), 'smtt_refresh_announcements')); ?>"
                        class="button"
                    >
                        <?php echo esc_html__('Atualizar agora', 'smtt-intranet'); ?>
                    </a>
                </div>

                <?php if (!empty($result['success'])) : ?>
                    <div style="margin-bottom:16px;display:flex;gap:18px;flex-wrap:wrap;">
                        <span class="smtt-meta-line">
                            <strong><?php echo esc_html__('Origem:', 'smtt-intranet'); ?></strong>
                            <?php echo esc_html(strtoupper($result['source'])); ?>
                        </span>
                        <?php if (!empty($result['updated_at'])) : ?>
                            <span class="smtt-meta-line">
                                <strong><?php echo esc_html__('Última sincronização:', 'smtt-intranet'); ?></strong>
                                <?php echo esc_html(date_i18n('d/m/Y H:i', (int) $result['updated_at'])); ?>
                            </span>
                        <?php endif; ?>
                        <span class="smtt-meta-line">
                            <strong><?php echo esc_html__('Atualização automática:', 'smtt-intranet'); ?></strong>
                            <?php echo esc_html__('a cada 15 minutos, em segundo plano', 'smtt-intranet'); ?>
                        </span>
                        <span class="smtt-meta-line">
                            <strong><?php echo esc_html__('Imagens:', 'smtt-intranet'); ?></strong>
                            <?php echo esc_html__('carregadas sem bloquear a sincronização', 'smtt-intranet'); ?>
                        </span>
                        <?php if (!empty($refresh_status['last_attempt'])) : ?>
                            <span class="smtt-meta-line">
                                <strong><?php echo esc_html__('Última tentativa:', 'smtt-intranet'); ?></strong>
                                <?php echo esc_html(date_i18n('d/m/Y H:i', (int) $refresh_status['last_attempt'])); ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($refresh_status['last_error'])) : ?>
                            <span class="smtt-meta-line" style="color:#b45309;max-width:100%;">
                                <strong><?php echo esc_html__('Último aviso:', 'smtt-intranet'); ?></strong>
                                <?php echo esc_html($refresh_status['last_error']); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="smtt-access-grid">
                        <?php foreach ($result['items'] as $item) : ?>
                            <div class="smtt-access-card">
                                <div class="smtt-access-card-top">
                                    <div class="smtt-module-icon">
                                        <span class="dashicons dashicons-megaphone"></span>
                                    </div>
                                    <span class="smtt-module-status"><?php echo esc_html__('Externo', 'smtt-intranet'); ?></span>
                                </div>

                                <?php
                                $admin_news_image = !empty($item['image'])
                                    ? $announcements->get_news_image_url($item['image'])
                                    : (!empty($item['image_local']) ? esc_url_raw($item['image_local']) : '');
                                ?>
                                <?php if ($admin_news_image) : ?>
                                    <div style="margin-bottom:12px;">
                                        <img src="<?php echo esc_url($admin_news_image); ?>" style="width:100%;height:auto;border:1px solid #ddd;" alt="">
                                    </div>
                                <?php endif; ?>

                                <h3><?php echo esc_html($item['title']); ?></h3>

                                <?php if (!empty($item['date'])) : ?>
                                    <div class="smtt-meta-line">
                                        <strong><?php echo esc_html__('Data:', 'smtt-intranet'); ?></strong>
                                        <?php echo esc_html(date_i18n('d/m/Y', strtotime($item['date']))); ?>
                                    </div>
                                <?php endif; ?>

                                <p>
                                    <?php
                                    $excerpt = wp_trim_words((string) $item['excerpt'], 22, '...');
                                    echo esc_html($excerpt);
                                    ?>
                                </p>

                                <div class="smtt-access-actions">
                                    <?php if (!empty($item['link'])) : ?>
                                        <a class="button button-primary" href="<?php echo esc_url($item['link']); ?>" target="_blank" rel="noopener noreferrer">
                                            <?php echo esc_html__('Ler no portal', 'smtt-intranet'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="smtt-empty-state">
                        <p><?php echo esc_html($result['message']); ?></p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
    <?php
}

    public function render_documents_placeholder() {

        if (!current_user_can('smtt_manage_documents')) {
            wp_die(esc_html__('Você não tem permissão para acessar esta página.', 'smtt-intranet'));
        }

        $search   = isset($_GET['smtt_doc_search']) ? sanitize_text_field(wp_unslash($_GET['smtt_doc_search'])) : '';
        $status   = isset($_GET['smtt_doc_status']) ? sanitize_text_field(wp_unslash($_GET['smtt_doc_status'])) : '';
        $sector   = isset($_GET['smtt_doc_sector']) ? intval($_GET['smtt_doc_sector']) : 0;
        $category = isset($_GET['smtt_doc_category']) ? intval($_GET['smtt_doc_category']) : 0;

        $documents = get_posts(array(
            'post_type'      => 'smtt_document',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_key'       => '_smtt_sort_order',
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
        ));

        $documents = array_values(array_filter($documents, function($item) use ($search, $status, $sector, $category) {
            $description = (string) get_post_meta($item->ID, '_smtt_description', true);
            $item_status = (string) get_post_meta($item->ID, '_smtt_status', true);

            if ($status && $item_status !== $status) {
                return false;
            }

            if ($sector > 0) {
                $terms = wp_get_object_terms($item->ID, 'smtt_sector', array('fields' => 'ids'));
                $terms = is_wp_error($terms) ? array() : array_map('intval', $terms);

                if (empty($terms) || !in_array($sector, $terms, true)) {
                    return false;
                }
            }

            if ($category > 0) {
                $terms = wp_get_object_terms($item->ID, 'smtt_document_category', array('fields' => 'ids'));
                $terms = is_wp_error($terms) ? array() : array_map('intval', $terms);

                if (empty($terms) || !in_array($category, $terms, true)) {
                    return false;
                }
            }

            if ($search) {
                $haystack = strtolower(get_the_title($item->ID) . ' ' . $description);
                if (strpos($haystack, strtolower($search)) === false) {
                    return false;
                }
            }

            return true;
        }));
        ?>
        <div class="wrap smtt-intranet-admin">
            <?php $this->render_dashboard_back_button(); ?>
            <div class="smtt-topbar">
                <div>
                    <h1><?php echo esc_html__('Documentos', 'smtt-intranet'); ?></h1>
                    <p class="smtt-subtitle"><?php echo esc_html__('Gerencie os documentos internos da intranet.', 'smtt-intranet'); ?></p>
                </div>
            </div>

            <form method="get" class="smtt-filter-bar">
                <input type="hidden" name="page" value="smtt-intranet-documents" />

                <input type="text" name="smtt_doc_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php echo esc_attr__('Buscar documentos...', 'smtt-intranet'); ?>" />

                <select name="smtt_doc_status">
                    <option value=""><?php echo esc_html__('Todos os status', 'smtt-intranet'); ?></option>
                    <option value="ativo" <?php selected($status, 'ativo'); ?>><?php echo esc_html__('Ativo', 'smtt-intranet'); ?></option>
                    <option value="inativo" <?php selected($status, 'inativo'); ?>><?php echo esc_html__('Inativo', 'smtt-intranet'); ?></option>
                </select>

                <?php
                wp_dropdown_categories(array(
                    'taxonomy'         => 'smtt_sector',
                    'name'             => 'smtt_doc_sector',
                    'orderby'          => 'name',
                    'show_option_all'  => __('Todos os setores', 'smtt-intranet'),
                    'hide_empty'       => false,
                    'hierarchical'     => true,
                    'selected'         => $sector,
                ));
                ?>

                <?php
                wp_dropdown_categories(array(
                    'taxonomy'         => 'smtt_document_category',
                    'name'             => 'smtt_doc_category',
                    'orderby'          => 'name',
                    'show_option_all'  => __('Todas as categorias', 'smtt-intranet'),
                    'hide_empty'       => false,
                    'hierarchical'     => true,
                    'selected'         => $category,
                ));
                ?>

                <button type="submit" class="button button-primary"><?php echo esc_html__('Filtrar', 'smtt-intranet'); ?></button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=smtt-intranet-documents')); ?>" class="button"><?php echo esc_html__('Limpar', 'smtt-intranet'); ?></a>
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=smtt_document')); ?>" class="button button-primary"><?php echo esc_html__('Adicionar documento', 'smtt-intranet'); ?></a>
            </form>

            <div class="smtt-main-grid smtt-main-grid-single">
                <section class="smtt-panel">
                    <div class="smtt-access-grid">
                        <?php if (!empty($documents)) : ?>
                            <?php foreach ($documents as $item) : ?>
                                <?php
                                $description    = get_post_meta($item->ID, '_smtt_description', true);
                                $document_type  = get_post_meta($item->ID, '_smtt_document_type', true);
                                $file_url       = get_post_meta($item->ID, '_smtt_file_url', true);
                                $external_url   = get_post_meta($item->ID, '_smtt_external_url', true);
                                $status_meta    = get_post_meta($item->ID, '_smtt_status', true);

                                $sector_terms   = wp_get_object_terms($item->ID, 'smtt_sector', array('fields' => 'names'));
                                $category_terms = wp_get_object_terms($item->ID, 'smtt_document_category', array('fields' => 'names'));
                                ?>
                                <div class="smtt-access-card">
                                    <div class="smtt-access-card-top">
                                        <div class="smtt-module-icon">
                                            <span class="dashicons dashicons-media-document"></span>
                                        </div>
                                        <span class="smtt-module-status"><?php echo esc_html($status_meta === 'ativo' ? __('Ativo', 'smtt-intranet') : __('Inativo', 'smtt-intranet')); ?></span>
                                    </div>

                                    <h3><?php echo esc_html(get_the_title($item->ID)); ?></h3>
                                    <p><?php echo esc_html($description); ?></p>

                                    <?php if (!is_wp_error($sector_terms) && !empty($sector_terms)) : ?>
                                        <div class="smtt-meta-line"><strong><?php echo esc_html__('Setor:', 'smtt-intranet'); ?></strong> <?php echo esc_html($sector_terms[0]); ?></div>
                                    <?php endif; ?>

                                    <?php if (!is_wp_error($category_terms) && !empty($category_terms)) : ?>
                                        <div class="smtt-meta-line"><strong><?php echo esc_html__('Categoria:', 'smtt-intranet'); ?></strong> <?php echo esc_html($category_terms[0]); ?></div>
                                    <?php endif; ?>

                                    <div class="smtt-meta-line"><strong><?php echo esc_html__('Tipo:', 'smtt-intranet'); ?></strong> <?php echo esc_html(ucfirst($document_type)); ?></div>

                                    <div class="smtt-access-actions">
                                        <?php if (!empty($file_url)) : ?>
                                            <a class="button button-secondary" href="<?php echo esc_url($file_url); ?>" target="_blank" rel="noopener noreferrer">
                                                <?php echo esc_html__('Abrir arquivo', 'smtt-intranet'); ?>
                                            </a>
                                        <?php endif; ?>

                                        <?php if (!empty($external_url)) : ?>
                                            <a class="button button-primary" href="<?php echo esc_url($external_url); ?>" target="_blank" rel="noopener noreferrer">
                                                <?php echo esc_html__('Abrir link', 'smtt-intranet'); ?>
                                            </a>
                                        <?php endif; ?>

                                        <a class="button" href="<?php echo esc_url(get_edit_post_link($item->ID)); ?>">
                                            <?php echo esc_html__('Editar', 'smtt-intranet'); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="smtt-empty-state">
                                <p><?php echo esc_html__('Nenhum documento encontrado.', 'smtt-intranet'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
        <?php
    }

    public function render_directory_placeholder() {
        $this->render_placeholder_page(
            __('Diretório', 'smtt-intranet'),
            __('Aqui vamos construir o diretório interno de setores, servidores e ramais.', 'smtt-intranet')
        );
    }

    public function render_ramais_page() {
        if (!current_user_can('smtt_manage_directory')) {
            wp_die(esc_html__('Você não tem permissão para acessar esta página.', 'smtt-intranet'));
        }

        $ramais_admin = new SMTT_Intranet_Ramais_Admin();
        $ramais_admin->render_page();
    }

    public function render_calendar_placeholder() {
        if (!current_user_can('smtt_manage_calendar')) {
            wp_die(esc_html__('Você não tem permissão para acessar esta página.', 'smtt-intranet'));
        }

        $search   = isset($_GET['smtt_agenda_search']) ? sanitize_text_field(wp_unslash($_GET['smtt_agenda_search'])) : '';
        $status   = isset($_GET['smtt_agenda_status']) ? sanitize_text_field(wp_unslash($_GET['smtt_agenda_status'])) : '';
        $type     = isset($_GET['smtt_agenda_type']) ? sanitize_text_field(wp_unslash($_GET['smtt_agenda_type'])) : '';
        $sector   = isset($_GET['smtt_agenda_sector']) ? intval($_GET['smtt_agenda_sector']) : 0;
        $space    = isset($_GET['smtt_agenda_space']) ? intval($_GET['smtt_agenda_space']) : 0;
        $category = isset($_GET['smtt_agenda_category']) ? intval($_GET['smtt_agenda_category']) : 0;
        $month    = isset($_GET['smtt_agenda_month']) ? sanitize_text_field(wp_unslash($_GET['smtt_agenda_month'])) : '';

        $items = get_posts(array(
            'post_type'      => 'smtt_agenda_item',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_key'       => '_smtt_event_date',
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
        ));

        $items = array_values(array_filter($items, function($item) use ($search, $status, $type, $sector, $space, $category, $month) {
            $item_type   = (string) get_post_meta($item->ID, '_smtt_item_type', true);
            $item_status = (string) get_post_meta($item->ID, '_smtt_status', true);
            $date        = (string) get_post_meta($item->ID, '_smtt_event_date', true);
            $description = (string) get_post_meta($item->ID, '_smtt_description', true);
            $responsible = (string) get_post_meta($item->ID, '_smtt_responsible_name', true);

            if ($status && $item_status !== $status) {
                return false;
            }

            if ($type && $item_type !== $type) {
                return false;
            }

            if ($month && strpos($date, $month) !== 0) {
                return false;
            }

            if ($sector > 0) {
                $terms = wp_get_object_terms($item->ID, 'smtt_sector', array('fields' => 'ids'));
                $terms = is_wp_error($terms) ? array() : array_map('intval', $terms);

                if (empty($terms) || !in_array($sector, $terms, true)) {
                    return false;
                }
            }

            if ($space > 0) {
                $terms = wp_get_object_terms($item->ID, 'smtt_agenda_space', array('fields' => 'ids'));
                $terms = is_wp_error($terms) ? array() : array_map('intval', $terms);

                if (empty($terms) || !in_array($space, $terms, true)) {
                    return false;
                }
            }

            if ($category > 0) {
                $terms = wp_get_object_terms($item->ID, 'smtt_agenda_category', array('fields' => 'ids'));
                $terms = is_wp_error($terms) ? array() : array_map('intval', $terms);

                if (empty($terms) || !in_array($category, $terms, true)) {
                    return false;
                }
            }

            if ($search) {
                $haystack = strtolower(
                    get_the_title($item->ID) . ' ' .
                    $description . ' ' .
                    $responsible
                );

                if (strpos($haystack, strtolower($search)) === false) {
                    return false;
                }
            }

            return true;
        }));
        ?>
        <div class="wrap smtt-intranet-admin">
        <?php $this->render_dashboard_back_button(); ?>
            <div class="smtt-topbar">
                <div>
                    <h1><?php echo esc_html__('Agenda', 'smtt-intranet'); ?></h1>
                    <p class="smtt-subtitle"><?php echo esc_html__('Eventos institucionais e reservas de espaços internos.', 'smtt-intranet'); ?></p>
                </div>
            </div>

            <form method="get" class="smtt-filter-bar">
                <input type="hidden" name="page" value="smtt-intranet-calendar" />

                <input type="text" name="smtt_agenda_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php echo esc_attr__('Buscar agenda...', 'smtt-intranet'); ?>" />

                <select name="smtt_agenda_type">
                    <option value=""><?php echo esc_html__('Todos os tipos', 'smtt-intranet'); ?></option>
                    <option value="evento" <?php selected($type, 'evento'); ?>><?php echo esc_html__('Evento', 'smtt-intranet'); ?></option>
                    <option value="reserva" <?php selected($type, 'reserva'); ?>><?php echo esc_html__('Reserva', 'smtt-intranet'); ?></option>
                </select>

                <select name="smtt_agenda_status">
                    <option value=""><?php echo esc_html__('Todos os status', 'smtt-intranet'); ?></option>
                    <option value="agendado" <?php selected($status, 'agendado'); ?>><?php echo esc_html__('Agendado', 'smtt-intranet'); ?></option>
                    <option value="cancelado" <?php selected($status, 'cancelado'); ?>><?php echo esc_html__('Cancelado', 'smtt-intranet'); ?></option>
                    <option value="concluido" <?php selected($status, 'concluido'); ?>><?php echo esc_html__('Concluído', 'smtt-intranet'); ?></option>
                </select>

                <input type="month" name="smtt_agenda_month" value="<?php echo esc_attr($month); ?>" />

                <?php
                wp_dropdown_categories(array(
                    'taxonomy'         => 'smtt_sector',
                    'name'             => 'smtt_agenda_sector',
                    'orderby'          => 'name',
                    'show_option_all'  => __('Todos os setores', 'smtt-intranet'),
                    'hide_empty'       => false,
                    'hierarchical'     => true,
                    'selected'         => $sector,
                ));
                ?>

                <?php
                wp_dropdown_categories(array(
                    'taxonomy'         => 'smtt_agenda_space',
                    'name'             => 'smtt_agenda_space',
                    'orderby'          => 'name',
                    'show_option_all'  => __('Todos os espaços', 'smtt-intranet'),
                    'hide_empty'       => false,
                    'hierarchical'     => true,
                    'selected'         => $space,
                ));
                ?>

                <?php
                wp_dropdown_categories(array(
                    'taxonomy'         => 'smtt_agenda_category',
                    'name'             => 'smtt_agenda_category',
                    'orderby'          => 'name',
                    'show_option_all'  => __('Todas as categorias', 'smtt-intranet'),
                    'hide_empty'       => false,
                    'hierarchical'     => true,
                    'selected'         => $category,
                ));
                ?>

                <button type="submit" class="button button-primary"><?php echo esc_html__('Filtrar', 'smtt-intranet'); ?></button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=smtt-intranet-calendar')); ?>" class="button"><?php echo esc_html__('Limpar', 'smtt-intranet'); ?></a>
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=smtt_agenda_item')); ?>" class="button button-primary"><?php echo esc_html__('Adicionar item', 'smtt-intranet'); ?></a>
                <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=smtt_agenda_space&post_type=smtt_agenda_item')); ?>" class="button"><?php echo esc_html__('Gerenciar espaços', 'smtt-intranet'); ?></a>
                <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=smtt_agenda_category&post_type=smtt_agenda_item')); ?>" class="button"><?php echo esc_html__('Gerenciar categorias', 'smtt-intranet'); ?></a>
            </form>

            <div class="smtt-main-grid smtt-main-grid-single">
                <section class="smtt-panel">
                    <div class="smtt-access-grid">
                        <?php if (!empty($items)) : ?>
                            <?php foreach ($items as $item) : ?>
                                <?php
                                $item_type   = get_post_meta($item->ID, '_smtt_item_type', true);
                                $description = get_post_meta($item->ID, '_smtt_description', true);
                                $responsible = get_post_meta($item->ID, '_smtt_responsible_name', true);
                                $date        = get_post_meta($item->ID, '_smtt_event_date', true);
                                $start_time  = get_post_meta($item->ID, '_smtt_start_time', true);
                                $end_time    = get_post_meta($item->ID, '_smtt_end_time', true);
                                $location    = get_post_meta($item->ID, '_smtt_location_text', true);
                                $item_status = get_post_meta($item->ID, '_smtt_status', true);

                                $sector_terms   = wp_get_object_terms($item->ID, 'smtt_sector', array('fields' => 'names'));
                                $space_terms    = wp_get_object_terms($item->ID, 'smtt_agenda_space', array('fields' => 'names'));
                                $category_terms = wp_get_object_terms($item->ID, 'smtt_agenda_category', array('fields' => 'names'));
                                ?>
                                <div class="smtt-access-card">
                                    <div class="smtt-access-card-top">
                                        <div class="smtt-module-icon">
                                            <span class="dashicons <?php echo esc_attr($item_type === 'reserva' ? 'dashicons-calendar-alt' : 'dashicons-megaphone'); ?>"></span>
                                        </div>
                                        <span class="smtt-module-status"><?php echo esc_html($this->get_agenda_status_label($item_status)); ?></span>
                                    </div>

                                    <h3><?php echo esc_html(get_the_title($item->ID)); ?></h3>
                                    <p><?php echo esc_html($description); ?></p>

                                    <div class="smtt-meta-line"><strong><?php echo esc_html__('Tipo:', 'smtt-intranet'); ?></strong> <?php echo esc_html($this->get_agenda_type_label($item_type)); ?></div>

                                    <?php if (!empty($date)) : ?>
                                        <div class="smtt-meta-line"><strong><?php echo esc_html__('Data:', 'smtt-intranet'); ?></strong> <?php echo esc_html(date_i18n('d/m/Y', strtotime($date))); ?></div>
                                    <?php endif; ?>

                                    <?php if (!empty($start_time) || !empty($end_time)) : ?>
                                        <div class="smtt-meta-line"><strong><?php echo esc_html__('Horário:', 'smtt-intranet'); ?></strong> <?php echo esc_html($start_time . (!empty($end_time) ? ' às ' . $end_time : '')); ?></div>
                                    <?php endif; ?>

                                    <?php if (!is_wp_error($sector_terms) && !empty($sector_terms)) : ?>
                                        <div class="smtt-meta-line"><strong><?php echo esc_html__('Setor:', 'smtt-intranet'); ?></strong> <?php echo esc_html($sector_terms[0]); ?></div>
                                    <?php endif; ?>

                                    <?php if (!is_wp_error($space_terms) && !empty($space_terms)) : ?>
                                        <div class="smtt-meta-line"><strong><?php echo esc_html__('Espaço:', 'smtt-intranet'); ?></strong> <?php echo esc_html($space_terms[0]); ?></div>
                                    <?php endif; ?>

                                    <?php if (!is_wp_error($category_terms) && !empty($category_terms)) : ?>
                                        <div class="smtt-meta-line"><strong><?php echo esc_html__('Categoria:', 'smtt-intranet'); ?></strong> <?php echo esc_html($category_terms[0]); ?></div>
                                    <?php endif; ?>

                                    <?php if (!empty($location)) : ?>
                                        <div class="smtt-meta-line"><strong><?php echo esc_html__('Local complementar:', 'smtt-intranet'); ?></strong> <?php echo esc_html($location); ?></div>
                                    <?php endif; ?>

                                    <?php if (!empty($responsible)) : ?>
                                        <div class="smtt-meta-line"><strong><?php echo esc_html__('Responsável:', 'smtt-intranet'); ?></strong> <?php echo esc_html($responsible); ?></div>
                                    <?php endif; ?>

                                    <div class="smtt-access-actions">
                                        <a class="button" href="<?php echo esc_url(get_edit_post_link($item->ID)); ?>">
                                            <?php echo esc_html__('Editar', 'smtt-intranet'); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="smtt-empty-state">
                                <p><?php echo esc_html__('Nenhum item encontrado na agenda.', 'smtt-intranet'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
        <?php
    }



    public function render_native_screen_navigation() {
        if (!is_admin() || !current_user_can('smtt_access_intranet')) {
            return;
        }

        if ($this->is_smtt_custom_page()) {
            return;
        }

        if (!function_exists('get_current_screen')) {
            return;
        }

        $screen = get_current_screen();

        if (!$screen) {
            return;
        }

        $context = $this->get_native_screen_navigation_context($screen);

        if (empty($context['module_title']) || empty($context['module_url'])) {
            return;
        }

        echo '<div class="notice notice-info" style="padding:12px 14px;">';
        $this->render_page_actions($context['module_title'], $context['module_url']);
        echo '</div>';
    }

    private function is_smtt_custom_page() {
        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        return strpos($page, 'smtt-intranet') === 0;
    }

    private function get_native_screen_navigation_context($screen) {
        $taxonomy  = isset($screen->taxonomy) ? (string) $screen->taxonomy : '';
        $post_type = isset($screen->post_type) ? (string) $screen->post_type : '';

        if ($post_type === '' && isset($_GET['post_type'])) {
            $post_type = sanitize_text_field(wp_unslash($_GET['post_type']));
        }

        if ($taxonomy === '' && isset($_GET['taxonomy'])) {
            $taxonomy = sanitize_text_field(wp_unslash($_GET['taxonomy']));
        }

        if (in_array($post_type, array('smtt_sector_folder', 'smtt_internal_system'), true)) {
            return array(
                'module_title' => __('Central de Acessos', 'smtt-intranet'),
                'module_url'   => admin_url('admin.php?page=smtt-intranet-access-center'),
            );
        }

        if ($taxonomy === 'smtt_sector' && $post_type === 'smtt_sector_folder') {
            return array(
                'module_title' => __('Central de Acessos', 'smtt-intranet'),
                'module_url'   => admin_url('admin.php?page=smtt-intranet-access-center'),
            );
        }

        if ($post_type === 'smtt_document' || $taxonomy === 'smtt_document_category') {
            return array(
                'module_title' => __('Documentos', 'smtt-intranet'),
                'module_url'   => admin_url('admin.php?page=smtt-intranet-documents'),
            );
        }

        if ($post_type === 'smtt_agenda_item' || in_array($taxonomy, array('smtt_agenda_space', 'smtt_agenda_category'), true)) {
            return array(
                'module_title' => __('Agenda', 'smtt-intranet'),
                'module_url'   => admin_url('admin.php?page=smtt-intranet-calendar'),
            );
        }

        if ($post_type === 'smtt_mural_item' || $taxonomy === 'smtt_mural_category') {
            return array(
                'module_title' => __('Mural Interno', 'smtt-intranet'),
                'module_url'   => admin_url('admin.php?page=smtt-intranet-mural'),
            );
        }

        if ($post_type === 'smtt_kb_article' || $taxonomy === 'smtt_kb_category') {
            return array(
                'module_title' => __('Base Interna', 'smtt-intranet'),
                'module_url'   => admin_url('admin.php?page=smtt-intranet-knowledge-base'),
            );
        }

        if ($post_type === 'smtt_birthday_person') {
            return array(
                'module_title' => __('Aniversariantes', 'smtt-intranet'),
                'module_url'   => admin_url('admin.php?page=smtt-intranet-birthdays'),
            );
        }

        return array();
    }

    private function render_dashboard_back_button() {
    ?>
    <div class="smtt-page-actions">
        <a href="<?php echo esc_url(admin_url('admin.php?page=smtt-intranet')); ?>" class="button button-secondary">
            <?php echo esc_html__('← Voltar ao Dashboard', 'smtt-intranet'); ?>
        </a>
    </div>
    <?php
}
    private function render_page_actions($module_title = '', $module_url = '') {
    ?>
    <div class="smtt-page-actions">
        <?php if (!empty($module_url)) : ?>
            <a href="<?php echo esc_url($module_url); ?>" class="button">
                <?php echo esc_html__('← Voltar para ', 'smtt-intranet') . esc_html($module_title); ?>
            </a>
        <?php endif; ?>

        <a href="<?php echo esc_url(admin_url('admin.php?page=smtt-intranet')); ?>" class="button button-secondary">
            <?php echo esc_html__('← Voltar ao Dashboard', 'smtt-intranet'); ?>
        </a>
    </div>
    <?php
}

    private function render_dashboard_card($title, $description, $icon, $url, $variant = 'default') {
        ?>
        <a class="smtt-dashboard-card smtt-dashboard-card-<?php echo esc_attr($variant); ?>" href="<?php echo esc_url($url); ?>">
            <div class="smtt-dashboard-card-icon">
                <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
            </div>
            <div class="smtt-dashboard-card-content">
                <h3><?php echo esc_html($title); ?></h3>
                <p><?php echo esc_html($description); ?></p>
            </div>
        </a>
        <?php
    }

    private function render_placeholder_page($title, $description) {
        if (!current_user_can('smtt_access_intranet')) {
            wp_die(esc_html__('Você não tem permissão para acessar esta página.', 'smtt-intranet'));
        }
        ?>
        <div class="wrap smtt-intranet-admin">
            <?php $this->render_dashboard_back_button(); ?>
            <div class="smtt-panel">
                <div class="smtt-panel-header">
                    <h1><?php echo esc_html($title); ?></h1>
                </div>
                <p><?php echo esc_html($description); ?></p>
            </div>
        </div>
        <?php
    }

    private function get_status_label($status) {
        $map = array(
            'ativo'      => __('Ativo', 'smtt-intranet'),
            'manutencao' => __('Em manutenção', 'smtt-intranet'),
            'inativo'    => __('Inativo', 'smtt-intranet'),
        );

        return isset($map[$status]) ? $map[$status] : __('Não definido', 'smtt-intranet');
    }

    private function get_access_type_label($type) {
        $map = array(
            'web'    => __('Web', 'smtt-intranet'),
            'rede'   => __('Pasta/Rede', 'smtt-intranet'),
            'atalho' => __('Atalho/Executável', 'smtt-intranet'),
            'remoto' => __('Remoto/RDP', 'smtt-intranet'),
        );

        return isset($map[$type]) ? $map[$type] : __('Não definido', 'smtt-intranet');
    }

    private function get_agenda_type_label($type) {
        $map = array(
            'evento'  => __('Evento', 'smtt-intranet'),
            'reserva' => __('Reserva', 'smtt-intranet'),
        );

        return isset($map[$type]) ? $map[$type] : __('Não definido', 'smtt-intranet');
    }

    private function get_agenda_status_label($status) {
        $map = array(
            'agendado'  => __('Agendado', 'smtt-intranet'),
            'cancelado' => __('Cancelado', 'smtt-intranet'),
            'concluido' => __('Concluído', 'smtt-intranet'),
        );

        return isset($map[$status]) ? $map[$status] : __('Não definido', 'smtt-intranet');
    }

    private function get_mural_type_label($type) {
        $map = array(
            'banner'            => __('Banner principal', 'smtt-intranet'),
            'card'              => __('Card', 'smtt-intranet'),
            'topo_deslizante'   => __('Faixa superior deslizante', 'smtt-intranet'),
            'rodape_deslizante' => __('Faixa inferior deslizante', 'smtt-intranet'),
            'alerta'            => __('Alerta simples', 'smtt-intranet'),
        );

        return isset($map[$type]) ? $map[$type] : __('Não definido', 'smtt-intranet');
    }

    private function normalize_access_link($link) {
        $link = trim((string) $link);

        if ($link === '') {
            return '#';
        }

        if (preg_match('/^(https?:\/\/|mailto:|tel:|ftp:\/\/)/i', $link)) {
            return $link;
        }

        return $link;
    }

    private function filter_access_items($items, $search, $status, $type = 'folder', $sector = 0, $is_admin = false, $user_sector_id = 0) {
        return array_values(array_filter($items, function($item) use ($search, $status, $type, $sector, $is_admin, $user_sector_id) {
            $description = (string) get_post_meta($item->ID, '_smtt_description', true);
            $item_status = (string) get_post_meta($item->ID, '_smtt_status', true);
            $extra_field = $type === 'folder'
                ? (string) get_post_meta($item->ID, '_smtt_manager', true)
                : '';

            $item_terms = wp_get_object_terms($item->ID, 'smtt_sector', array('fields' => 'ids'));
            $item_terms = is_wp_error($item_terms) ? array() : array_map('intval', $item_terms);

            if ($status && $item_status !== $status) {
                return false;
            }

            if (!$is_admin && $user_sector_id > 0) {
                if (!empty($item_terms) && !in_array($user_sector_id, $item_terms, true)) {
                    return false;
                }
            }

            if ($sector > 0) {
                if (empty($item_terms) || !in_array((int) $sector, $item_terms, true)) {
                    return false;
                }
            }

            if ($search) {
                $terms = wp_get_object_terms($item->ID, 'smtt_sector', array('fields' => 'names'));
                $sector_names = !is_wp_error($terms) ? implode(' ', $terms) : '';

                $haystack = strtolower(
                    get_the_title($item->ID) . ' ' .
                    $description . ' ' .
                    $extra_field . ' ' .
                    $sector_names
                );

                if (strpos($haystack, strtolower($search)) === false) {
                    return false;
                }
            }

            return true;
        }));
    }

    private function is_featured_item($item) {
        return get_post_meta($item->ID, '_smtt_featured', true) === '1';
    }

    private function render_folder_card($item, $featured = false) {
        $description = get_post_meta($item->ID, '_smtt_description', true);
        $server_path = get_post_meta($item->ID, '_smtt_server_path', true);
        $manager     = get_post_meta($item->ID, '_smtt_manager', true);
        $status      = get_post_meta($item->ID, '_smtt_status', true);
        $icon        = get_post_meta($item->ID, '_smtt_icon', true);
        $priority    = get_post_meta($item->ID, '_smtt_priority', true);
        $terms       = wp_get_object_terms($item->ID, 'smtt_sector', array('fields' => 'names'));

        if (!$icon) {
            $icon = 'dashicons-networking';
        }
        ?>
        <div class="smtt-access-card <?php echo $featured ? 'smtt-access-card-featured' : ''; ?>">
            <div class="smtt-access-card-top">
                <div class="smtt-module-icon">
                    <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                </div>
                <span class="smtt-priority-badge smtt-priority-<?php echo esc_attr($priority ? $priority : 'media'); ?>">
                    <?php echo esc_html($this->get_priority_label($priority)); ?>
                </span>
            </div>

            <h3><?php echo esc_html(get_the_title($item->ID)); ?></h3>
            <p><?php echo esc_html($description); ?></p>

            <?php if (!is_wp_error($terms) && !empty($terms)) : ?>
                <div class="smtt-meta-line">
                    <strong><?php echo esc_html__('Setor:', 'smtt-intranet'); ?></strong>
                    <?php echo esc_html($terms[0]); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($manager)) : ?>
                <div class="smtt-meta-line">
                    <strong><?php echo esc_html__('Responsável:', 'smtt-intranet'); ?></strong>
                    <?php echo esc_html($manager); ?>
                </div>
            <?php endif; ?>

            <div class="smtt-meta-line">
                <strong><?php echo esc_html__('Status:', 'smtt-intranet'); ?></strong>
                <?php echo esc_html($this->get_status_label($status)); ?>
            </div>

            <?php if (!empty($server_path)) : ?>
                <div class="smtt-meta-line">
                    <strong><?php echo esc_html__('Caminho:', 'smtt-intranet'); ?></strong>
                    <?php echo esc_html($server_path); ?>
                </div>
                <div class="smtt-access-actions">
                    <a class="button button-secondary" href="<?php echo esc_url($this->normalize_access_link($server_path)); ?>" target="_blank" rel="noopener noreferrer">
                        <?php echo esc_html__('Abrir pasta', 'smtt-intranet'); ?>
                    </a>
                    <a class="button" href="<?php echo esc_url(get_edit_post_link($item->ID)); ?>">
                        <?php echo esc_html__('Editar', 'smtt-intranet'); ?>
                    </a>
                    <a class="button button-link-delete" href="<?php echo esc_url(get_delete_post_link($item->ID, '', true)); ?>" onclick="return confirm('<?php echo esc_js(__('Deseja realmente enviar este item para a lixeira?', 'smtt-intranet')); ?>');">
                        <?php echo esc_html__('Excluir', 'smtt-intranet'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_system_card($item, $featured = false) {
        $description = get_post_meta($item->ID, '_smtt_description', true);
        $access_type = get_post_meta($item->ID, '_smtt_access_type', true);
        $access_path = get_post_meta($item->ID, '_smtt_access_path', true);
        $status      = get_post_meta($item->ID, '_smtt_status', true);
        $icon        = get_post_meta($item->ID, '_smtt_icon', true);
        $priority    = get_post_meta($item->ID, '_smtt_priority', true);
        $terms       = wp_get_object_terms($item->ID, 'smtt_sector', array('fields' => 'names'));

        if (!$icon) {
            $icon = 'dashicons-admin-tools';
        }
        ?>
        <div class="smtt-access-card <?php echo $featured ? 'smtt-access-card-featured' : ''; ?>">
            <div class="smtt-access-card-top">
                <div class="smtt-module-icon">
                    <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                </div>
                <span class="smtt-priority-badge smtt-priority-<?php echo esc_attr($priority ? $priority : 'media'); ?>">
                    <?php echo esc_html($this->get_priority_label($priority)); ?>
                </span>
            </div>

            <h3><?php echo esc_html(get_the_title($item->ID)); ?></h3>
            <p><?php echo esc_html($description); ?></p>

            <?php if (!is_wp_error($terms) && !empty($terms)) : ?>
                <div class="smtt-meta-line">
                    <strong><?php echo esc_html__('Setor:', 'smtt-intranet'); ?></strong>
                    <?php echo esc_html($terms[0]); ?>
                </div>
            <?php endif; ?>

            <div class="smtt-meta-line">
                <strong><?php echo esc_html__('Tipo:', 'smtt-intranet'); ?></strong>
                <?php echo esc_html($this->get_access_type_label($access_type)); ?>
            </div>

            <div class="smtt-meta-line">
                <strong><?php echo esc_html__('Status:', 'smtt-intranet'); ?></strong>
                <?php echo esc_html($this->get_status_label($status)); ?>
            </div>

            <?php if (!empty($access_path)) : ?>
                <div class="smtt-meta-line">
                    <strong><?php echo esc_html__('Acesso:', 'smtt-intranet'); ?></strong>
                    <?php echo esc_html($access_path); ?>
                </div>
                <div class="smtt-access-actions">
                    <a class="button button-primary" href="<?php echo esc_url($this->normalize_access_link($access_path)); ?>" target="_blank" rel="noopener noreferrer">
                        <?php echo esc_html__('Acessar sistema', 'smtt-intranet'); ?>
                    </a>
                    <a class="button" href="<?php echo esc_url(get_edit_post_link($item->ID)); ?>">
                        <?php echo esc_html__('Editar', 'smtt-intranet'); ?>
                    </a>
                    <a class="button button-link-delete" href="<?php echo esc_url(get_delete_post_link($item->ID, '', true)); ?>" onclick="return confirm('<?php echo esc_js(__('Deseja realmente enviar este item para a lixeira?', 'smtt-intranet')); ?>');">
                        <?php echo esc_html__('Excluir', 'smtt-intranet'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function get_priority_label($priority) {
        $map = array(
            'alta'  => __('Alta prioridade', 'smtt-intranet'),
            'media' => __('Média prioridade', 'smtt-intranet'),
            'baixa' => __('Baixa prioridade', 'smtt-intranet'),
        );

        return isset($map[$priority]) ? $map[$priority] : __('Média prioridade', 'smtt-intranet');
    }
}