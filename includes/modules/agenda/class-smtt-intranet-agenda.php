<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Agenda {

    const POST_TYPE = 'smtt_agenda_item';

    public function register() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }

    public function register_post_type() {
        $labels = array(
            'name'               => __('Agenda', 'smtt-intranet'),
            'singular_name'      => __('Item da Agenda', 'smtt-intranet'),
            'menu_name'          => __('Agenda', 'smtt-intranet'),
            'add_new'            => __('Adicionar novo', 'smtt-intranet'),
            'add_new_item'       => __('Adicionar item da agenda', 'smtt-intranet'),
            'edit_item'          => __('Editar item da agenda', 'smtt-intranet'),
            'new_item'           => __('Novo item da agenda', 'smtt-intranet'),
            'view_item'          => __('Ver item da agenda', 'smtt-intranet'),
            'search_items'       => __('Buscar agenda', 'smtt-intranet'),
            'not_found'          => __('Nenhum item encontrado', 'smtt-intranet'),
            'not_found_in_trash' => __('Nenhum item encontrado na lixeira', 'smtt-intranet'),
        );

        $args = array(
            'labels'          => $labels,
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => 'smtt-intranet',
            'supports'        => array('title'),
            'taxonomies'      => array('smtt_sector', 'smtt_agenda_space', 'smtt_agenda_category'),
            'capability_type' => 'post',
            'map_meta_cap'    => true,
        );

        register_post_type(self::POST_TYPE, $args);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'smtt_agenda_details',
            __('Dados da Agenda', 'smtt-intranet'),
            array($this, 'render_details_meta_box'),
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render_details_meta_box($post) {
        wp_nonce_field('smtt_save_agenda', 'smtt_agenda_nonce');

        $item_type         = get_post_meta($post->ID, '_smtt_item_type', true);
        $description       = get_post_meta($post->ID, '_smtt_description', true);
        $responsible_name  = get_post_meta($post->ID, '_smtt_responsible_name', true);
        $event_date        = get_post_meta($post->ID, '_smtt_event_date', true);
        $start_time        = get_post_meta($post->ID, '_smtt_start_time', true);
        $end_time          = get_post_meta($post->ID, '_smtt_end_time', true);
        $location_text     = get_post_meta($post->ID, '_smtt_location_text', true);
        $status            = get_post_meta($post->ID, '_smtt_status', true);
        $featured          = get_post_meta($post->ID, '_smtt_featured', true);
        $public_visible    = get_post_meta($post->ID, '_smtt_public_visible', true);

        $sector_terms = wp_get_object_terms($post->ID, 'smtt_sector', array('fields' => 'ids'));
        $selected_sector = !empty($sector_terms) ? (int) $sector_terms[0] : 0;

        $space_terms = wp_get_object_terms($post->ID, 'smtt_agenda_space', array('fields' => 'ids'));
        $selected_space = !empty($space_terms) ? (int) $space_terms[0] : 0;

        $category_terms = wp_get_object_terms($post->ID, 'smtt_agenda_category', array('fields' => 'ids'));
        $selected_category = !empty($category_terms) ? (int) $category_terms[0] : 0;
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="smtt_item_type"><?php echo esc_html__('Tipo', 'smtt-intranet'); ?></label></th>
                <td>
                    <select name="smtt_item_type" id="smtt_item_type">
                        <option value="evento" <?php selected($item_type, 'evento'); ?>><?php echo esc_html__('Evento', 'smtt-intranet'); ?></option>
                        <option value="reserva" <?php selected($item_type, 'reserva'); ?>><?php echo esc_html__('Reserva', 'smtt-intranet'); ?></option>
                    </select>
                </td>
            </tr>

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
                <th scope="row"><label for="smtt_agenda_category_term"><?php echo esc_html__('Categoria', 'smtt-intranet'); ?></label></th>
                <td>
                    <?php
                    wp_dropdown_categories(array(
                        'taxonomy'         => 'smtt_agenda_category',
                        'name'             => 'smtt_agenda_category_term',
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
                <th scope="row"><label for="smtt_agenda_space_term"><?php echo esc_html__('Espaço/Local reservado', 'smtt-intranet'); ?></label></th>
                <td>
                    <?php
                    wp_dropdown_categories(array(
                        'taxonomy'         => 'smtt_agenda_space',
                        'name'             => 'smtt_agenda_space_term',
                        'orderby'          => 'name',
                        'show_option_none' => __('Selecione um espaço', 'smtt-intranet'),
                        'hide_empty'       => false,
                        'hierarchical'     => true,
                        'selected'         => $selected_space,
                    ));
                    ?>
                    <p class="description"><?php echo esc_html__('Para reservas de sala, este campo deve ser preenchido.', 'smtt-intranet'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smtt_location_text"><?php echo esc_html__('Local complementar', 'smtt-intranet'); ?></label></th>
                <td>
                    <input type="text" name="smtt_location_text" id="smtt_location_text" class="regular-text" value="<?php echo esc_attr($location_text); ?>" />
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smtt_responsible_name"><?php echo esc_html__('Responsável pela marcação', 'smtt-intranet'); ?></label></th>
                <td>
                    <input type="text" name="smtt_responsible_name" id="smtt_responsible_name" class="regular-text" value="<?php echo esc_attr($responsible_name); ?>" />
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smtt_event_date"><?php echo esc_html__('Data', 'smtt-intranet'); ?></label></th>
                <td>
                    <input type="date" name="smtt_event_date" id="smtt_event_date" value="<?php echo esc_attr($event_date); ?>" />
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smtt_start_time"><?php echo esc_html__('Hora inicial', 'smtt-intranet'); ?></label></th>
                <td>
                    <input type="time" name="smtt_start_time" id="smtt_start_time" value="<?php echo esc_attr($start_time); ?>" />
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smtt_end_time"><?php echo esc_html__('Hora final', 'smtt-intranet'); ?></label></th>
                <td>
                    <input type="time" name="smtt_end_time" id="smtt_end_time" value="<?php echo esc_attr($end_time); ?>" />
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smtt_description"><?php echo esc_html__('Descrição', 'smtt-intranet'); ?></label></th>
                <td>
                    <textarea name="smtt_description" id="smtt_description" rows="4" class="large-text"><?php echo esc_textarea($description); ?></textarea>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smtt_status"><?php echo esc_html__('Status', 'smtt-intranet'); ?></label></th>
                <td>
                    <select name="smtt_status" id="smtt_status">
                        <option value="agendado" <?php selected($status, 'agendado'); ?>><?php echo esc_html__('Agendado', 'smtt-intranet'); ?></option>
                        <option value="cancelado" <?php selected($status, 'cancelado'); ?>><?php echo esc_html__('Cancelado', 'smtt-intranet'); ?></option>
                        <option value="concluido" <?php selected($status, 'concluido'); ?>><?php echo esc_html__('Concluído', 'smtt-intranet'); ?></option>
                    </select>
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
                <th scope="row"><?php echo esc_html__('Visível na agenda interna', 'smtt-intranet'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="smtt_public_visible" value="1" <?php checked($public_visible, '1'); ?> />
                        <?php echo esc_html__('Exibir para consulta interna dos colaboradores', 'smtt-intranet'); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_meta_boxes($post_id) {
        if (!isset($_POST['smtt_agenda_nonce'])) {
            return;
        }

        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['smtt_agenda_nonce'])), 'smtt_save_agenda')) {
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

        $item_type        = isset($_POST['smtt_item_type']) ? sanitize_text_field(wp_unslash($_POST['smtt_item_type'])) : 'evento';
        $description      = isset($_POST['smtt_description']) ? sanitize_textarea_field(wp_unslash($_POST['smtt_description'])) : '';
        $responsible_name = isset($_POST['smtt_responsible_name']) ? sanitize_text_field(wp_unslash($_POST['smtt_responsible_name'])) : '';
        $event_date       = isset($_POST['smtt_event_date']) ? sanitize_text_field(wp_unslash($_POST['smtt_event_date'])) : '';
        $start_time       = isset($_POST['smtt_start_time']) ? sanitize_text_field(wp_unslash($_POST['smtt_start_time'])) : '';
        $end_time         = isset($_POST['smtt_end_time']) ? sanitize_text_field(wp_unslash($_POST['smtt_end_time'])) : '';
        $location_text    = isset($_POST['smtt_location_text']) ? sanitize_text_field(wp_unslash($_POST['smtt_location_text'])) : '';
        $status           = isset($_POST['smtt_status']) ? sanitize_text_field(wp_unslash($_POST['smtt_status'])) : 'agendado';
        $featured         = isset($_POST['smtt_featured']) ? '1' : '0';
        $public_visible   = isset($_POST['smtt_public_visible']) ? '1' : '0';

        update_post_meta($post_id, '_smtt_item_type', $item_type);
        update_post_meta($post_id, '_smtt_description', $description);
        update_post_meta($post_id, '_smtt_responsible_name', $responsible_name);
        update_post_meta($post_id, '_smtt_event_date', $event_date);
        update_post_meta($post_id, '_smtt_start_time', $start_time);
        update_post_meta($post_id, '_smtt_end_time', $end_time);
        update_post_meta($post_id, '_smtt_location_text', $location_text);
        update_post_meta($post_id, '_smtt_status', $status);
        update_post_meta($post_id, '_smtt_featured', $featured);
        update_post_meta($post_id, '_smtt_public_visible', $public_visible);

        $sector_term   = isset($_POST['smtt_sector_term']) ? intval($_POST['smtt_sector_term']) : 0;
        $space_term    = isset($_POST['smtt_agenda_space_term']) ? intval($_POST['smtt_agenda_space_term']) : 0;
        $category_term = isset($_POST['smtt_agenda_category_term']) ? intval($_POST['smtt_agenda_category_term']) : 0;

        if ($sector_term > 0) {
            wp_set_object_terms($post_id, array($sector_term), 'smtt_sector');
        } else {
            wp_set_object_terms($post_id, array(), 'smtt_sector');
        }

        if ($space_term > 0) {
            wp_set_object_terms($post_id, array($space_term), 'smtt_agenda_space');
        } else {
            wp_set_object_terms($post_id, array(), 'smtt_agenda_space');
        }

        if ($category_term > 0) {
            wp_set_object_terms($post_id, array($category_term), 'smtt_agenda_category');
        } else {
            wp_set_object_terms($post_id, array(), 'smtt_agenda_category');
        }

        if ($item_type === 'reserva' && $space_term > 0 && !empty($event_date) && !empty($start_time) && !empty($end_time) && $status !== 'cancelado') {
            if ($this->has_conflict($post_id, $space_term, $event_date, $start_time, $end_time)) {
                remove_action('save_post', array($this, 'save_meta_boxes'));

                wp_update_post(array(
                    'ID'          => $post_id,
                    'post_status' => 'draft',
                ));

                add_action('save_post', array($this, 'save_meta_boxes'));

                set_transient(
                    'smtt_agenda_conflict_' . get_current_user_id(),
                    __('Conflito detectado: já existe uma reserva nesse espaço, data e faixa de horário. O item foi salvo como rascunho.', 'smtt-intranet'),
                    60
                );
            }
        }
    }

    private function has_conflict($post_id, $space_term, $event_date, $start_time, $end_time) {
        $items = get_posts(array(
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => -1,
            'post_status'    => array('publish', 'draft', 'future', 'pending'),
            'post__not_in'   => array($post_id),
            'meta_query'     => array(
                array(
                    'key'   => '_smtt_item_type',
                    'value' => 'reserva',
                ),
                array(
                    'key'   => '_smtt_event_date',
                    'value' => $event_date,
                ),
            ),
        ));

        foreach ($items as $item) {
            $item_status = get_post_meta($item->ID, '_smtt_status', true);
            if ($item_status === 'cancelado') {
                continue;
            }

            $space_terms = wp_get_object_terms($item->ID, 'smtt_agenda_space', array('fields' => 'ids'));
            $space_terms = is_wp_error($space_terms) ? array() : array_map('intval', $space_terms);

            if (empty($space_terms) || !in_array((int) $space_term, $space_terms, true)) {
                continue;
            }

            $item_start = get_post_meta($item->ID, '_smtt_start_time', true);
            $item_end   = get_post_meta($item->ID, '_smtt_end_time', true);

            if ($start_time < $item_end && $end_time > $item_start) {
                return true;
            }
        }

        return false;
    }

    public function admin_notices() {
        $message = get_transient('smtt_agenda_conflict_' . get_current_user_id());

        if ($message) {
            delete_transient('smtt_agenda_conflict_' . get_current_user_id());

            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }
    }
}