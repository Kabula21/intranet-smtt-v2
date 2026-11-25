<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_User_Sector {

    const META_KEY = 'smtt_user_sector';

    public function register() {
        add_action('show_user_profile', array($this, 'render_field'));
        add_action('edit_user_profile', array($this, 'render_field'));

        add_action('personal_options_update', array($this, 'save_field'));
        add_action('edit_user_profile_update', array($this, 'save_field'));
    }

    public function render_field($user) {
        if (!current_user_can('edit_user', $user->ID)) {
            return;
        }

        $selected_sector = (int) get_user_meta($user->ID, self::META_KEY, true);
        ?>
        <h2><?php echo esc_html__('SMTT Intranet', 'smtt-intranet'); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="smtt_user_sector"><?php echo esc_html__('Setor do usuário', 'smtt-intranet'); ?></label></th>
                <td>
                    <?php
                    wp_dropdown_categories(array(
                        'taxonomy'         => 'smtt_sector',
                        'name'             => 'smtt_user_sector',
                        'id'               => 'smtt_user_sector',
                        'orderby'          => 'name',
                        'show_option_none' => __('Sem setor definido', 'smtt-intranet'),
                        'option_none_value'=> '0',
                        'hide_empty'       => false,
                        'hierarchical'     => true,
                        'selected'         => $selected_sector,
                    ));
                    ?>
                    <p class="description">
                        <?php echo esc_html__('Define qual setor esse usuário pertence para filtrar os acessos da intranet.', 'smtt-intranet'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_field($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        $sector_id = isset($_POST['smtt_user_sector']) ? intval($_POST['smtt_user_sector']) : 0;
        update_user_meta($user_id, self::META_KEY, $sector_id);
    }
}