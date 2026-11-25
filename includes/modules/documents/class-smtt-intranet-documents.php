<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Documents {

    const POST_TYPE = 'smtt_document';

    public function register() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
    }

    public function register_post_type() {
        $labels = array(
            'name'               => __('Documentos', 'smtt-intranet'),
            'singular_name'      => __('Documento', 'smtt-intranet'),
            'menu_name'          => __('Documentos', 'smtt-intranet'),
            'add_new'            => __('Adicionar novo', 'smtt-intranet'),
            'add_new_item'       => __('Adicionar novo documento', 'smtt-intranet'),
            'edit_item'          => __('Editar documento', 'smtt-intranet'),
            'new_item'           => __('Novo documento', 'smtt-intranet'),
            'view_item'          => __('Ver documento', 'smtt-intranet'),
            'search_items'       => __('Buscar documentos', 'smtt-intranet'),
            'not_found'          => __('Nenhum documento encontrado', 'smtt-intranet'),
            'not_found_in_trash' => __('Nenhum documento encontrado na lixeira', 'smtt-intranet'),
        );

        $args = array(
            'labels'          => $labels,
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => 'smtt-intranet',
            'supports'        => array('title'),
            'taxonomies'      => array('smtt_sector', 'smtt_document_category'),
            'capability_type' => 'post',
            'map_meta_cap'    => true,
        );

        register_post_type(self::POST_TYPE, $args);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'smtt_document_details',
            __('Dados do Documento', 'smtt-intranet'),
            array($this, 'render_details_meta_box'),
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render_details_meta_box($post) {
        wp_nonce_field('smtt_save_document', 'smtt_document_nonce');

        $description = get_post_meta($post->ID, '_smtt_description', true);
        $document_type = get_post_meta($post->ID, '_smtt_document_type', true);
        $file_url = get_post_meta($post->ID, '_smtt_file_url', true);
        $external_url = get_post_meta($post->ID, '_smtt_external_url', true);
        $status = get_post_meta($post->ID, '_smtt_status', true);
        $featured = get_post_meta($post->ID, '_smtt_featured', true);
        $sort_order = get_post_meta($post->ID, '_smtt_sort_order', true);

        $sector_terms = wp_get_object_terms($post->ID, 'smtt_sector', array('fields' => 'ids'));
        $selected_sector = !empty($sector_terms) ? (int) $sector_terms[0] : 0;

        $category_terms = wp_get_object_terms($post->ID, 'smtt_document_category', array('fields' => 'ids'));
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
                <th scope="row"><label for="smtt_document_category_term"><?php echo esc_html__('Categoria', 'smtt-intranet'); ?></label></th>
                <td>
                    <?php
                    wp_dropdown_categories(array(
                        'taxonomy'         => 'smtt_document_category',
                        'name'             => 'smtt_document_category_term',
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
                <th scope="row"><label for="smtt_description"><?php echo esc_html__('Descrição', 'smtt-intranet'); ?></label></th>
                <td>
                    <textarea name="smtt_description" id="smtt_description" rows="4" class="large-text"><?php echo esc_textarea($description); ?></textarea>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smtt_document_type"><?php echo esc_html__('Tipo do documento', 'smtt-intranet'); ?></label></th>
                <td>
                    <select name="smtt_document_type" id="smtt_document_type">
                        <option value="arquivo" <?php selected($document_type, 'arquivo'); ?>><?php echo esc_html__('Arquivo interno', 'smtt-intranet'); ?></option>
                        <option value="link" <?php selected($document_type, 'link'); ?>><?php echo esc_html__('Link externo', 'smtt-intranet'); ?></option>
                        <option value="misto" <?php selected($document_type, 'misto'); ?>><?php echo esc_html__('Arquivo + Link', 'smtt-intranet'); ?></option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smtt_file_url"><?php echo esc_html__('URL do arquivo', 'smtt-intranet'); ?></label></th>
                <td>
                    <input type="url" name="smtt_file_url" id="smtt_file_url" class="regular-text" value="<?php echo esc_attr($file_url); ?>" />
                    <p class="description"><?php echo esc_html__('Ex.: link do arquivo hospedado no WordPress ou no servidor.', 'smtt-intranet'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smtt_external_url"><?php echo esc_html__('Link externo', 'smtt-intranet'); ?></label></th>
                <td>
                    <input type="url" name="smtt_external_url" id="smtt_external_url" class="regular-text" value="<?php echo esc_attr($external_url); ?>" />
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
        if (!isset($_POST['smtt_document_nonce'])) {
            return;
        }

        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['smtt_document_nonce'])), 'smtt_save_document')) {
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

        update_post_meta($post_id, '_smtt_description', isset($_POST['smtt_description']) ? sanitize_textarea_field(wp_unslash($_POST['smtt_description'])) : '');
        update_post_meta($post_id, '_smtt_document_type', isset($_POST['smtt_document_type']) ? sanitize_text_field(wp_unslash($_POST['smtt_document_type'])) : 'arquivo');
        update_post_meta($post_id, '_smtt_file_url', isset($_POST['smtt_file_url']) ? esc_url_raw(wp_unslash($_POST['smtt_file_url'])) : '');
        update_post_meta($post_id, '_smtt_external_url', isset($_POST['smtt_external_url']) ? esc_url_raw(wp_unslash($_POST['smtt_external_url'])) : '');
        update_post_meta($post_id, '_smtt_sort_order', isset($_POST['smtt_sort_order']) ? intval($_POST['smtt_sort_order']) : 0);
        update_post_meta($post_id, '_smtt_featured', isset($_POST['smtt_featured']) ? '1' : '0');
        update_post_meta($post_id, '_smtt_status', isset($_POST['smtt_status']) ? sanitize_text_field(wp_unslash($_POST['smtt_status'])) : 'ativo');

        $sector_term = isset($_POST['smtt_sector_term']) ? intval($_POST['smtt_sector_term']) : 0;
        $category_term = isset($_POST['smtt_document_category_term']) ? intval($_POST['smtt_document_category_term']) : 0;

        if ($sector_term > 0) {
            wp_set_object_terms($post_id, array($sector_term), 'smtt_sector');
        } else {
            wp_set_object_terms($post_id, array(), 'smtt_sector');
        }

        if ($category_term > 0) {
            wp_set_object_terms($post_id, array($category_term), 'smtt_document_category');
        } else {
            wp_set_object_terms($post_id, array(), 'smtt_document_category');
        }
    }
}