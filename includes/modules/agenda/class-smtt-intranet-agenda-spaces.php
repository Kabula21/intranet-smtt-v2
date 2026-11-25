<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Agenda_Spaces {

    const TAXONOMY = 'smtt_agenda_space';

    public function register() {
        add_action('init', array($this, 'register_taxonomy'));
        add_action('restrict_manage_posts', array($this, 'add_admin_filter'));
        add_filter('parse_query', array($this, 'filter_admin_list'));
    }

    public function register_taxonomy() {
        $labels = array(
            'name'              => __('Espaços', 'smtt-intranet'),
            'singular_name'     => __('Espaço', 'smtt-intranet'),
            'search_items'      => __('Buscar espaços', 'smtt-intranet'),
            'all_items'         => __('Todos os espaços', 'smtt-intranet'),
            'edit_item'         => __('Editar espaço', 'smtt-intranet'),
            'update_item'       => __('Atualizar espaço', 'smtt-intranet'),
            'add_new_item'      => __('Adicionar novo espaço', 'smtt-intranet'),
            'new_item_name'     => __('Novo espaço', 'smtt-intranet'),
            'menu_name'         => __('Espaços', 'smtt-intranet'),
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
            'show_option_all' => __('Todos os espaços', 'smtt-intranet'),
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