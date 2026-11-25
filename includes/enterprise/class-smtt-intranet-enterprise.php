<?php
if (!defined('ABSPATH')) { exit; }
/** Read-only AD catalog + CPD-only first administration page. */
class SMTT_Intranet_Enterprise {
    const OPTION_REWRITE = 'smtt_intranet_enterprise_rewrite';
    public function register() {
        add_action('init', array($this, 'routes'), 9);
        add_filter('query_vars', array($this, 'query_vars'));
        add_filter('pre_handle_404', array($this, 'handle_portal_status'), 10, 2);
        add_filter('redirect_canonical', array($this, 'portal_no_canonical'));
        add_action('template_redirect', array($this, 'protect_portal'), 0);
        add_filter('template_include', array($this, 'portal_template'), 99);
        add_action('wp_enqueue_scripts', array($this, 'assets'));
        add_action('wp_head', array($this, 'home_favicon'), 999);
        add_filter('show_admin_bar', array($this, 'hide_admin_bar'));
        add_action('admin_post_smtt_enterprise_set_groups', array($this, 'save_groups'));
        add_action('admin_post_smtt_enterprise_sync_ad', array($this, 'sync_now'));
        add_filter('cron_schedules', array($this, 'schedules'));
        add_action(SMTT_Intranet_AD::CRON_HOOK, array($this, 'run_sync'));
        add_action('init', array($this, 'schedule'), 19);
        add_action('init', array($this, 'ensure_schema'), 8);
        $manager = new SMTT_Intranet_Manager();
        $manager->register();
        if (defined('WP_CLI') && WP_CLI) { $this->register_cli(); }
    }
    public function ensure_schema() { SMTT_Intranet_AD::installed(); SMTT_Intranet_Policy::install(); }
    public function routes() {
        add_rewrite_rule('^administracao/?$', 'index.php?smtt_intranet_admin_portal=1', 'top');
        add_rewrite_rule('^minha-gestao/?$', 'index.php?smtt_intranet_my_management=1', 'top');
        if (get_option(self::OPTION_REWRITE) !== SMTT_INTRANET_VERSION) {
            flush_rewrite_rules(false);
            update_option(self::OPTION_REWRITE, SMTT_INTRANET_VERSION, false);
        }
    }
    public function query_vars($vars) { $vars[] = 'smtt_intranet_admin_portal'; $vars[] = 'smtt_intranet_my_management'; return $vars; }
    public function is_portal() { return (bool) get_query_var('smtt_intranet_admin_portal'); }
    public function is_mine() { return (bool) get_query_var('smtt_intranet_my_management'); }
    public function is_enterprise_page() { return $this->is_portal() || $this->is_mine(); }
    public function handle_portal_status($preempt, $query) {
        if ((bool) $query->get('smtt_intranet_admin_portal') || (bool) $query->get('smtt_intranet_my_management')) {
            $query->is_404 = false;
            status_header(200);
            return true;
        }
        return $preempt;
    }
    public function portal_no_canonical($url) {
        return $this->is_enterprise_page() ? false : $url;
    }
    public function protect_portal() {
        if (!$this->is_enterprise_page()) { return; }
        if (($this->is_portal() && !SMTT_Intranet_Corporate_Identity::is_admin()) || ($this->is_mine() && !SMTT_Intranet_Policy::has_management_access())) {
            status_header(403);
            nocache_headers();
            wp_die('A Central Administrativa é exclusiva dos integrantes autorizados do CPD, autenticados no Active Directory.', 'Acesso restrito', array('response' => 403));
        }
        if (!defined('DONOTCACHEPAGE')) { define('DONOTCACHEPAGE', true); }
        nocache_headers();
    }
    public function portal_template($template) {
        if ($this->is_enterprise_page()) { return SMTT_INTRANET_PATH . 'includes/enterprise/portal-template.php'; }
        return $template;
    }
    /** Show the Intranet brand on the Home without changing WordPress global site icon. */
    public function home_favicon() {
        if (!$this->is_enterprise_page() && !SMTT_Intranet_Home_Routing::is_home_request()) { return; }
        echo '<link rel="icon" type="image/png" href="' . esc_url(add_query_arg('v', SMTT_INTRANET_VERSION, SMTT_INTRANET_URL . 'assets/public/img/logo-smtt.png')) . '">' . "\n";
    }

    public function hide_admin_bar($show) {
        return $this->is_enterprise_page() ? false : $show;
    }

