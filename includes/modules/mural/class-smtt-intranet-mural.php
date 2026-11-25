<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Mural {

    const POST_TYPE = 'smtt_mural_item';

    public function register() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_media_scripts'));
    }

    public function register_post_type() {
        $labels = array(
            'name'               => __('Mural Interno', 'smtt-intranet'),
            'singular_name'      => __('Item do Mural', 'smtt-intranet'),
            'menu_name'          => __('Mural Interno', 'smtt-intranet'),
            'add_new'            => __('Adicionar novo', 'smtt-intranet'),
            'add_new_item'       => __('Adicionar item do mural', 'smtt-intranet'),
            'edit_item'          => __('Editar item do mural', 'smtt-intranet'),
            'new_item'           => __('Novo item do mural', 'smtt-intranet'),
            'view_item'          => __('Ver item do mural', 'smtt-intranet'),
            'search_items'       => __('Buscar itens do mural', 'smtt-intranet'),
            'not_found'          => __('Nenhum item encontrado', 'smtt-intranet'),
            'not_found_in_trash' => __('Nenhum item encontrado na lixeira', 'smtt-intranet'),
        );

        $args = array(
            'labels'          => $labels,
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => 'smtt-intranet',
            'supports'        => array('title', 'editor'),
            'taxonomies'      => array('smtt_sector', 'smtt_mural_category'),
            'capability_type' => 'post',
            'map_meta_cap'    => true,
        );

        register_post_type(self::POST_TYPE, $args);
    }

    public function enqueue_media_scripts($hook) {
        if (strpos((string) $hook, 'smtt_mural_item') === false) {
            return;
        }

        wp_enqueue_media();
    }

    public function add_meta_boxes() {
        add_meta_box(
            'smtt_mural_details',
            __('Dados do Mural Interno', 'smtt-intranet'),
            array($this, 'render_details_meta_box'),
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render_details_meta_box($post) {
        wp_nonce_field('smtt_save_mural_item', 'smtt_mural_item_nonce');

        $subtitle       = get_post_meta($post->ID, '_smtt_subtitle', true);
        $summary        = get_post_meta($post->ID, '_smtt_summary', true);
        $link_url       = get_post_meta($post->ID, '_smtt_link_url', true);
        $display_type   = get_post_meta($post->ID, '_smtt_display_type', true);
        $priority       = get_post_meta($post->ID, '_smtt_priority', true);
        $start_date     = get_post_meta($post->ID, '_smtt_start_date', true);
        $end_date       = get_post_meta($post->ID, '_smtt_end_date', true);
        $status         = get_post_meta($post->ID, '_smtt_status', true);
        $featured       = get_post_meta($post->ID, '_smtt_featured', true);
        $sort_order     = get_post_meta($post->ID, '_smtt_sort_order', true);

        /*
         * v51: registros antigos não tinham um marcador que diferenciasse
         * "desmarcado de propósito" de "salvo como 0 porque o checkbox não
         * vinha marcado por padrão". Para compatibilidade, avisos legados sem
         * o marcador passam a ser considerados visíveis por padrão.
         */
        $visibility_explicit = metadata_exists('post', $post->ID, '_smtt_public_visibility_set');
        $public_visible      = $visibility_explicit
            ? get_post_meta($post->ID, '_smtt_public_visible', true)
            : '1';

        if ($status === '') {
            $status = 'ativo';
        }

        $image_id       = get_post_meta($post->ID, '_smtt_image_id', true);
        $gallery_ids    = get_post_meta($post->ID, '_smtt_gallery_ids', true);

        $sector_terms = wp_get_object_terms($post->ID, 'smtt_sector', array('fields' => 'ids'));
        $selected_sector = !empty($sector_terms) ? (int) $sector_terms[0] : 0;

        $category_terms = wp_get_object_terms($post->ID, 'smtt_mural_category', array('fields' => 'ids'));
        $selected_category = !empty($category_terms) ? (int) $category_terms[0] : 0;

        $image_url = $image_id ? wp_get_attachment_image_url((int) $image_id, 'medium') : '';
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
                <th scope="row"><label for="smtt_mural_category_term"><?php echo esc_html__('Categoria', 'smtt-intranet'); ?></label></th>
                <td>
                    <?php
                    wp_dropdown_categories(array(
                        'taxonomy'         => 'smtt_mural_category',
                        'name'             => 'smtt_mural_category_term',
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
                <th scope="row"><label for="smtt_subtitle"><?php echo esc_html__('Subtítulo', 'smtt-intranet'); ?></label></th>
                <td>
                    <input type="text" name="smtt_subtitle" id="smtt_subtitle" class="regular-text" value="<?php echo esc_attr($subtitle); ?>">
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smtt_summary"><?php echo esc_html__('Resumo', 'smtt-intranet'); ?></label></th>
                <td>
                    <textarea name="smtt_summary" id="smtt_summary" rows="3" class="large-text"><?php echo esc_textarea($summary); ?></textarea>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smtt_link_url"><?php echo esc_html__('Link opcional', 'smtt-intranet'); ?></label></th>
                <td>
                    <input type="url" name="smtt_link_url" id="smtt_link_url" class="regular-text" value="<?php echo esc_attr($link_url); ?>">
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smtt_display_type"><?php echo esc_html__('Tipo de exibição', 'smtt-intranet'); ?></label></th>
                <td>
                    <select name="smtt_display_type" id="smtt_display_type">
                        <option value="banner" <?php selected($display_type, 'banner'); ?>><?php echo esc_html__('Banner principal', 'smtt-intranet'); ?></option>
                        <option value="card" <?php selected($display_type, 'card'); ?>><?php echo esc_html__('Card', 'smtt-intranet'); ?></option>
                        <option value="topo_deslizante" <?php selected($display_type, 'topo_deslizante'); ?>><?php echo esc_html__('Faixa superior deslizante', 'smtt-intranet'); ?></option>
                        <option value="rodape_deslizante" <?php selected($display_type, 'rodape_deslizante'); ?>><?php echo esc_html__('Faixa inferior deslizante', 'smtt-intranet'); ?></option>
                        <option value="alerta" <?php selected($display_type, 'alerta'); ?>><?php echo esc_html__('Alerta simples', 'smtt-intranet'); ?></option>
                    </select>
                    <p class="description"><?php echo esc_html__('O front aplicará o efeito automaticamente conforme o tipo escolhido.', 'smtt-intranet'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smtt_priority"><?php echo esc_html__('Prioridade', 'smtt-intranet'); ?></label></th>
                <td>
                    <select name="smtt_priority" id="smtt_priority">
                        <option value="alta" <?php selected($priority, 'alta'); ?>><?php echo esc_html__('Alta', 'smtt-intranet'); ?></option>
                        <option value="media" <?php selected($priority, 'media'); ?>><?php echo esc_html__('Média', 'smtt-intranet'); ?></option>
                        <option value="baixa" <?php selected($priority, 'baixa'); ?>><?php echo esc_html__('Baixa', 'smtt-intranet'); ?></option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smtt_start_date"><?php echo esc_html__('Data inicial', 'smtt-intranet'); ?></label></th>
                <td>
                    <input type="date" name="smtt_start_date" id="smtt_start_date" value="<?php echo esc_attr($start_date); ?>">
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smtt_end_date"><?php echo esc_html__('Data final', 'smtt-intranet'); ?></label></th>
                <td>
                    <input type="date" name="smtt_end_date" id="smtt_end_date" value="<?php echo esc_attr($end_date); ?>">
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smtt_sort_order"><?php echo esc_html__('Ordem de exibição', 'smtt-intranet'); ?></label></th>
                <td>
                    <input type="number" name="smtt_sort_order" id="smtt_sort_order" class="small-text" value="<?php echo esc_attr($sort_order !== '' ? $sort_order : 0); ?>">
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

            <tr>
                <th scope="row"><?php echo esc_html__('Destaque', 'smtt-intranet'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="smtt_featured" value="1" <?php checked($featured, '1'); ?>>
                        <?php echo esc_html__('Mostrar em destaque', 'smtt-intranet'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php echo esc_html__('Visível no front interno', 'smtt-intranet'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="smtt_public_visible" value="1" <?php checked($public_visible, '1'); ?>>
                        <?php echo esc_html__('Exibir para os colaboradores na intranet', 'smtt-intranet'); ?>
                        <p class="description" style="margin:4px 0 0;"><?php echo esc_html__('Por padrão, todo aviso publicado fica visível. Desmarque somente se quiser ocultá-lo do card.', 'smtt-intranet'); ?></p>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php echo esc_html__('Imagem principal', 'smtt-intranet'); ?></th>
                <td>
                    <input type="hidden" name="smtt_image_id" id="smtt_image_id" value="<?php echo esc_attr($image_id); ?>">
                    <button type="button" class="button smtt-upload-image"><?php echo esc_html__('Selecionar imagem', 'smtt-intranet'); ?></button>
                    <button type="button" class="button smtt-remove-image"><?php echo esc_html__('Remover', 'smtt-intranet'); ?></button>
                    <div class="smtt-image-preview" style="margin-top:10px;">
                        <?php if ($image_url) : ?>
                            <img src="<?php echo esc_url($image_url); ?>" style="max-width:220px;height:auto;border:1px solid #ddd;padding:4px;">
                        <?php endif; ?>
                    </div>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php echo esc_html__('Galeria de imagens', 'smtt-intranet'); ?></th>
                <td>
                    <input type="text" name="smtt_gallery_ids" id="smtt_gallery_ids" class="regular-text" value="<?php echo esc_attr($gallery_ids); ?>">
                    <p class="description"><?php echo esc_html__('Selecione múltiplas imagens. Os IDs serão preenchidos automaticamente.', 'smtt-intranet'); ?></p>
                    <button type="button" class="button smtt-upload-gallery"><?php echo esc_html__('Selecionar galeria', 'smtt-intranet'); ?></button>
                </td>
            </tr>
        </table>

        <script>
        jQuery(function($){
            $('.smtt-upload-image').on('click', function(e){
                e.preventDefault();
                const frame = wp.media({
                    title: 'Selecionar imagem',
                    button: { text: 'Usar imagem' },
                    multiple: false
                });

                frame.on('select', function(){
                    const attachment = frame.state().get('selection').first().toJSON();
                    $('#smtt_image_id').val(attachment.id);
                    $('.smtt-image-preview').html('<img src="' + attachment.url + '" style="max-width:220px;height:auto;border:1px solid #ddd;padding:4px;">');
                });

                frame.open();
            });

            $('.smtt-remove-image').on('click', function(e){
                e.preventDefault();
                $('#smtt_image_id').val('');
                $('.smtt-image-preview').html('');
            });

            $('.smtt-upload-gallery').on('click', function(e){
                e.preventDefault();
                const frame = wp.media({
                    title: 'Selecionar galeria',
                    button: { text: 'Usar galeria' },
                    multiple: true
                });

                frame.on('select', function(){
                    const selection = frame.state().get('selection').toJSON();
                    const ids = selection.map(function(item){ return item.id; });
                    $('#smtt_gallery_ids').val(ids.join(','));
                });

                frame.open();
            });
        });
        </script>
        <?php
    }

    public function save_meta_boxes($post_id) {
        if (!isset($_POST['smtt_mural_item_nonce'])) {
            return;
        }

        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['smtt_mural_item_nonce'])), 'smtt_save_mural_item')) {
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

        update_post_meta($post_id, '_smtt_subtitle', isset($_POST['smtt_subtitle']) ? sanitize_text_field(wp_unslash($_POST['smtt_subtitle'])) : '');
        update_post_meta($post_id, '_smtt_summary', isset($_POST['smtt_summary']) ? sanitize_textarea_field(wp_unslash($_POST['smtt_summary'])) : '');
        update_post_meta($post_id, '_smtt_link_url', isset($_POST['smtt_link_url']) ? esc_url_raw(wp_unslash($_POST['smtt_link_url'])) : '');
        update_post_meta($post_id, '_smtt_display_type', isset($_POST['smtt_display_type']) ? sanitize_text_field(wp_unslash($_POST['smtt_display_type'])) : 'card');
        update_post_meta($post_id, '_smtt_priority', isset($_POST['smtt_priority']) ? sanitize_text_field(wp_unslash($_POST['smtt_priority'])) : 'media');
        update_post_meta($post_id, '_smtt_start_date', isset($_POST['smtt_start_date']) ? sanitize_text_field(wp_unslash($_POST['smtt_start_date'])) : '');
        update_post_meta($post_id, '_smtt_end_date', isset($_POST['smtt_end_date']) ? sanitize_text_field(wp_unslash($_POST['smtt_end_date'])) : '');
        update_post_meta($post_id, '_smtt_sort_order', isset($_POST['smtt_sort_order']) ? intval($_POST['smtt_sort_order']) : 0);
        update_post_meta($post_id, '_smtt_status', isset($_POST['smtt_status']) ? sanitize_text_field(wp_unslash($_POST['smtt_status'])) : 'ativo');
        update_post_meta($post_id, '_smtt_featured', isset($_POST['smtt_featured']) ? '1' : '0');
        update_post_meta($post_id, '_smtt_public_visible', isset($_POST['smtt_public_visible']) ? '1' : '0');
        // Marca que, a partir da v51, a escolha de visibilidade foi explícita.
        update_post_meta($post_id, '_smtt_public_visibility_set', '1');
        update_post_meta($post_id, '_smtt_image_id', isset($_POST['smtt_image_id']) ? intval($_POST['smtt_image_id']) : 0);
        update_post_meta($post_id, '_smtt_gallery_ids', isset($_POST['smtt_gallery_ids']) ? sanitize_text_field(wp_unslash($_POST['smtt_gallery_ids'])) : '');

        $sector_term   = isset($_POST['smtt_sector_term']) ? intval($_POST['smtt_sector_term']) : 0;
        $category_term = isset($_POST['smtt_mural_category_term']) ? intval($_POST['smtt_mural_category_term']) : 0;

        if ($sector_term > 0) {
            wp_set_object_terms($post_id, array($sector_term), 'smtt_sector');
        } else {
            wp_set_object_terms($post_id, array(), 'smtt_sector');
        }

        if ($category_term > 0) {
            wp_set_object_terms($post_id, array($category_term), 'smtt_mural_category');
        } else {
            wp_set_object_terms($post_id, array(), 'smtt_mural_category');
        }

        /*
         * O hook save_post_smtt_mural_item da home dispara antes deste save_meta_boxes
         * genérico no fluxo do WordPress. Disparamos um segundo evento somente depois
         * que todas as metas/taxonomias já foram persistidas para invalidar o cache
         * com os dados definitivos.
         */
        do_action('smtt_mural_item_meta_saved', $post_id);
    }
}