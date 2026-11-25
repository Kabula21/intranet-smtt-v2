<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SMTT_Intranet_Chat_Header')) {

    class SMTT_Intranet_Chat_Header {

        const SHORTCODE = 'smtt_chat_header';

        public function register() {
            add_shortcode(self::SHORTCODE, array($this, 'render_shortcode'));
            add_action('wp_enqueue_scripts', array($this, 'maybe_enqueue_assets'));
        }

        public function maybe_enqueue_assets() {
            // O novo Chat SMTT utiliza folha de estilo e JavaScript próprios.
            if (class_exists('SMTT_Intranet_Chat_Integration') && SMTT_Intranet_Chat_Integration::is_chat_page()) { return; }
            if (!is_singular()) {
                return;
            }

            $post = get_post();

            if (!$post || empty($post->post_content)) {
                return;
            }

            if (!has_shortcode($post->post_content, self::SHORTCODE)) {
                return;
            }

            $this->enqueue_public_assets();
        }

        private function enqueue_public_assets() {
            if (!wp_style_is('smtt-intranet-public', 'registered')) {
                wp_register_style(
                    'smtt-intranet-public',
                    SMTT_INTRANET_URL . 'assets/public/css/public.css',
                    array(),
                    defined('SMTT_INTRANET_VERSION') ? SMTT_INTRANET_VERSION : '1.0.0'
                );
            }

            if (!wp_script_is('smtt-intranet-public', 'registered')) {
                wp_register_script(
                    'smtt-intranet-public',
                    SMTT_INTRANET_URL . 'assets/public/js/public.js',
                    array(),
                    defined('SMTT_INTRANET_VERSION') ? SMTT_INTRANET_VERSION : '1.0.0',
                    true
                );
            }

            wp_enqueue_style('smtt-intranet-public');
            wp_enqueue_script('smtt-intranet-public');
        }

        public function render_shortcode($atts = array()) {
            $this->enqueue_public_assets();

            $atts = shortcode_atts(
                array(
                    'title'       => 'Chat Interno SMTT',
                    'badge'       => 'AMBIENTE CORPORATIVO',
                    'description' => 'Comunicação oficial entre setores, equipes e servidores da SMTT/Aracaju.',
                ),
                $atts,
                self::SHORTCODE
            );

            $user_data = $this->get_logged_user_data();

            ob_start();
            ?>

            <div class="smtt-chat-header">
                <div class="smtt-chat-top">

                    <div class="smtt-chat-left">
                        <div class="smtt-chat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M7.5 15.5H6.2C4.4 15.5 3 14.1 3 12.3V7.2C3 5.4 4.4 4 6.2 4H14.8C16.6 4 18 5.4 18 7.2V12.3C18 14.1 16.6 15.5 14.8 15.5H11L7.5 18.5V15.5Z"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"/>
                                <path d="M10 8.5H14"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"/>
                                <path d="M7 11.3H14"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"/>
                                <path d="M17.8 9H18.2C19.8 9 21 10.2 21 11.8V15.6C21 17.2 19.8 18.4 18.2 18.4H17.3V21L14.2 18.4H11.7"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"/>
                            </svg>
                        </div>

                        <div class="smtt-chat-title-area">
                            <span class="smtt-chat-badge">
                                <?php echo esc_html($atts['badge']); ?>
                            </span>

                            <h2><?php echo esc_html($atts['title']); ?></h2>

                            <p>
                                <?php echo esc_html($atts['description']); ?>
                            </p>
                        </div>
                    </div>

                    <div class="smtt-chat-right">
    
                        <div class="smtt-chat-user-box">
                            <div class="smtt-chat-avatar">
                                <?php if (!empty($user_data['avatar_url'])) : ?>
                                    <img src="<?php echo esc_url($user_data['avatar_url']); ?>" alt="<?php echo esc_attr($user_data['name']); ?>">
                                <?php else : ?>
                                    <span><?php echo esc_html($user_data['initial']); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="smtt-chat-user-info">
                                <span>
                                    <?php echo $user_data['is_logged'] ? '' : 'Acesso não identificado'; ?>
                                </span>

                                <strong>
                                    <?php echo esc_html($user_data['name']); ?>
                                </strong>

                                <small>
                                    <?php echo esc_html($user_data['role']); ?>

                                    <?php if (!empty($user_data['sector'])) : ?>
                                        · <?php echo esc_html($user_data['sector']); ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>

                        <div class="smtt-chat-status">
                            <span class="dot"></span>

                            <span class="smtt-chat-status-text">
                                <strong>
                                    <?php echo $user_data['is_logged'] ? 'Online' : 'Acesso Restrito'; ?>
                                </strong>

                                <small>
                                    <?php echo esc_html($user_data['date']); ?> às <?php echo esc_html($user_data['time']); ?>
                                </small>
                            </span>
                        </div>

                    </div>
                </div>
            </div>

            <?php
            return ob_get_clean();
        }

        private function get_logged_user_data() {
            $current_user = wp_get_current_user();

            $user_data = array(
                'is_logged'  => false,
                'id'         => 0,
                'name'       => 'Visitante',
                'role'       => 'Não autenticado',
                'sector'     => '',
                'avatar_url' => '',
                'initial'    => 'U',
                'date'       => date_i18n('d/m/Y', current_time('timestamp')),
                'time'       => date_i18n('H:i', current_time('timestamp')),
            );

            if (!$current_user || !$current_user->exists()) {
                return $user_data;
            }

            $user_id = $current_user->ID;

            $first_name = get_user_meta($user_id, 'first_name', true);
            $last_name  = get_user_meta($user_id, 'last_name', true);

            $user_name = trim($first_name . ' ' . $last_name);

            if (empty($user_name)) {
                if (!empty($current_user->display_name)) {
                    $user_name = $current_user->display_name;
                } elseif (!empty($current_user->user_login)) {
                    $user_name = $current_user->user_login;
                } else {
                    $user_name = 'Usuário logado';
                }
            }

            $user_role = 'Usuário WordPress';

            if (!empty($current_user->roles) && is_array($current_user->roles)) {
                $role_key = $current_user->roles[0];
                $roles_object = wp_roles();

                if (!empty($roles_object->roles[$role_key]['name'])) {
                    $user_role = translate_user_role($roles_object->roles[$role_key]['name']);
                } else {
                    $user_role = ucfirst($role_key);
                }
            }

            /*
             * Preparado para Ultimate Member.
             * Depois você pode trocar/adicionar aqui os nomes reais dos campos do UM.
             */
            $sector_keys = array(
                'smtt_setor',
                'setor',
                'departamento',
                'department',
                'user_setor',
                'user_departamento',
            );

            $user_sector = '';

            foreach ($sector_keys as $meta_key) {
                $meta_value = get_user_meta($user_id, $meta_key, true);

                if (!empty($meta_value)) {
                    $user_sector = $meta_value;
                    break;
                }
            }

            $initial = function_exists('mb_substr')
                ? mb_strtoupper(mb_substr($user_name, 0, 1))
                : strtoupper(substr($user_name, 0, 1));

            $user_data = array(
                'is_logged'  => true,
                'id'         => $user_id,
                'name'       => $user_name,
                'role'       => $user_role,
                'sector'     => $user_sector,
                'avatar_url' => get_avatar_url($user_id, array('size' => 96)),
                'initial'    => $initial,
                'date'       => date_i18n('d/m/Y', current_time('timestamp')),
                'time'       => date_i18n('H:i', current_time('timestamp')),
            );

            return apply_filters('smtt_chat_header_user_data', $user_data, $user_id);
        }
    }
}