    public function assets() {
        if (!$this->is_enterprise_page()) { return; }
        wp_enqueue_style('dashicons');
        if ((string)($_GET['module']??'')==='knowledge') { wp_enqueue_editor(); }
        wp_enqueue_style('smtt-enterprise-admin', SMTT_INTRANET_URL . 'assets/enterprise/portal.css', array(), SMTT_INTRANET_VERSION);
        wp_enqueue_script('smtt-enterprise-admin', SMTT_INTRANET_URL . 'assets/enterprise/portal.js', array(), SMTT_INTRANET_VERSION, true);
        if (isset($_GET['section']) && sanitize_key(wp_unslash($_GET['section'])) === 'apps' && $this->is_portal()) {
            wp_enqueue_script('smtt-app-launcher-admin', SMTT_INTRANET_URL . 'assets/enterprise/app-launcher.js', array(), SMTT_INTRANET_VERSION, true);
        }
        if (isset($_GET['section']) && sanitize_key(wp_unslash($_GET['section'])) === 'home_layout' && $this->is_portal()) {
            wp_enqueue_script('smtt-home-layout-editor', SMTT_INTRANET_URL . 'assets/enterprise/home-layout.js', array(), SMTT_INTRANET_VERSION, true);
        }
    }
    /**
     * Link de gestão no cabeçalho da Home. A autorização é repetida no servidor;
     * esconder/exibir links nunca é tratado como controle de acesso.
     */
    public static function home_shortcuts() {
        if (is_admin() || !is_user_logged_in()) { return; }
        $is_admin = SMTT_Intranet_Corporate_Identity::is_admin();
        $is_editor = !$is_admin && SMTT_Intranet_Policy::has_management_access();
        if (!$is_admin && !$is_editor) { return; }
        $url = home_url($is_admin ? '/administracao/' : '/minha-gestao/');
        $label = $is_admin ? 'Administração' : 'Minha Gestão';
        $preview = $is_admin && SMTT_Intranet_Corporate_Identity::is_local_preview_admin();
        $title = $preview ? 'Administração — prévia local sem AD' : $label . ' da Intranet';
        echo '<a class="smtt-topbar-admin-link" href="'.esc_url($url).'" title="'.esc_attr($title).'" aria-label="'.esc_attr($title).'">';
        echo '<span class="smtt-topbar-admin-link-icon" aria-hidden="true">&#9881;</span>';
        echo '<span>'.esc_html($label).($preview ? ' <small>(teste)</small>' : '').'</span></a>';
    }
    public function schedules($schedules) {
        $schedules['smtt_fifteen_minutes'] = array('interval' => 15 * MINUTE_IN_SECONDS, 'display' => 'A cada 15 minutos (SMTT AD)');
        return $schedules;
    }
    public function schedule() {
        if (!wp_next_scheduled(SMTT_Intranet_AD::CRON_HOOK)) {
            wp_schedule_event(time() + 5 * MINUTE_IN_SECONDS, 'smtt_fifteen_minutes', SMTT_Intranet_AD::CRON_HOOK);
        }
    }
    public function run_sync() {
        if (!SMTT_Intranet_AD::config_ready()) { return; }
        $result = SMTT_Intranet_AD::sync();
        if (is_wp_error($result)) { update_option('smtt_intranet_ad_last_error', $result->get_error_message(), false); }
        else { delete_option('smtt_intranet_ad_last_error'); }
    }
    public function save_groups() {
        if (!SMTT_Intranet_Corporate_Identity::is_admin()) {
            wp_die('Sem autorização para alterar grupos.', 'Acesso restrito', array('response' => 403));
        }
        check_admin_referer('smtt_enterprise_set_groups');
        $cpd = isset($_POST['cpd_sid']) ? sanitize_text_field(wp_unslash($_POST['cpd_sid'])) : '';
        $admin = isset($_POST['admin_sid']) ? sanitize_text_field(wp_unslash($_POST['admin_sid'])) : '';
        if ($cpd === $admin || !SMTT_Intranet_AD::valid_group_sid($cpd) || !SMTT_Intranet_AD::valid_group_sid($admin)) {
            wp_die('Escolha dois grupos AD de segurança distintos, ativos e sincronizados.', 'Configuração inválida', array('response'=>400));
        }
        $identity = SMTT_Intranet_Corporate_Identity::current();
        if (is_wp_error($identity) || !in_array($cpd, $identity['groups'], true) || !in_array($admin, $identity['groups'], true)) {
            wp_die('Você precisa pertencer a ambos os novos grupos antes de salvar. Use o bootstrap técnico para uma mudança de grupos.', 'Regra de segurança', array('response'=>403));
        }
        update_option(SMTT_Intranet_AD::OPT_CPD, $cpd, false);
        update_option(SMTT_Intranet_AD::OPT_ADMINS, $admin, false);
        wp_safe_redirect(add_query_arg('smtt_notice', 'groups_saved', home_url('/administracao/')));
        exit;
    }
    public function sync_now() {
        if (!SMTT_Intranet_Corporate_Identity::is_admin()) {
            wp_die('Acesso restrito ao CPD.', 'Acesso negado', array('response' => 403));
        }
        check_admin_referer('smtt_enterprise_sync_ad');
        $result = SMTT_Intranet_AD::sync();
        if (is_wp_error($result)) {
            update_option('smtt_intranet_ad_last_error', $result->get_error_message(), false);
            $notice = 'sync_error';
        } else {
            delete_option('smtt_intranet_ad_last_error');
            $notice = 'sync_ok';
        }
        wp_safe_redirect(add_query_arg('smtt_notice', $notice, home_url('/administracao/')));
        exit;
    }

