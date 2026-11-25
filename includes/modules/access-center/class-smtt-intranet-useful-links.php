<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Useful_Links {

    const POST_TYPE = 'smtt_useful_link';
    const SEED_OPTION = 'smtt_useful_links_seeded_v1';

    public function register() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_' . self::POST_TYPE, array($this, 'save_meta_boxes'));
        add_action('init', array($this, 'seed_defaults_once'), 30);
    }

    public function register_post_type() {
        $labels = array(
            'name'               => __('Links úteis', 'smtt-intranet'),
            'singular_name'      => __('Link útil', 'smtt-intranet'),
            'menu_name'          => __('Links úteis', 'smtt-intranet'),
            'add_new'            => __('Adicionar novo', 'smtt-intranet'),
            'add_new_item'       => __('Adicionar link útil', 'smtt-intranet'),
            'edit_item'          => __('Editar link útil', 'smtt-intranet'),
            'new_item'           => __('Novo link útil', 'smtt-intranet'),
            'search_items'       => __('Buscar links úteis', 'smtt-intranet'),
            'not_found'          => __('Nenhum link útil encontrado', 'smtt-intranet'),
            'not_found_in_trash' => __('Nenhum link útil encontrado na lixeira', 'smtt-intranet'),
        );

        register_post_type(self::POST_TYPE, array(
            'labels'          => $labels,
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => 'smtt-intranet',
            'menu_icon'       => 'dashicons-admin-links',
            'supports'        => array('title'),
            'capability_type' => 'post',
            'map_meta_cap'    => true,
        ));
    }

    public function add_meta_boxes() {
        add_meta_box(
            'smtt_useful_link_details',
            __('Dados do link', 'smtt-intranet'),
            array($this, 'render_meta_box'),
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('smtt_save_useful_link', 'smtt_useful_link_nonce');

        $url         = get_post_meta($post->ID, '_smtt_url', true);
        $description = get_post_meta($post->ID, '_smtt_description', true);
        $icon        = get_post_meta($post->ID, '_smtt_icon', true);
        $sort_order  = get_post_meta($post->ID, '_smtt_sort_order', true);
        $status      = get_post_meta($post->ID, '_smtt_status', true);
        $new_tab     = get_post_meta($post->ID, '_smtt_new_tab', true);

        if ($icon === '') {
            $icon = 'document-check';
        }
        if ($status === '') {
            $status = 'ativo';
        }
        if ($new_tab === '') {
            $new_tab = '1';
        }

        $icons = array(
            'document-check' => 'Documento',
            'brain'          => 'Tecnologia',
            'shopping-cart'  => 'Compras',
            'newspaper'      => 'Notícias / Diário',
            'id-card'        => 'Servidor / Identidade',
            'settings'       => 'Configurações',
            'landmark'       => 'Órgão público',
            'car'            => 'Trânsito / Veículo',
            'route'          => 'Mobilidade / Rota',
            'headset'        => 'Atendimento',
            'eye'            => 'Transparência',
            'mail'           => 'E-mail',
        );
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="smtt_useful_link_url"><?php echo esc_html__('URL', 'smtt-intranet'); ?></label></th>
                <td><input type="url" class="large-text" name="smtt_useful_link_url" id="smtt_useful_link_url" value="<?php echo esc_attr($url); ?>" placeholder="https://..."></td>
            </tr>
            <tr>
                <th scope="row"><label for="smtt_useful_link_description"><?php echo esc_html__('Descrição', 'smtt-intranet'); ?></label></th>
                <td><input type="text" class="large-text" name="smtt_useful_link_description" id="smtt_useful_link_description" value="<?php echo esc_attr($description); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><label for="smtt_useful_link_icon"><?php echo esc_html__('Ícone', 'smtt-intranet'); ?></label></th>
                <td>
                    <select name="smtt_useful_link_icon" id="smtt_useful_link_icon">
                        <?php foreach ($icons as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($icon, $value); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="smtt_useful_link_sort_order"><?php echo esc_html__('Ordem de exibição', 'smtt-intranet'); ?></label></th>
                <td><input type="number" class="small-text" name="smtt_useful_link_sort_order" id="smtt_useful_link_sort_order" value="<?php echo esc_attr($sort_order !== '' ? $sort_order : 0); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><label for="smtt_useful_link_status"><?php echo esc_html__('Status', 'smtt-intranet'); ?></label></th>
                <td>
                    <select name="smtt_useful_link_status" id="smtt_useful_link_status">
                        <option value="ativo" <?php selected($status, 'ativo'); ?>><?php echo esc_html__('Ativo', 'smtt-intranet'); ?></option>
                        <option value="inativo" <?php selected($status, 'inativo'); ?>><?php echo esc_html__('Inativo', 'smtt-intranet'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html__('Abrir', 'smtt-intranet'); ?></th>
                <td>
                    <label><input type="checkbox" name="smtt_useful_link_new_tab" value="1" <?php checked($new_tab, '1'); ?>> <?php echo esc_html__('Abrir em nova aba', 'smtt-intranet'); ?></label>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_meta_boxes($post_id) {
        if (!isset($_POST['smtt_useful_link_nonce'])) {
            return;
        }

        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['smtt_useful_link_nonce'])), 'smtt_save_useful_link')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        update_post_meta($post_id, '_smtt_url', isset($_POST['smtt_useful_link_url']) ? esc_url_raw(wp_unslash($_POST['smtt_useful_link_url'])) : '');
        update_post_meta($post_id, '_smtt_description', isset($_POST['smtt_useful_link_description']) ? sanitize_text_field(wp_unslash($_POST['smtt_useful_link_description'])) : '');
        update_post_meta($post_id, '_smtt_icon', isset($_POST['smtt_useful_link_icon']) ? sanitize_key(wp_unslash($_POST['smtt_useful_link_icon'])) : 'document-check');
        update_post_meta($post_id, '_smtt_sort_order', isset($_POST['smtt_useful_link_sort_order']) ? intval($_POST['smtt_useful_link_sort_order']) : 0);
        update_post_meta($post_id, '_smtt_status', isset($_POST['smtt_useful_link_status']) ? sanitize_key(wp_unslash($_POST['smtt_useful_link_status'])) : 'ativo');
        update_post_meta($post_id, '_smtt_new_tab', isset($_POST['smtt_useful_link_new_tab']) ? '1' : '0');
    }

    public function seed_defaults_once() {
        if (get_option(self::SEED_OPTION)) {
            return;
        }

        $existing = get_posts(array(
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => 1,
            'post_status'    => array('publish', 'draft', 'private'),
            'fields'         => 'ids',
        ));

        if (!empty($existing)) {
            update_option(self::SEED_OPTION, '1', false);
            return;
        }

        $defaults = array(
            array('Aracaju 1Doc', 'https://aracaju.1doc.com.br/', 'document-check'),
            array('Aracaju Inteligente', '', 'brain'),
            array('Aracaju Compras', '', 'shopping-cart'),
            array('D.O Aracaju', '', 'newspaper'),
            array('Portal do Servidor', '', 'id-card'),
            array('SGA Aracaju', '', 'settings'),
            array('Portal Prefeitura de Aracaju', '', 'landmark'),
            array('Portal Detran', '', 'car'),
            array('Portal SMTT', '', 'route'),
            array('Ouvidoria SMTT', '', 'headset'),
            array('Portal Transparência', '', 'eye'),
            array('Webmail', '', 'mail'),
        );

        foreach ($defaults as $index => $item) {
            $post_id = wp_insert_post(array(
                'post_type'   => self::POST_TYPE,
                'post_status' => 'publish',
                'post_title'  => $item[0],
            ));

            if (is_wp_error($post_id) || !$post_id) {
                continue;
            }

            update_post_meta($post_id, '_smtt_url', $item[1]);
            update_post_meta($post_id, '_smtt_icon', $item[2]);
            update_post_meta($post_id, '_smtt_sort_order', $index + 1);
            update_post_meta($post_id, '_smtt_status', 'ativo');
            update_post_meta($post_id, '_smtt_new_tab', '1');
        }

        update_option(self::SEED_OPTION, '1', false);
    }
}
