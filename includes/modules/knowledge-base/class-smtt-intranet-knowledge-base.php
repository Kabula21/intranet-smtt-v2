<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Knowledge_Base {

    const POST_TYPE = 'smtt_kb_article';

    public function register() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
    }

    public function register_post_type() {
        $labels = array(
            'name'               => __('Base Interna', 'smtt-intranet'),
            'singular_name'      => __('Artigo da Base Interna', 'smtt-intranet'),
            'menu_name'          => __('Base Interna', 'smtt-intranet'),
            'add_new'            => __('Adicionar novo', 'smtt-intranet'),
            'add_new_item'       => __('Adicionar artigo', 'smtt-intranet'),
            'edit_item'          => __('Editar artigo', 'smtt-intranet'),
            'new_item'           => __('Novo artigo', 'smtt-intranet'),
            'view_item'          => __('Ver artigo', 'smtt-intranet'),
            'search_items'       => __('Buscar artigos', 'smtt-intranet'),
            'not_found'          => __('Nenhum artigo encontrado', 'smtt-intranet'),
            'not_found_in_trash' => __('Nenhum artigo encontrado na lixeira', 'smtt-intranet'),
        );

        $args = array(
            'labels'          => $labels,
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => 'smtt-intranet',
            'supports'        => array('title', 'editor'),
            'taxonomies'      => array('smtt_sector', 'smtt_kb_category'),
            'capability_type' => 'post',
            'map_meta_cap'    => true,
        );

        register_post_type(self::POST_TYPE, $args);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'smtt_kb_details',
            __('Dados do Artigo', 'smtt-intranet'),
            array($this, 'render_details_meta_box'),
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render_details_meta_box($post) {
        wp_nonce_field('smtt_save_kb_article', 'smtt_kb_article_nonce');

        $summary        = get_post_meta($post->ID, '_smtt_summary', true);
        $keywords       = get_post_meta($post->ID, '_smtt_keywords', true);
        $status         = get_post_meta($post->ID, '_smtt_status', true);
        $featured       = get_post_meta($post->ID, '_smtt_featured', true);
        $sort_order     = get_post_meta($post->ID, '_smtt_sort_order', true);
        $public_visible = get_post_meta($post->ID, '_smtt_public_visible', true);

        $sector_terms = wp_get_object_terms($post->ID, 'smtt_sector', array('fields' => 'ids'));
        $selected_sector = !empty($sector_terms) ? (int) $sector_terms[0] : 0;

        $category_terms = wp_get_object_terms($post->ID, 'smtt_kb_category', array('fields' => 'ids'));
        $selected_category = !empty($category_terms) ? (int) $category_terms[0] : 0;
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="smtt_sector_term"><?php echo esc_html__('Setor', 'smtt-intranet'); ?></label></th>
                <td>
                    <?php
                    wp_dropdown_categories(array(
                        'taxonomy'         => 'smtt_sector',
                        'name'             => 'smtt_sector_term',
                        'orderby'          => 'name',
                        'show_option_none' => __('Selecione um setor', 'smtt-intranet'),
                        'hide_empty'       => false,
                        'hierarchical'     => true,
                        'selected'         => $selected_sector,
                    ));
                    ?>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smtt_kb_category_term"><?php echo esc_html__('Categoria', 'smtt-intranet'); ?></label></th>
                <td>
                    <?php
                    wp_dropdown_categories(array(
                        'taxonomy'         => 'smtt_kb_category',
                        'name'             => 'smtt_kb_category_term',
                        'orderby'          => 'name',
                        'show_option_none' => __('Selecione uma categoria', 'smtt-intranet'),
                        'hide_empty'       => false,
                        'hierarchical'     => true,
                        'selected'         => $selected_category,
                    ));
                    ?>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smtt_summary"><?php echo esc_html__('Resumo', 'smtt-intranet'); ?></label></th>
                <td>
                    <textarea name="smtt_summary" id="smtt_summary" rows="3" class="large-text"><?php echo esc_textarea($summary); ?></textarea>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smtt_keywords"><?php echo esc_html__('Palavras-chave', 'smtt-intranet'); ?></label></th>
                <td>
                    <input type="text" name="smtt_keywords" id="smtt_keywords" class="regular-text" value="<?php echo esc_attr($keywords); ?>" />
                    <p class="description"><?php echo esc_html__('Ex.: processo, memorando, reserva, sistema, RH', 'smtt-intranet'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smtt_sort_order"><?php echo esc_html__('Ordem de exibição', 'smtt-intranet'); ?></label></th>
                <td>
                    <input type="number" name="smtt_sort_order" id="smtt_sort_order" class="small-text" value="<?php echo esc_attr($sort_order !== '' ? $sort_order : 0); ?>" />
                </td>
            </tr>

            <tr>
                <th scope="row"><?php echo esc_html__('Destaque', 'smtt-intranet'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="smtt_featured" value="1" <?php checked($featured, '1'); ?> />
                        <?php echo esc_html__('Mostrar em destaque', 'smtt-intranet'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php echo esc_html__('Visível para consulta interna', 'smtt-intranet'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="smtt_public_visible" value="1" <?php checked($public_visible, '1'); ?> />
                        <?php echo esc_html__('Exibir para os colaboradores na área pública interna', 'smtt-intranet'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smtt_status"><?php echo esc_html__('Status', 'smtt-intranet'); ?></label></th>
                <td>
                    <select name="smtt_status" id="smtt_status">
                        <option value="ativo" <?php selected($status, 'ativo'); ?>><?php echo esc_html__('Ativo', 'smtt-intranet'); ?></option>
                        <option value="inativo" <?php selected($status, 'inativo'); ?>><?php echo esc_html__('Inativo', 'smtt-intranet'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_meta_boxes($post_id) {
        if (!isset($_POST['smtt_kb_article_nonce'])) {
            return;
        }

        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['smtt_kb_article_nonce'])), 'smtt_save_kb_article')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (get_post_type($post_id) !== self::POST_TYPE) {
            return;
        }

        update_post_meta($post_id, '_smtt_summary', isset($_POST['smtt_summary']) ? sanitize_textarea_field(wp_unslash($_POST['smtt_summary'])) : '');
        update_post_meta($post_id, '_smtt_keywords', isset($_POST['smtt_keywords']) ? sanitize_text_field(wp_unslash($_POST['smtt_keywords'])) : '');
        update_post_meta($post_id, '_smtt_sort_order', isset($_POST['smtt_sort_order']) ? intval($_POST['smtt_sort_order']) : 0);
        update_post_meta($post_id, '_smtt_featured', isset($_POST['smtt_featured']) ? '1' : '0');
        update_post_meta($post_id, '_smtt_public_visible', isset($_POST['smtt_public_visible']) ? '1' : '0');
        update_post_meta($post_id, '_smtt_status', isset($_POST['smtt_status']) ? sanitize_text_field(wp_unslash($_POST['smtt_status'])) : 'ativo');

        $sector_term   = isset($_POST['smtt_sector_term']) ? intval($_POST['smtt_sector_term']) : 0;
        $category_term = isset($_POST['smtt_kb_category_term']) ? intval($_POST['smtt_kb_category_term']) : 0;

        if ($sector_term > 0) {
            wp_set_object_terms($post_id, array($sector_term), 'smtt_sector');
        } else {
            wp_set_object_terms($post_id, array(), 'smtt_sector');
        }

        if ($category_term > 0) {
            wp_set_object_terms($post_id, array($category_term), 'smtt_kb_category');
        } else {
            wp_set_object_terms($post_id, array(), 'smtt_kb_category');
        }
    }
}