<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Access_Gate {

    const DB_VERSION              = '1.0.0';
    const OPTION_DB_VERSION       = 'smtt_access_gate_db_version';
    const OPTION_ENABLED          = 'smtt_access_gate_enabled';
    const OPTION_VISIBILITY       = 'smtt_access_gate_visibility_seconds';
    const OPTION_RETENTION_DAYS   = 'smtt_access_gate_retention_days';
    const OPTION_API_KEY          = 'smtt_access_gate_api_key';
    const CRON_HOOK               = 'smtt_access_gate_daily_cleanup';
    const CAPABILITY              = 'smtt_manage_access_gate';

    private $repository;
    private $identity;
    private $api;

    public function __construct() {
        $this->repository = new SMTT_Intranet_Access_Gate_Repository();
        $this->identity   = new SMTT_Intranet_Access_Gate_Identity();
        $this->api        = new SMTT_Intranet_Access_Gate_API($this->repository, $this->identity);
    }

    public function register() {
        add_action('init', array($this, 'maybe_upgrade'), 5);
        add_action('rest_api_init', array($this->api, 'register_routes'));
        add_action('admin_menu', array($this, 'register_admin_page'));

        add_action('show_user_profile', array($this, 'render_user_identity_fields'));
        add_action('edit_user_profile', array($this, 'render_user_identity_fields'));
        add_action('personal_options_update', array($this, 'save_user_identity_fields'));
        add_action('edit_user_profile_update', array($this, 'save_user_identity_fields'));

        add_action('admin_post_smtt_access_gate_save_settings', array($this, 'handle_save_settings'));
        add_action('admin_post_smtt_access_gate_rotate_key', array($this, 'handle_rotate_key'));
        add_action('admin_post_smtt_access_gate_prune', array($this, 'handle_prune'));
        add_action(self::CRON_HOOK, array($this, 'run_cleanup'));

        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK);
        }
    }

    public static function is_enabled() {
        return get_option(self::OPTION_ENABLED, '1') === '1';
    }

    public static function visibility_seconds() {
        return max(1, min(15, (int) get_option(self::OPTION_VISIBILITY, 2)));
    }

    public static function ensure_defaults() {
        add_option(self::OPTION_ENABLED, '1');
        add_option(self::OPTION_VISIBILITY, 2);
        add_option(self::OPTION_RETENTION_DAYS, 90);

        if (!get_option(self::OPTION_API_KEY, '')) {
            update_option(self::OPTION_API_KEY, wp_generate_password(48, false, false), false);
        }
    }

    public static function create_table() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = $wpdb->prefix . 'smtt_intranet_access_gate';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            identity VARCHAR(190) NOT NULL,
            windows_username VARCHAR(190) DEFAULT NULL,
            user_login VARCHAR(190) NOT NULL,
            display_name VARCHAR(190) DEFAULT NULL,
            computer_name VARCHAR(190) DEFAULT NULL,
            ip_address VARCHAR(64) DEFAULT NULL,
            access_date DATE NOT NULL,
            first_seen_at DATETIME NOT NULL,
            last_seen_at DATETIME NOT NULL,
            authorized_at DATETIME NOT NULL,
            valid_until DATETIME NOT NULL,
            source VARCHAR(40) NOT NULL DEFAULT 'browser',
            status VARCHAR(30) NOT NULL DEFAULT 'authorized',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_user_date (user_id, access_date),
            KEY idx_identity (identity),
            KEY idx_windows_username (windows_username),
            KEY idx_user_login (user_login),
            KEY idx_access_date (access_date),
            KEY idx_valid_until (valid_until),
            KEY idx_status (status)
        ) {$charset_collate};";

        dbDelta($sql);
        update_option(self::OPTION_DB_VERSION, self::DB_VERSION, false);
        self::ensure_defaults();
    }

    public function maybe_upgrade() {
        if (get_option(self::OPTION_DB_VERSION, '') !== self::DB_VERSION) {
            self::create_table();
        } else {
            self::ensure_defaults();
        }
    }

    public static function enqueue_detector() {
        if (!self::is_enabled() || !is_user_logged_in()) {
            return;
        }

        $handle = 'smtt-intranet-access-gate';
        $src = SMTT_INTRANET_URL . 'assets/public/js/access-gate.js';
        $path = SMTT_INTRANET_PATH . 'assets/public/js/access-gate.js';

        wp_register_script(
            $handle,
            $src,
            array(),
            file_exists($path) ? (string) filemtime($path) : SMTT_INTRANET_VERSION,
            true
        );

        wp_localize_script($handle, 'SMTTAccessGate', array(
            'enabled'           => true,
            'passUrl'           => esc_url_raw(rest_url('smtt-intranet/v1/gate/pass')),
            'startUrl'          => esc_url_raw(rest_url('smtt-intranet/v1/gate/start')),
            'statusUrl'         => esc_url_raw(rest_url('smtt-intranet/v1/gate/status')),
            'nonce'             => wp_create_nonce('wp_rest'),
            'visibilitySeconds' => self::visibility_seconds(),
        ));

        wp_enqueue_script($handle);
    }

    public function register_admin_page() {
        add_submenu_page(
            null,
            __('Access Gate', 'smtt-intranet'),
            __('Access Gate', 'smtt-intranet'),
            self::CAPABILITY,
            'smtt-intranet-access-gate',
            array($this, 'render_admin_page')
        );
    }

    public function render_user_identity_fields($user) {
        if (!current_user_can(self::CAPABILITY) || !current_user_can('edit_user', $user->ID)) {
            return;
        }

        $windows = (string) get_user_meta($user->ID, SMTT_Intranet_Access_Gate_Identity::META_WINDOWS_USERNAME, true);
        $upn = (string) get_user_meta($user->ID, SMTT_Intranet_Access_Gate_Identity::META_UPN, true);
        ?>
        <h2><?php echo esc_html__('Access Gate / Identidade corporativa', 'smtt-intranet'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="smtt_access_gate_windows_username"><?php echo esc_html__('Usuário Windows', 'smtt-intranet'); ?></label></th>
                <td>
                    <input type="text" class="regular-text" id="smtt_access_gate_windows_username" name="smtt_access_gate_windows_username" value="<?php echo esc_attr($windows); ?>" placeholder="DOMINIO\\usuario">
                    <p class="description"><?php echo esc_html__('Identidade usada pela futura integração com Active Directory, GPO e firewall/proxy. Se vazio, o Access Gate usa o login do WordPress.', 'smtt-intranet'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="smtt_access_gate_upn"><?php echo esc_html__('UPN / e-mail corporativo', 'smtt-intranet'); ?></label></th>
                <td>
                    <input type="text" class="regular-text" id="smtt_access_gate_upn" name="smtt_access_gate_upn" value="<?php echo esc_attr($upn); ?>" placeholder="usuario@dominio.local">
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_user_identity_fields($user_id) {
        if (!current_user_can(self::CAPABILITY) || !current_user_can('edit_user', $user_id)) {
            return;
        }

        if (isset($_POST['smtt_access_gate_windows_username'])) {
            update_user_meta(
                $user_id,
                SMTT_Intranet_Access_Gate_Identity::META_WINDOWS_USERNAME,
                sanitize_text_field(wp_unslash($_POST['smtt_access_gate_windows_username']))
            );
        }

        if (isset($_POST['smtt_access_gate_upn'])) {
            update_user_meta(
                $user_id,
                SMTT_Intranet_Access_Gate_Identity::META_UPN,
                sanitize_text_field(wp_unslash($_POST['smtt_access_gate_upn']))
            );
        }
    }

    public function handle_save_settings() {
        $this->guard_admin_action('smtt_access_gate_save_settings');

        update_option(self::OPTION_ENABLED, isset($_POST['enabled']) ? '1' : '0', false);
        update_option(
            self::OPTION_VISIBILITY,
            max(1, min(15, isset($_POST['visibility_seconds']) ? (int) $_POST['visibility_seconds'] : 2)),
            false
        );
        update_option(
            self::OPTION_RETENTION_DAYS,
            max(7, min(730, isset($_POST['retention_days']) ? (int) $_POST['retention_days'] : 90)),
            false
        );

        $this->redirect_with_notice('settings_saved');
    }

    public function handle_rotate_key() {
        $this->guard_admin_action('smtt_access_gate_rotate_key');
        update_option(self::OPTION_API_KEY, wp_generate_password(48, false, false), false);
        $this->redirect_with_notice('key_rotated');
    }

    public function handle_prune() {
        $this->guard_admin_action('smtt_access_gate_prune');
        $deleted = $this->run_cleanup();
        $this->redirect_with_notice('pruned', array('deleted' => (int) $deleted));
    }

    public function run_cleanup() {
        $days = max(7, (int) get_option(self::OPTION_RETENTION_DAYS, 90));
        return $this->repository->prune($days);
    }

    private function guard_admin_action($nonce_action) {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('Você não tem permissão para administrar o Access Gate.', 'smtt-intranet'));
        }

        check_admin_referer($nonce_action);
    }

    private function redirect_with_notice($notice, $extra = array()) {
        $args = array_merge(array(
            'page'        => 'smtt-intranet-access-gate',
            'smtt_notice' => sanitize_key($notice),
        ), $extra);

        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }

    public function render_admin_page() {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('Você não tem permissão para acessar esta página.', 'smtt-intranet'));
        }

        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $paged = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
        $per_page = 50;
        $offset = ($paged - 1) * $per_page;
        $rows = $this->repository->get_recent($per_page, $offset, $search);
        $total_rows = $this->repository->count_recent($search);
        $total_pages = max(1, (int) ceil($total_rows / $per_page));
        $authorized_today = $this->repository->count_authorized_today();
        $user_counts = count_users();
        $total_users = isset($user_counts['total_users']) ? (int) $user_counts['total_users'] : 0;
        $pending_today = max(0, $total_users - $authorized_today);
        $api_key = (string) get_option(self::OPTION_API_KEY, '');
        $enabled = self::is_enabled();
        $visibility = self::visibility_seconds();
        $retention = max(7, (int) get_option(self::OPTION_RETENTION_DAYS, 90));
        $notice = isset($_GET['smtt_notice']) ? sanitize_key(wp_unslash($_GET['smtt_notice'])) : '';
        ?>
        <div class="wrap smtt-intranet-admin smtt-access-gate-admin">
            <div class="smtt-page-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=smtt-intranet')); ?>" class="button button-secondary">← <?php echo esc_html__('Voltar ao Dashboard', 'smtt-intranet'); ?></a>
            </div>

            <div class="smtt-admin-header smtt-admin-header-v2">
                <div>
                    <h1><?php echo esc_html__('Access Gate', 'smtt-intranet'); ?></h1>
                    <p class="smtt-admin-subtitle"><?php echo esc_html__('Registra automaticamente a passagem pela Intranet e fornece o estado de autorização para a futura integração com GPO e firewall/proxy.', 'smtt-intranet'); ?></p>
                </div>
            </div>

            <?php if ($notice === 'settings_saved') : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html__('Configurações salvas.', 'smtt-intranet'); ?></p></div>
            <?php elseif ($notice === 'key_rotated') : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html__('Chave da API rotacionada. Atualize a integração de rede antes de usar a nova chave em produção.', 'smtt-intranet'); ?></p></div>
            <?php elseif ($notice === 'pruned') : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html(sprintf(__('Limpeza concluída. Registros removidos: %d.', 'smtt-intranet'), isset($_GET['deleted']) ? (int) $_GET['deleted'] : 0)); ?></p></div>
            <?php endif; ?>

            <div class="smtt-gate-status-banner <?php echo $enabled ? 'is-enabled' : 'is-disabled'; ?>">
                <strong><?php echo $enabled ? esc_html__('ATIVO — Modo auditoria/integração', 'smtt-intranet') : esc_html__('DESATIVADO', 'smtt-intranet'); ?></strong>
                <span><?php echo esc_html__('Nesta fase o plugin apenas registra e consulta autorizações; ele não bloqueia a Internet por conta própria.', 'smtt-intranet'); ?></span>
            </div>

            <div class="smtt-gate-metrics">
                <div class="smtt-gate-metric"><span><?php echo esc_html__('Passaram hoje', 'smtt-intranet'); ?></span><strong><?php echo esc_html(number_format_i18n($authorized_today)); ?></strong></div>
                <div class="smtt-gate-metric"><span><?php echo esc_html__('Usuários WP sem passagem hoje', 'smtt-intranet'); ?></span><strong><?php echo esc_html(number_format_i18n($pending_today)); ?></strong></div>
                <div class="smtt-gate-metric"><span><?php echo esc_html__('Validade', 'smtt-intranet'); ?></span><strong><?php echo esc_html__('até 23:59', 'smtt-intranet'); ?></strong></div>
                <div class="smtt-gate-metric"><span><?php echo esc_html__('Exibição mínima', 'smtt-intranet'); ?></span><strong><?php echo esc_html($visibility . 's'); ?></strong></div>
            </div>

            <div class="smtt-gate-grid">
                <section class="smtt-admin-panel">
                    <div class="smtt-admin-panel-header">
                        <h2><?php echo esc_html__('Configuração', 'smtt-intranet'); ?></h2>
                    </div>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="smtt_access_gate_save_settings">
                        <?php wp_nonce_field('smtt_access_gate_save_settings'); ?>
                        <table class="form-table">
                            <tr>
                                <th><?php echo esc_html__('Access Gate', 'smtt-intranet'); ?></th>
                                <td><label><input type="checkbox" name="enabled" value="1" <?php checked($enabled); ?>> <?php echo esc_html__('Registrar passagem automaticamente na Home da Intranet', 'smtt-intranet'); ?></label></td>
                            </tr>
                            <tr>
                                <th><label for="visibility_seconds"><?php echo esc_html__('Tempo mínimo visível', 'smtt-intranet'); ?></label></th>
                                <td><input type="number" min="1" max="15" id="visibility_seconds" name="visibility_seconds" value="<?php echo esc_attr($visibility); ?>" class="small-text"> <?php echo esc_html__('segundos', 'smtt-intranet'); ?></td>
                            </tr>
                            <tr>
                                <th><label for="retention_days"><?php echo esc_html__('Retenção do histórico', 'smtt-intranet'); ?></label></th>
                                <td><input type="number" min="7" max="730" id="retention_days" name="retention_days" value="<?php echo esc_attr($retention); ?>" class="small-text"> <?php echo esc_html__('dias', 'smtt-intranet'); ?></td>
                            </tr>
                        </table>
                        <?php submit_button(__('Salvar configurações', 'smtt-intranet')); ?>
                    </form>
                </section>

                <section class="smtt-admin-panel">
                    <div class="smtt-admin-panel-header">
                        <h2><?php echo esc_html__('API para firewall/proxy', 'smtt-intranet'); ?></h2>
                    </div>
                    <p><?php echo esc_html__('Endpoint de consulta por identidade corporativa:', 'smtt-intranet'); ?></p>
                    <code><?php echo esc_html(rest_url('smtt-intranet/v1/gate/network/status?identity=DOMINIO%5Cusuario')); ?></code>
                    <p style="margin-top:16px;"><strong><?php echo esc_html__('Cabeçalho obrigatório:', 'smtt-intranet'); ?></strong></p>
                    <code>X-SMTT-Gate-Key: <?php echo esc_html($api_key); ?></code>
                    <p class="description"><?php echo esc_html__('A chave só deve ser compartilhada com a infraestrutura responsável pela integração. O endpoint informa se a identidade está autorizada e até quando.', 'smtt-intranet'); ?></p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('Rotacionar a chave invalida a chave atual. Continuar?');">
                        <input type="hidden" name="action" value="smtt_access_gate_rotate_key">
                        <?php wp_nonce_field('smtt_access_gate_rotate_key'); ?>
                        <?php submit_button(__('Rotacionar chave da API', 'smtt-intranet'), 'secondary', 'submit', false); ?>
                    </form>
                </section>
            </div>

            <section class="smtt-admin-panel smtt-gate-history">
                <div class="smtt-admin-panel-header smtt-gate-history-header">
                    <div>
                        <h2><?php echo esc_html__('Histórico de passagens', 'smtt-intranet'); ?></h2>
                        <p><?php echo esc_html__('Uma autorização por usuário/dia. Novas visualizações no mesmo dia atualizam o último acesso sem criar duplicatas.', 'smtt-intranet'); ?></p>
                    </div>
                    <form method="get" class="smtt-gate-search">
                        <input type="hidden" name="page" value="smtt-intranet-access-gate">
                        <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Nome, login, identidade ou IP">
                        <button class="button"><?php echo esc_html__('Pesquisar', 'smtt-intranet'); ?></button>
                    </form>
                </div>

                <div class="smtt-gate-table-wrap">
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Usuário', 'smtt-intranet'); ?></th>
                                <th><?php echo esc_html__('Identidade', 'smtt-intranet'); ?></th>
                                <th><?php echo esc_html__('IP', 'smtt-intranet'); ?></th>
                                <th><?php echo esc_html__('Passagem', 'smtt-intranet'); ?></th>
                                <th><?php echo esc_html__('Válido até', 'smtt-intranet'); ?></th>
                                <th><?php echo esc_html__('Estado atual', 'smtt-intranet'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($rows)) : ?>
                            <tr><td colspan="6"><?php echo esc_html__('Nenhuma passagem registrada ainda.', 'smtt-intranet'); ?></td></tr>
                        <?php else : ?>
                            <?php foreach ($rows as $row) : ?>
                                <?php $is_authorized = $this->repository->is_row_authorized($row); ?>
                                <tr>
                                    <td><strong><?php echo esc_html($row['display_name']); ?></strong><br><small><?php echo esc_html($row['user_login']); ?></small></td>
                                    <td><?php echo esc_html($row['identity']); ?><?php if (!empty($row['computer_name'])) : ?><br><small><?php echo esc_html($row['computer_name']); ?></small><?php endif; ?></td>
                                    <td><?php echo esc_html($row['ip_address']); ?></td>
                                    <td><?php echo esc_html(mysql2date('d/m/Y H:i:s', $row['authorized_at'])); ?></td>
                                    <td><?php echo esc_html(mysql2date('d/m/Y H:i:s', $row['valid_until'])); ?></td>
                                    <td><span class="smtt-gate-badge <?php echo $is_authorized ? 'is-authorized' : 'is-pending'; ?>"><?php echo $is_authorized ? esc_html__('LIBERADO', 'smtt-intranet') : esc_html__('EXPIRADO', 'smtt-intranet'); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1) : ?>
                    <div class="tablenav"><div class="tablenav-pages">
                        <?php echo wp_kses_post(paginate_links(array(
                            'base'      => add_query_arg(array('page' => 'smtt-intranet-access-gate', 's' => $search, 'paged' => '%#%'), admin_url('admin.php')),
                            'format'    => '',
                            'current'   => $paged,
                            'total'     => $total_pages,
                            'prev_text' => '‹',
                            'next_text' => '›',
                        ))); ?>
                    </div></div>
                <?php endif; ?>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('Executar agora a limpeza conforme o período de retenção?');">
                    <input type="hidden" name="action" value="smtt_access_gate_prune">
                    <?php wp_nonce_field('smtt_access_gate_prune'); ?>
                    <?php submit_button(__('Executar limpeza agora', 'smtt-intranet'), 'secondary', 'submit', false); ?>
                </form>
            </section>
        </div>
        <?php
    }
}