    private function register_cli() {
        // Diagnóstico somente leitura para implantação no Ubuntu.
        // Não imprime credenciais, chaves do Access Gate ou dados pessoais.
        WP_CLI::add_command('smtt-intranet preflight', function () {
            global $wpdb;
            $table_exists = function ($table) use ($wpdb) {
                return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))) === $table;
            };
            $prefix = $wpdb->prefix;
            $checks = array(
                'PHP LDAP habilitado' => function_exists('ldap_connect'),
                'URL WordPress em HTTPS' => strtolower((string) wp_parse_url(home_url('/'), PHP_URL_SCHEME)) === 'https',
                'LDAPS configurado em wp-config.php' => SMTT_Intranet_AD::config_ready(),
                'Adaptador de identidade SSO registrado' => has_filter('smtt_intranet_verified_ad_identity') !== false,
                'Grupos CPD e Admin selecionados' => (bool) get_option(SMTT_Intranet_AD::OPT_CPD) && (bool) get_option(SMTT_Intranet_AD::OPT_ADMINS),
                'Tabela de objetos AD' => $table_exists(SMTT_Intranet_AD::table()),
                'Tabelas de permissões e auditoria' => $table_exists(SMTT_Intranet_Policy::table()) && $table_exists(SMTT_Intranet_Policy::audit_table()),
                'Tabela Ramais' => $table_exists($prefix . 'smtt_intranet_ramais'),
                'Tabelas Chat SMTT' => $table_exists($prefix . 'smtt_chat_conversations') && $table_exists($prefix . 'smtt_chat_participants') && $table_exists($prefix . 'smtt_chat_messages'),
                'Tabela Access Gate' => $table_exists($prefix . 'smtt_intranet_access_gate'),
                'Página Home da Intranet' => (bool) SMTT_Intranet_Home_Routing::url(),
                'Armazenamento privado de Documentos/Wiki' => (bool) SMTT_Intranet_Content_Files::private_dir(),
            );
            foreach ($checks as $label => $ready) {
                WP_CLI::log(($ready ? '[OK] ' : '[PENDENTE] ') . $label);
            }
            WP_CLI::log('[PENDENTE] FortiGate: somente simulação; nenhuma política de rede é aplicada pelo plugin.');
            WP_CLI::log('[ATENÇÃO] Verifique regras antigas do wp-admin, ACLs da Home e proteção de URLs de documentos em homologação.');
            WP_CLI::log('[ATENÇÃO] Este diagnóstico não substitui testes reais de SSO, LDAPS, chat, WordPress e firewall.');
        });
        WP_CLI::add_command('smtt-intranet ad-sync', function () {
            $result = SMTT_Intranet_AD::sync();
            if (is_wp_error($result)) { WP_CLI::error($result->get_error_message()); }
            WP_CLI::success('Objetos sincronizados: ' . wp_json_encode($result));
        });
        WP_CLI::add_command('smtt-intranet ad-bootstrap', function ($args, $assoc) {
            $cpd = (string) ($assoc['cpd'] ?? '');
            $admin = (string) ($assoc['admin'] ?? '');
            if ($cpd === $admin || !SMTT_Intranet_AD::valid_group_sid($cpd) || !SMTT_Intranet_AD::valid_group_sid($admin)) {
                WP_CLI::error('Informe --cpd=S-1-... --admin=S-1-... com dois grupos AD distintos já sincronizados.');
            }
            update_option(SMTT_Intranet_AD::OPT_CPD, $cpd, false);
            update_option(SMTT_Intranet_AD::OPT_ADMINS, $admin, false);
            WP_CLI::success('Grupos configurados. A Central exige associação simultânea aos dois grupos.');
        });
        WP_CLI::add_command('smtt-intranet ad-status', function () {
            WP_CLI::log(wp_json_encode(array(
                'ldaps_configured' => SMTT_Intranet_AD::config_ready(),
                'last_sync' => get_option(SMTT_Intranet_AD::OPT_SYNC, array()),
                'last_error' => get_option('smtt_intranet_ad_last_error', ''),
                'cpd_sid' => get_option(SMTT_Intranet_AD::OPT_CPD, ''),
                'admin_sid' => get_option(SMTT_Intranet_AD::OPT_ADMINS, ''),
            ), JSON_PRETTY_PRINT));
        });
    }
}
