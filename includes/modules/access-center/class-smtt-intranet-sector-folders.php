<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Sector_Folders {

    const POST_TYPE = 'smtt_sector_folder';

    public function register() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
    }

    public function register_post_type() {
        $labels = array(
            'name'               => __('Pastas dos Setores', 'smtt-intranet'),
            'singular_name'      => __('Pasta do Setor', 'smtt-intranet'),
            'menu_name'          => __('Pastas dos Setores', 'smtt-intranet'),
            'name_admin_bar'     => __('Pasta do Setor', 'smtt-intranet'),
            'add_new'            => __('Adicionar nova', 'smtt-intranet'),
            'add_new_item'       => __('Adicionar nova pasta de setor', 'smtt-intranet'),
            'edit_item'          => __('Editar pasta do setor', 'smtt-intranet'),
            'new_item'           => __('Nova pasta do setor', 'smtt-intranet'),
            'view_item'          => __('Ver pasta do setor', 'smtt-intranet'),
            'search_items'       => __('Buscar pastas dos setores', 'smtt-intranet'),
            'not_found'          => __('Nenhuma pasta encontrada', 'smtt-intranet'),
            'not_found_in_trash' => __('Nenhuma pasta encontrada na lixeira', 'smtt-intranet'),
        );

        $args = array(
            'labels'          => $labels,
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => 'smtt-intranet',
            'menu_position'   => 20,
            'menu_icon'       => 'dashicons-networking',
            'supports'        => array('title'),
            'taxonomies'      => array('smtt_sector'),
            'capability_type' => 'post',
            'map_meta_cap'    => true,
        );

        register_post_type(self::POST_TYPE, $args);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'smtt_sector_folder_details',
            __('Dados da Pasta do Setor', 'smtt-intranet'),
            array($this, 'render_details_meta_box'),
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render_details_meta_box($post) {
        wp_nonce_field('smtt_save_sector_folder', 'smtt_sector_folder_nonce');

        $description = get_post_meta($post->ID, '_smtt_description', true);
        $server_path = get_post_meta($post->ID, '_smtt_server_path', true);
        $manager     = get_post_meta($post->ID, '_smtt_manager', true);
        $status      = get_post_meta($post->ID, '_smtt_status', true);
        $icon        = get_post_meta($post->ID, '_smtt_icon', true);
        $priority    = get_post_meta($post->ID, '_smtt_priority', true);
        $featured     = get_post_meta($post->ID, '_smtt_featured', true);
        $sort_order   = get_post_meta($post->ID, '_smtt_sort_order', true);
        $show_sidebar = get_post_meta($post->ID, '_smtt_show_sidebar', true);

        if ($show_sidebar === '') {
            $show_sidebar = '1';
        }

        $sector_terms = wp_get_object_terms($post->ID, 'smtt_sector', array('fields' => 'ids'));
        $selected_sectors = (!is_wp_error($sector_terms) && !empty($sector_terms))
            ? array_map('intval', $sector_terms)
            : array();
        ?>
        <table class="form-table">
            <tr>
    <th scope="row">
        <label><?php echo esc_html__('Setores vinculados', 'smtt-intranet'); ?></label>
    </th>
    <td>
        <?php
        $sectors = get_terms(array(
            'taxonomy'   => 'smtt_sector',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ));
        ?>

        <?php if (!empty($sectors) && !is_wp_error($sectors)) : ?>
            <div style="max-height:220px;overflow:auto;border:1px solid #dcdcde;padding:10px;background:#fff;">
                <?php foreach ($sectors as $sector_item) : ?>
                    <label style="display:block;margin-bottom:8px;">
                        <input
                            type="checkbox"
                            name="smtt_sector_terms[]"
                            value="<?php echo esc_attr($sector_item->term_id); ?>"
                            <?php checked(in_array((int) $sector_item->term_id, $selected_sectors, true)); ?>
                        >
                        <?php echo esc_html($sector_item->name); ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <p class="description">
                <?php echo esc_html__('Marque todos os setores que poderão acessar esta pasta.', 'smtt-intranet'); ?>
            </p>
        <?php else : ?>
            <p><?php echo esc_html__('Nenhum setor cadastrado.', 'smtt-intranet'); ?></p>
        <?php endif; ?>
    </td>
</tr>
            <tr>
                <th scope="row"><label for="smtt_description"><?php echo esc_html__('Descrição', 'smtt-intranet'); ?></label></th>
                <td>
                    <textarea name="smtt_description" id="smtt_description" rows="4" class="large-text"><?php echo esc_textarea($description); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="smtt_server_path"><?php echo esc_html__('Caminho da pasta no servidor', 'smtt-intranet'); ?></label></th>
                <td>
                    <input type="text" name="smtt_server_path" id="smtt_server_path" class="regular-text" value="<?php echo esc_attr($server_path); ?>" placeholder="\\servidor\setores\ASCOM" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="smtt_manager"><?php echo esc_html__('Responsável', 'smtt-intranet'); ?></label></th>
                <td>
                    <input type="text" name="smtt_manager" id="smtt_manager" class="regular-text" value="<?php echo esc_attr($manager); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="smtt_icon"><?php echo esc_html__('Ícone Dashicon', 'smtt-intranet'); ?></label></th>
                <td>
                    <input type="text" name="smtt_icon" id="smtt_icon" class="regular-text" value="<?php echo esc_attr($icon); ?>" placeholder="dashicons-networking" />
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
                        <?php echo esc_html__('Mostrar em destaque na central', 'smtt-intranet'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html__('Navbar - Departamentos', 'smtt-intranet'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="smtt_show_sidebar" value="1" <?php checked($show_sidebar, '1'); ?> />
                        <?php echo esc_html__('Exibir esta pasta no menu Departamentos da navbar pública', 'smtt-intranet'); ?>
                    </label>
                    <p class="description">
                        <?php echo esc_html__('Se desmarcar, a pasta sai da navbar, mas continua disponível na Central de Acessos dos setores vinculados.', 'smtt-intranet'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="smtt_status"><?php echo esc_html__('Status', 'smtt-intranet'); ?></label></th>
                <td>
                    <select name="smtt_status" id="smtt_status">
                        <option value="ativo" <?php selected($status, 'ativo'); ?>><?php echo esc_html__('Ativo', 'smtt-intranet'); ?></option>
                        <option value="manutencao" <?php selected($status, 'manutencao'); ?>><?php echo esc_html__('Em manutenção', 'smtt-intranet'); ?></option>
                        <option value="inativo" <?php selected($status, 'inativo'); ?>><?php echo esc_html__('Inativo', 'smtt-intranet'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_meta_boxes($post_id) {
        if (!isset($_POST['smtt_sector_folder_nonce'])) {
            return;
        }

        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['smtt_sector_folder_nonce'])), 'smtt_save_sector_folder')) {
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
        update_post_meta($post_id, '_smtt_server_path', isset($_POST['smtt_server_path']) ? sanitize_text_field(wp_unslash($_POST['smtt_server_path'])) : '');
        update_post_meta($post_id, '_smtt_manager', isset($_POST['smtt_manager']) ? sanitize_text_field(wp_unslash($_POST['smtt_manager'])) : '');
        update_post_meta($post_id, '_smtt_icon', isset($_POST['smtt_icon']) ? sanitize_text_field(wp_unslash($_POST['smtt_icon'])) : 'dashicons-networking');
        update_post_meta($post_id, '_smtt_priority', isset($_POST['smtt_priority']) ? sanitize_text_field(wp_unslash($_POST['smtt_priority'])) : 'media');
        update_post_meta($post_id, '_smtt_sort_order', isset($_POST['smtt_sort_order']) ? intval($_POST['smtt_sort_order']) : 0);
        update_post_meta($post_id, '_smtt_featured', isset($_POST['smtt_featured']) ? '1' : '0');
        update_post_meta($post_id, '_smtt_show_sidebar', isset($_POST['smtt_show_sidebar']) ? '1' : '0');
        update_post_meta($post_id, '_smtt_status', isset($_POST['smtt_status']) ? sanitize_text_field(wp_unslash($_POST['smtt_status'])) : 'ativo');

        $sector_terms = array();

        if (isset($_POST['smtt_sector_terms']) && is_array($_POST['smtt_sector_terms'])) {
            $sector_terms = array_map('intval', wp_unslash($_POST['smtt_sector_terms']));
            $sector_terms = array_values(array_filter($sector_terms));
        }

        wp_set_object_terms($post_id, $sector_terms, 'smtt_sector');
    }
}