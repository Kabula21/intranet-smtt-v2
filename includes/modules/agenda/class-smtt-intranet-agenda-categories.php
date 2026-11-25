<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Agenda_Categories {

    const TAXONOMY = 'smtt_agenda_category';

    public function register() {
        add_action('init', array($this, 'register_taxonomy'));
        add_action('restrict_manage_posts', array($this, 'add_admin_filter'));
        add_filter('parse_query', array($this, 'filter_admin_list'));
    }

    public function register_taxonomy() {
        $labels = array(
            'name'              => __('Categorias da Agenda', 'smtt-intranet'),
            'singular_name'     => __('Categoria da Agenda', 'smtt-intranet'),
            'search_items'      => __('Buscar categorias', 'smtt-intranet'),
            'all_items'         => __('Todas as categorias', 'smtt-intranet'),
            'edit_item'         => __('Editar categoria', 'smtt-intranet'),
            'update_item'       => __('Atualizar categoria', 'smtt-intranet'),
            'add_new_item'      => __('Adicionar nova categoria', 'smtt-intranet'),
            'new_item_name'     => __('Nova categoria', 'smtt-intranet'),
            'menu_name'         => __('Categorias da Agenda', 'smtt-intranet'),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => false,
        );

        register_taxonomy(
            self::TAXONOMY,
            array('smtt_agenda_item'),
            $args
        );
    }

    public function add_admin_filter($post_type) {
        if ($post_type !== 'smtt_agenda_item') {
            return;
        }

        $selected = isset($_GET[self::TAXONOMY]) ? sanitize_text_field(wp_unslash($_GET[self::TAXONOMY])) : '';

        wp_dropdown_categories(array(
            'show_option_all' => __('Todas as categorias', 'smtt-intranet'),
            'taxonomy'        => self::TAXONOMY,
            'name'            => self::TAXONOMY,
            'orderby'         => 'name',
            'selected'        => $selected,
            'hierarchical'    => true,
            'hide_empty'      => false,
            'value_field'     => 'slug',
        ));
    }

    public function filter_admin_list($query) {
        global $pagenow;

        if (!is_admin() || 'edit.php' !== $pagenow || !$query->is_main_query()) {
            return;
        }

        $post_type = isset($_GET['post_type']) ? sanitize_text_field(wp_unslash($_GET['post_type'])) : '';

        if ($post_type !== 'smtt_agenda_item') {
            return;
        }

        $term_slug = isset($_GET[self::TAXONOMY]) ? sanitize_text_field(wp_unslash($_GET[self::TAXONOMY])) : '';

        if ($term_slug) {
            $query->query_vars[self::TAXONOMY] = $term_slug;
        }
    }
}