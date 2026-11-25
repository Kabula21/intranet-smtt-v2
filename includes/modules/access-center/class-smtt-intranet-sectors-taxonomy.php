<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Sectors_Taxonomy {

    const TAXONOMY = 'smtt_sector';

    const META_VISIBLE     = '_smtt_access_center_visible';
    const META_ORDER       = '_smtt_access_center_order';
    const META_SHORT_LABEL = '_smtt_access_center_short_label';

    public function register() {
        add_action('init', array($this, 'register_taxonomy'));
        add_action('restrict_manage_posts', array($this, 'add_admin_filter'));
        add_filter('parse_query', array($this, 'filter_admin_list'));

        add_action(self::TAXONOMY . '_add_form_fields', array($this, 'render_add_fields'));
        add_action(self::TAXONOMY . '_edit_form_fields', array($this, 'render_edit_fields'));

        add_action('created_' . self::TAXONOMY, array($this, 'save_term_meta'));
        add_action('edited_' . self::TAXONOMY, array($this, 'save_term_meta'));
    }

    public function register_taxonomy() {
        $labels = array(
            'name'              => __('Setores', 'smtt-intranet'),
            'singular_name'     => __('Setor', 'smtt-intranet'),
            'search_items'      => __('Buscar setores', 'smtt-intranet'),
            'all_items'         => __('Todos os setores', 'smtt-intranet'),
            'parent_item'       => __('Setor pai', 'smtt-intranet'),
            'parent_item_colon' => __('Setor pai:', 'smtt-intranet'),
            'edit_item'         => __('Editar setor', 'smtt-intranet'),
            'update_item'       => __('Atualizar setor', 'smtt-intranet'),
            'add_new_item'      => __('Adicionar novo setor', 'smtt-intranet'),
            'new_item_name'     => __('Novo nome de setor', 'smtt-intranet'),
            'menu_name'         => __('Setores', 'smtt-intranet'),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_in_menu'      => 'smtt-intranet',
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => false,
        );

        register_taxonomy(
            self::TAXONOMY,
            array('smtt_sector_folder', 'smtt_internal_system'),
            $args
        );
    }

    public function render_add_fields() {
        ?>
        <div class="form-field">
            <label for="smtt_access_center_short_label">
                <?php echo esc_html__('Nome curto na Central de Acessos', 'smtt-intranet'); ?>
            </label>
            <input type="text" name="smtt_access_center_short_label" id="smtt_access_center_short_label" value="" placeholder="Ex.: JARI, DAF, CPD">
            <p><?php echo esc_html__('Use uma sigla ou nome curto para aparecer no público.', 'smtt-intranet'); ?></p>
        </div>

        <div class="form-field">
            <label>
                <input type="checkbox" name="smtt_access_center_visible" value="1" checked>
                <?php echo esc_html__('Exibir este setor na Central de Acessos pública', 'smtt-intranet'); ?>
            </label>
        </div>

        <div class="form-field">
            <label for="smtt_access_center_order">
                <?php echo esc_html__('Ordem na Central de Acessos', 'smtt-intranet'); ?>
            </label>
            <input type="number" name="smtt_access_center_order" id="smtt_access_center_order" value="0" min="0" step="1">
        </div>
        <?php
    }

    public function render_edit_fields($term) {
        $short_label = get_term_meta($term->term_id, self::META_SHORT_LABEL, true);
        $visible     = get_term_meta($term->term_id, self::META_VISIBLE, true);
        $order       = get_term_meta($term->term_id, self::META_ORDER, true);

        if ($visible === '') {
            $visible = '1';
        }
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="smtt_access_center_short_label">
                    <?php echo esc_html__('Nome curto na Central de Acessos', 'smtt-intranet'); ?>
                </label>
            </th>
            <td>
                <input type="text" name="smtt_access_center_short_label" id="smtt_access_center_short_label" value="<?php echo esc_attr($short_label); ?>" placeholder="Ex.: JARI, DAF, CPD">
                <p class="description">
                    <?php echo esc_html__('Use uma sigla ou nome curto para aparecer no público. Se deixar vazio, será usado o nome completo do setor.', 'smtt-intranet'); ?>
                </p>
            </td>
        </tr>

        <tr class="form-field">
            <th scope="row">
                <?php echo esc_html__('Visibilidade pública', 'smtt-intranet'); ?>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="smtt_access_center_visible" value="1" <?php checked($visible, '1'); ?>>
                    <?php echo esc_html__('Exibir este setor na Central de Acessos pública', 'smtt-intranet'); ?>
                </label>
            </td>
        </tr>

        <tr class="form-field">
            <th scope="row">
                <label for="smtt_access_center_order">
                    <?php echo esc_html__('Ordem na Central de Acessos', 'smtt-intranet'); ?>
                </label>
            </th>
            <td>
                <input type="number" name="smtt_access_center_order" id="smtt_access_center_order" value="<?php echo esc_attr($order !== '' ? $order : 0); ?>" min="0" step="1">
            </td>
        </tr>
        <?php
    }

    public function save_term_meta($term_id) {
        if (!current_user_can('manage_categories')) {
            return;
        }

        $short_label = isset($_POST['smtt_access_center_short_label'])
            ? sanitize_text_field(wp_unslash($_POST['smtt_access_center_short_label']))
            : '';

        $visible = isset($_POST['smtt_access_center_visible']) ? '1' : '0';

        $order = isset($_POST['smtt_access_center_order'])
            ? intval($_POST['smtt_access_center_order'])
            : 0;

        update_term_meta($term_id, self::META_SHORT_LABEL, $short_label);
        update_term_meta($term_id, self::META_VISIBLE, $visible);
        update_term_meta($term_id, self::META_ORDER, $order);
    }

    public function add_admin_filter($post_type) {
        if (!in_array($post_type, array('smtt_sector_folder', 'smtt_internal_system'), true)) {
            return;
        }

        $taxonomy = self::TAXONOMY;
        $selected = isset($_GET[$taxonomy]) ? sanitize_text_field(wp_unslash($_GET[$taxonomy])) : '';

        wp_dropdown_categories(array(
            'show_option_all' => __('Todos os setores', 'smtt-intranet'),
            'taxonomy'        => $taxonomy,
            'name'            => $taxonomy,
            'orderby'         => 'name',
            'selected'        => $selected,
            'hierarchical'    => true,
            'depth'           => 3,
            'show_count'      => false,
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

        if (!in_array($post_type, array('smtt_sector_folder', 'smtt_internal_system'), true)) {
            return;
        }

        $taxonomy = self::TAXONOMY;
        $term_slug = isset($_GET[$taxonomy]) ? sanitize_text_field(wp_unslash($_GET[$taxonomy])) : '';

        if ($term_slug) {
            $query->query_vars[$taxonomy] = $term_slug;
        }
    }
}