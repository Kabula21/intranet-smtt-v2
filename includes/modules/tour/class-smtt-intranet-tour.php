<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Tour {

    const OPTION_SETTINGS = 'smtt_intranet_tour_settings';
    const USER_META_SEEN  = 'smtt_intranet_tour_seen_version';
    const CAPABILITY      = 'smtt_manage_tour';

    public function register() {
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('option_page_capability_smtt_intranet_tour_settings_group', array($this, 'settings_capability'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'), 30);
        add_action('wp_ajax_smtt_intranet_mark_tour_viewed', array($this, 'mark_tour_viewed'));
        add_action('admin_post_smtt_intranet_force_tour', array($this, 'force_tour_for_all'));
    }

    public function settings_capability() {
        return self::CAPABILITY;
    }

    public function register_settings() {
        register_setting(
            'smtt_intranet_tour_settings_group',
            self::OPTION_SETTINGS,
            array($this, 'sanitize_settings')
        );
    }

    public static function get_default_settings() {
        return array(
            'enabled'       => '0',
            'version'       => 1,
            'delay'         => 1000,
            'welcome_title' => 'Oi, {nome}! Que bom ter você na Intranet SMTT V2 👋',
            'welcome_text'  => 'A nova Intranet foi organizada para deixar o seu dia mais simples, com acessos, informações e pessoas mais perto. Em poucos passos, vamos abrir os principais recursos e mostrar onde encontrar cada coisa. Fique à vontade: este espaço também é seu.',
            'image_id'      => 0,
            'steps'         => array(
                'systems_menu'     => '1',
                'documents_menu'   => '1',
                'departments_menu' => '1',
                'chat'             => '1',
                'theme'            => '1',
                'apps'             => '1',
                'user'             => '1',
                'search'           => '1',
                'access'           => '1',
                'access_detail'    => '1',
                'agenda'           => '1',
                'agenda_detail'    => '1',
                'mural'            => '1',
                'mural_detail'     => '1',
                'wiki'             => '1',
                'useful_links'     => '1',
                'news'             => '1',
                'birthdays'        => '1',
                'birthdays_detail' => '1',
                'ramais'           => '1',
            ),
        );
    }

    public static function get_step_definitions() {
        return array(
            'systems_menu' => array(
                'target'      => '#smttSystemsToggle',
                'admin_title' => 'Menu · Sistemas',
                'eyebrow'     => 'Acessos rápidos',
                'icon'        => 'systems',
                'title'       => 'Seus sistemas, a um clique',
                'text'        => 'No menu Sistemas ficam os acessos institucionais usados no dia a dia. Durante o Tour, ele abre de verdade para você visualizar as ferramentas disponíveis — sempre respeitando as permissões do seu perfil.',
            ),
            'documents_menu' => array(
                'target'      => '#smttDocumentsToggle',
                'admin_title' => 'Menu · Documentos',
                'eyebrow'     => 'Acessos rápidos',
                'icon'        => 'documents',
                'title'       => 'Documentos sem caça ao arquivo',
                'text'        => 'Modelos, arquivos e documentos internos ficam reunidos aqui. Você pode pesquisar pelo nome e abrir o que precisa sem ficar procurando em vários lugares.',
            ),
            'departments_menu' => array(
                'target'      => '#smttServicesToggle',
                'admin_title' => 'Menu · Departamentos',
                'eyebrow'     => 'Acessos rápidos',
                'icon'        => 'departments',
                'title'       => 'As pastas do seu setor ficam mais perto',
                'text'        => 'Em Departamentos ficam as pastas e acessos organizados por setor. A Intranet mostra o que está disponível para o seu perfil, deixando o caminho até os arquivos de trabalho mais direto e seguro.',
            ),
            'chat' => array(
                'target'      => '.smtt-topbar-chat-btn',
                'admin_title' => 'Chat Interno',
                'eyebrow'     => 'Comunicação',
                'icon'        => 'chat',
                'title'       => 'Converse com a equipe sem sair da SMTT',
                'text'        => 'O Chat SMTT é o nosso messenger interno. Por aqui você pode trocar mensagens, criar conversas em grupo e enviar recados aos colaboradores de forma rápida, mantendo a comunicação do trabalho mais próxima.',
            ),
            'theme' => array(
                'target'      => '#smttThemeToggle',
                'admin_title' => 'Tema claro e escuro',
                'eyebrow'     => 'Conforto visual',
                'icon'        => 'theme',
                'title'       => 'Use a Intranet do jeito que fica melhor para você',
                'text'        => 'Este botão alterna entre o tema claro e o escuro. O Tour faz uma demonstração rápida agora — e, fora do Tour, a sua escolha fica salva para os próximos acessos.',
            ),
            'apps' => array(
                'target'      => '#smttAppsToggle',
                'admin_title' => 'Menu de Aplicativos',
                'eyebrow'     => 'Ferramentas rápidas',
                'icon'        => 'apps',
                'title'       => 'Suas ferramentas do dia a dia, juntas aqui',
                'text'        => 'Este menu reúne atalhos rápidos definidos pelo CPD. Você pode abrir ferramentas de trabalho e aplicativos autorizados sem sair da Home da Intranet.',
            ),
            'user' => array(
                'target'      => '.smtt-modern-user',
                'admin_title' => 'Identificação do usuário',
                'eyebrow'     => 'Seu acesso',
                'icon'        => 'user',
                'title'       => 'A Intranet reconhece quem está por aqui',
                'text'        => 'No topo você confere a identificação do usuário conectado e encontra a saída segura da conta. É essa identificação que também ajuda a Intranet a respeitar os acessos destinados ao seu setor.',
            ),
            'search' => array(
                'target'      => '.smtt-banner-hero-center',
                'admin_title' => 'Busca Global',
                'eyebrow'     => 'Encontre mais rápido',
                'icon'        => 'search',
                'title'       => 'Uma busca para encontrar a Intranet inteira',
                'text'        => 'A Busca Global procura pessoas, ramais, sistemas, documentos, setores e outros conteúdos em um só lugar. Se não souber onde alguma coisa está, este é um ótimo ponto de partida.',
            ),
            'access' => array(
                'target'      => '#central-acessos-card',
                'admin_title' => 'Central de Acessos · visão geral',
                'eyebrow'     => 'Seu setor',
                'icon'        => 'access',
                'title'       => 'Primeiro, conheça a sua Central de Acessos',
                'text'        => 'Este card reúne todos os departamentos da SMTT em um só lugar. Os setores aparecem organizados para você identificar rapidamente onde estão os sistemas e as pastas de trabalho. O acesso ao conteúdo interno continua respeitando o seu setor e as permissões do seu perfil.',
            ),
            'access_detail' => array(
                'target'      => '#central-acessos-card',
                'admin_title' => 'Central de Acessos · abrir um setor',
                'eyebrow'     => 'Demonstração',
                'icon'        => 'access',
                'title'       => 'Agora veja o que existe dentro do seu setor',
                'text'        => 'Ao abrir um setor liberado para você, a Central mostra os sistemas e as pastas daquele departamento. Assim cada equipe encontra seus recursos no mesmo lugar, sem expor acessos que pertencem a outros setores.',
            ),
            'agenda' => array(
                'target'      => '#agenda',
                'admin_title' => 'Agenda SMTT · calendário',
                'eyebrow'     => 'Organização',
                'icon'        => 'agenda',
                'title'       => 'Veja rapidamente quando um espaço já está reservado',
                'text'        => 'A Agenda SMTT mostra as reservas de salas de reunião, auditório, sala de crise e outros espaços internos. Os dias que possuem marcação ficam sinalizados no calendário para você perceber rapidamente quando há alguma atividade agendada.',
            ),
            'agenda_detail' => array(
                'target'      => '#agenda',
                'admin_title' => 'Agenda SMTT · detalhes da reserva',
                'eyebrow'     => 'Demonstração',
                'icon'        => 'agenda',
                'title'       => 'Abra o dia e confira os detalhes da reserva',
                'text'        => 'Ao selecionar um dia com marcação, a Agenda abre os compromissos daquele dia com horário, local, responsável e demais informações cadastradas. Assim fica fácil confirmar se um espaço está disponível antes de organizar uma nova atividade.',
            ),
            'mural' => array(
                'target'      => '#mural',
                'admin_title' => 'Mural Interno · visão geral',
                'eyebrow'     => 'Informação interna',
                'icon'        => 'mural',
                'title'       => 'Os avisos importantes ficam em destaque no Mural',
                'text'        => 'O Mural Interno é a área de comunicação para informações relevantes a todos os colaboradores. Aqui você encontra avisos, orientações e comunicados que precisam ganhar visibilidade dentro da SMTT.',
            ),
            'mural_detail' => array(
                'target'      => '#mural',
                'admin_title' => 'Mural Interno · abrir aviso',
                'eyebrow'     => 'Demonstração',
                'icon'        => 'mural',
                'title'       => 'Clique no aviso para ler o conteúdo completo',
                'text'        => 'Os cards do Mural trazem uma prévia. Ao abrir um deles, você vê o comunicado completo, com texto, imagem e demais informações publicadas para os colaboradores.',
            ),
            'wiki' => array(
                'target'      => '#base-interna',
                'admin_title' => 'Wiki SMTT',
                'eyebrow'     => 'Conhecimento',
                'icon'        => 'wiki',
                'title'       => 'A nossa Wikipédia da SMTT',
                'text'        => 'Na Wiki SMTT você encontra leis, regimentos, manuais, procedimentos e outros conteúdos institucionais úteis para o trabalho. Use a busca ou os filtros para chegar rapidamente ao conhecimento que precisa.',
            ),
            'useful_links' => array(
                'target'      => '#links-uteis-card',
                'admin_title' => 'Links úteis',
                'eyebrow'     => 'Atalhos',
                'icon'        => 'links',
                'title'       => 'Atalhos para o que você usa com frequência',
                'text'        => 'Links úteis reúne acessos rápidos a portais e serviços importantes. A ideia é simples: menos caminho, menos procura e mais agilidade para chegar ao que você precisa.',
            ),
            'news' => array(
                'target'      => '#comunicados',
                'admin_title' => 'Notícias da SMTT',
                'eyebrow'     => 'Fique por dentro',
                'icon'        => 'news',
                'title'       => 'As notícias da SMTT também chegam até você',
                'text'        => 'As publicações produzidas pela Assessoria de Comunicação aparecem aqui para manter os colaboradores informados sobre ações, projetos e acontecimentos da SMTT.',
            ),
            'birthdays' => array(
                'target'      => '#aniversariantes',
                'admin_title' => 'Aniversariantes · card',
                'eyebrow'     => 'Pessoas',
                'icon'        => 'birthday',
                'title'       => 'Um cantinho para lembrar de quem está apagando velinhas 🎉',
                'text'        => 'O card de Aniversariantes ajuda a acompanhar quem comemora no mês e quem está mais perto de celebrar. É uma forma simples de deixar o dia a dia um pouco mais próximo entre as equipes.',
            ),
            'birthdays_detail' => array(
                'target'      => '#aniversariantes',
                'admin_title' => 'Aniversariantes · lista completa',
                'eyebrow'     => 'Demonstração',
                'icon'        => 'birthday',
                'title'       => 'Quer conferir todo mundo? Abra a lista completa',
                'text'        => 'Na lista completa você encontra os aniversários de hoje, os próximos e os mais recentes. Assim fica fácil conferir quem está comemorando e não deixar passar aquele parabéns.',
            ),
            'ramais' => array(
                'target'      => '[data-smtt-ramais-open]',
                'admin_title' => 'Ramais da SMTT',
                'eyebrow'     => 'Diretório interno',
                'icon'        => 'phone',
                'title'       => 'Encontrar um ramal ficou bem mais fácil',
                'text'        => 'No diretório de Ramais da SMTT você encontra os números internos dos setores de forma rápida. O painel abre pela lateral e permite pesquisar para chegar ao contato certo sem perder tempo.',
            ),
        );
    }

    public static function get_settings() {
        $defaults = self::get_default_settings();
        $saved    = get_option(self::OPTION_SETTINGS, array());

        if (!is_array($saved)) {
            $saved = array();
        }

        $settings          = wp_parse_args($saved, $defaults);
        $settings['steps'] = wp_parse_args(
            isset($saved['steps']) && is_array($saved['steps']) ? $saved['steps'] : array(),
            $defaults['steps']
        );

        return $settings;
    }

    public function sanitize_settings($input) {
        $defaults = self::get_default_settings();
        $input    = is_array($input) ? $input : array();
        $delays   = array(0, 500, 1000, 1500, 2000, 3000);

        $delay = isset($input['delay']) ? absint($input['delay']) : $defaults['delay'];
        if (!in_array($delay, $delays, true)) {
            $delay = $defaults['delay'];
        }

        $version = isset($input['version']) ? absint($input['version']) : 1;
        $version = max(1, min(9999, $version));

        $steps = array();
        foreach (self::get_step_definitions() as $key => $definition) {
            $steps[$key] = !empty($input['steps'][$key]) ? '1' : '0';
        }

        return array(
            'enabled'       => !empty($input['enabled']) ? '1' : '0',
            'version'       => $version,
            'delay'         => $delay,
            'welcome_title' => isset($input['welcome_title']) ? sanitize_text_field($input['welcome_title']) : $defaults['welcome_title'],
            'welcome_text'  => isset($input['welcome_text']) ? sanitize_textarea_field($input['welcome_text']) : $defaults['welcome_text'],
            'image_id'      => isset($input['image_id']) ? absint($input['image_id']) : 0,
            'steps'         => $steps,
        );
    }

    public function enqueue_admin_assets($hook) {
        if (strpos((string) $hook, 'smtt-intranet-tour') === false) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script(
            'smtt-intranet-tour-admin',
            SMTT_INTRANET_URL . 'assets/js/tour-admin.js',
            array('jquery'),
            file_exists(SMTT_INTRANET_PATH . 'assets/js/tour-admin.js') ? (string) filemtime(SMTT_INTRANET_PATH . 'assets/js/tour-admin.js') : SMTT_INTRANET_VERSION,
            true
        );
    }

    public function enqueue_frontend_assets() {
        if (!$this->is_intranet_request() || !is_user_logged_in()) {
            return;
        }

        $settings = self::get_settings();
        $preview  = $this->is_preview_request();

        if ($settings['enabled'] !== '1' && !$preview) {
            return;
        }

        $seen_version = (int) get_user_meta(get_current_user_id(), self::USER_META_SEEN, true);
        $tour_version = max(1, (int) $settings['version']);
        $has_seen     = !$preview && $seen_version >= $tour_version;

        /*
         * Mesmo depois de o colaborador concluir ou pular a apresentação,
         * mantemos os assets do Tour carregados para exibir o pequeno botão
         * "Rever Tour". A abertura automática, porém, só acontece uma vez por
         * usuário e por versão.
         */
        wp_enqueue_style(
            'smtt-intranet-tour',
            SMTT_INTRANET_URL . 'assets/public/css/tour.css',
            array('smtt-intranet-public'),
            file_exists(SMTT_INTRANET_PATH . 'assets/public/css/tour.css') ? (string) filemtime(SMTT_INTRANET_PATH . 'assets/public/css/tour.css') : SMTT_INTRANET_VERSION
        );

        wp_enqueue_script(
            'smtt-intranet-tour',
            SMTT_INTRANET_URL . 'assets/public/js/tour.js',
            array(),
            file_exists(SMTT_INTRANET_PATH . 'assets/public/js/tour.js') ? (string) filemtime(SMTT_INTRANET_PATH . 'assets/public/js/tour.js') : SMTT_INTRANET_VERSION,
            true
        );

        wp_localize_script(
            'smtt-intranet-tour',
            'smttIntranetTour',
            $this->get_frontend_payload($settings, $preview, $has_seen)
        );
    }

    private function get_frontend_payload($settings, $preview, $has_seen = false) {
        $image_url         = '';
        $image_orientation = 'none';
        $image_alt         = '';
        $image_id          = absint($settings['image_id']);

        if ($image_id) {
            $image_src = wp_get_attachment_image_src($image_id, 'large');
            if ($image_src) {
                $image_url = $image_src[0];
                $width     = (int) $image_src[1];
                $height    = (int) $image_src[2];

                if ($width > $height * 1.15) {
                    $image_orientation = 'horizontal';
                } elseif ($height > $width * 1.15) {
                    $image_orientation = 'vertical';
                } else {
                    $image_orientation = 'square';
                }
            }

            $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
        }

        $steps = array();
        foreach (self::get_step_definitions() as $key => $definition) {
            if (empty($settings['steps'][$key]) || $settings['steps'][$key] !== '1') {
                continue;
            }

            $steps[] = array(
                'key'    => $key,
                'target' => $definition['target'],
                'title'   => $definition['title'],
                'text'    => $definition['text'],
                'eyebrow' => isset($definition['eyebrow']) ? $definition['eyebrow'] : 'Tour da Intranet',
                'icon'    => isset($definition['icon']) ? $definition['icon'] : 'spark',
            );
        }

        $current_user = wp_get_current_user();
        $first_name   = trim((string) $current_user->first_name);
        if ($first_name === '') {
            $name_parts = preg_split('/\s+/', trim((string) $current_user->display_name));
            $first_name = !empty($name_parts[0]) ? $name_parts[0] : 'você';
        }

        $welcome_title = str_replace('{nome}', $first_name, (string) $settings['welcome_title']);

        $current_user_id = get_current_user_id();

        return array(
            'show'             => true,
            'preview'          => (bool) $preview,
            'autoStart'        => (bool) ($preview || !$has_seen),
            'replayAvailable'  => (bool) ($settings['enabled'] === '1'),
            'version'          => (int) $settings['version'],
            'delay'            => (int) $settings['delay'],
            'welcomeTitle'     => $welcome_title,
            'welcomeText'      => $settings['welcome_text'],
            'imageUrl'         => $image_url,
            'imageAlt'         => $image_alt ? $image_alt : 'Apresentação da Intranet SMTT V2',
            'imageOrientation' => $image_orientation,
            'steps'            => $steps,
            'finishTitle'      => 'Tudo pronto, ' . $first_name . '. A Intranet V2 é sua também. 💙',
            'finishText'       => 'Agora pode explorar com calma. Sempre que precisar de um acesso, uma informação ou de alguém da equipe, a ideia é que a Intranet seja o caminho mais simples para chegar lá.',
            'ajaxUrl'          => admin_url('admin-ajax.php'),
            'nonce'            => wp_create_nonce('smtt_intranet_tour'),
            'storageKey'       => 'smtt_intranet_tour_seen_u' . $current_user_id . '_v' . (int) $settings['version'],
        );
    }

    /**
     * Mantido por compatibilidade com integrações antigas.
     * Desde a v2.0.24 o atalho de replay é renderizado diretamente na navbar.
     */
    public function render_replay_button() {
        return;
    }

    public function mark_tour_viewed() {
        check_ajax_referer('smtt_intranet_tour', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Usuário não autenticado.', 'smtt-intranet')), 401);
        }

        $settings        = self::get_settings();
        $current_version = max(1, (int) $settings['version']);
        $requested       = isset($_POST['version']) ? absint(wp_unslash($_POST['version'])) : $current_version;
        $version         = max(1, min($current_version, $requested));
        $user_id         = get_current_user_id();
        $seen_version    = (int) get_user_meta($user_id, self::USER_META_SEEN, true);

        if ($seen_version < $version) {
            update_user_meta($user_id, self::USER_META_SEEN, $version);
        }

        wp_send_json_success(array('version' => $version));
    }

    public function force_tour_for_all() {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('Você não tem permissão para disparar o Tour da Intranet.', 'smtt-intranet'));
        }

        check_admin_referer('smtt_intranet_force_tour');

        $settings = self::get_settings();
        $version  = max(1, (int) $settings['version']);

        if ($version >= 9999) {
            $redirect = add_query_arg(
                array(
                    'page'                  => 'smtt-intranet-tour',
                    'smtt_tour_force_error' => 'limit',
                ),
                admin_url('admin.php')
            );
            wp_safe_redirect($redirect);
            exit;
        }

        $settings['version'] = $version + 1;
        $settings['enabled'] = '1';
        update_option(self::OPTION_SETTINGS, $settings, false);

        $redirect = add_query_arg(
            array(
                'page'                  => 'smtt-intranet-tour',
                'smtt_tour_forced'      => '1',
                'smtt_tour_new_version' => $version + 1,
            ),
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect);
        exit;
    }

    public function render_admin_page() {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('Você não tem permissão para gerenciar o Tour da Intranet.', 'smtt-intranet'));
        }

        $settings    = self::get_settings();
        $definitions = self::get_step_definitions();
        $image_url   = '';
        $orientation = '';

        if (!empty($settings['image_id'])) {
            $image_src = wp_get_attachment_image_src((int) $settings['image_id'], 'medium_large');
            if ($image_src) {
                $image_url = $image_src[0];
                $orientation = $image_src[1] > $image_src[2] * 1.15
                    ? 'Horizontal'
                    : ($image_src[2] > $image_src[1] * 1.15 ? 'Vertical' : 'Quadrada');
            }
        }

        $preview_url = self::get_preview_url();
        $force_url   = wp_nonce_url(
            admin_url('admin-post.php?action=smtt_intranet_force_tour'),
            'smtt_intranet_force_tour'
        );
        ?>
        <div class="wrap smtt-intranet-admin smtt-tour-admin">
            <a class="button smtt-dashboard-back-button" href="<?php echo esc_url(admin_url('admin.php?page=smtt-intranet')); ?>">← <?php echo esc_html__('Voltar ao Gerenciador', 'smtt-intranet'); ?></a>

            <?php if (isset($_GET['smtt_tour_forced']) && $_GET['smtt_tour_forced'] === '1') : ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?php echo esc_html__('Nova exibição liberada para todos.', 'smtt-intranet'); ?></strong> <?php echo esc_html(sprintf(__('O Tour agora está na rodada %d. Cada colaborador verá essa nova rodada uma única vez.', 'smtt-intranet'), isset($_GET['smtt_tour_new_version']) ? absint($_GET['smtt_tour_new_version']) : (int) $settings['version'])); ?></p>
                </div>
            <?php elseif (isset($_GET['smtt_tour_force_error']) && $_GET['smtt_tour_force_error'] === 'limit') : ?>
                <div class="notice notice-error is-dismissible"><p><?php echo esc_html__('O limite interno de versões do Tour foi atingido. Entre em contato com o suporte antes de criar uma nova rodada.', 'smtt-intranet'); ?></p></div>
            <?php endif; ?>

            <div class="smtt-admin-header smtt-admin-header-v2">
                <div>
                    <h1><?php echo esc_html__('Tour da Intranet V2', 'smtt-intranet'); ?></h1>
                    <p class="smtt-admin-subtitle"><?php echo esc_html__('Uma apresentação interativa e acolhedora: o Tour abre menus e recursos reais para mostrar a Intranet V2 funcionando.', 'smtt-intranet'); ?></p>
                </div>
                <a class="button button-secondary" href="<?php echo esc_url($preview_url); ?>" target="_blank" rel="noopener noreferrer">
                    <?php echo esc_html__('Pré-visualizar Tour', 'smtt-intranet'); ?> ↗
                </a>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields('smtt_intranet_tour_settings_group'); ?>

                <div class="smtt-tour-admin-grid">
                    <div class="smtt-tour-admin-main">
                        <section class="smtt-admin-panel smtt-tour-panel">
                            <div class="smtt-admin-panel-header">
                                <h2><?php echo esc_html__('Boas-vindas', 'smtt-intranet'); ?></h2>
                                <p><?php echo esc_html__('Esta é a primeira tela do Tour. Ela acolhe o colaborador e prepara a experiência antes de começar a demonstração interativa.', 'smtt-intranet'); ?></p>
                            </div>

                            <table class="form-table smtt-tour-form-table">
                                <tr>
                                    <th scope="row"><label for="smtt_tour_welcome_title"><?php echo esc_html__('Título', 'smtt-intranet'); ?></label></th>
                                    <td><input type="text" class="large-text" id="smtt_tour_welcome_title" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[welcome_title]" value="<?php echo esc_attr($settings['welcome_title']); ?>"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="smtt_tour_welcome_text"><?php echo esc_html__('Mensagem', 'smtt-intranet'); ?></label></th>
                                    <td>
                                        <textarea class="large-text" rows="5" id="smtt_tour_welcome_text" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[welcome_text]"><?php echo esc_textarea($settings['welcome_text']); ?></textarea>
                                        <p class="description"><?php echo esc_html__('Você pode usar {nome} no título para chamar cada colaborador pelo primeiro nome. Prefira uma mensagem curta: acolher primeiro, explicar depois.', 'smtt-intranet'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo esc_html__('Imagem de boas-vindas', 'smtt-intranet'); ?></th>
                                    <td>
                                        <input type="hidden" id="smtt_tour_image_id" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[image_id]" value="<?php echo esc_attr((int) $settings['image_id']); ?>">
                                        <div class="smtt-tour-media-actions">
                                            <button type="button" class="button" id="smttTourSelectImage"><?php echo esc_html__('Selecionar imagem', 'smtt-intranet'); ?></button>
                                            <button type="button" class="button" id="smttTourRemoveImage" <?php disabled(empty($image_url)); ?>><?php echo esc_html__('Remover imagem', 'smtt-intranet'); ?></button>
                                        </div>
                                        <div class="smtt-tour-image-preview <?php echo $image_url ? 'has-image' : ''; ?>" id="smttTourImagePreview">
                                            <?php if ($image_url) : ?>
                                                <img src="<?php echo esc_url($image_url); ?>" alt="">
                                            <?php endif; ?>
                                            <span class="smtt-tour-image-empty" <?php echo $image_url ? 'hidden' : ''; ?>><?php echo esc_html__('Nenhuma imagem selecionada. O Tour também funciona perfeitamente só com texto.', 'smtt-intranet'); ?></span>
                                        </div>
                                        <p class="description" id="smttTourImageOrientation"><?php echo $orientation ? esc_html('Orientação detectada: ' . $orientation . '. O layout se adapta automaticamente no desktop e no celular.') : esc_html__('Aceita imagem horizontal, vertical ou quadrada. O layout se adapta automaticamente.', 'smtt-intranet'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </section>

                        <section class="smtt-admin-panel smtt-tour-panel">
                            <div class="smtt-admin-panel-header">
                                <h2><?php echo esc_html__('Etapas do Tour', 'smtt-intranet'); ?></h2>
                                <p><?php echo esc_html__('O Tour percorre os principais recursos da Intranet e, quando o componente permite, abre menus, modais e painéis reais para demonstrar a funcionalidade.', 'smtt-intranet'); ?></p>
                            </div>

                            <div class="smtt-tour-step-list">
                                <?php $step_number = 0; ?>
                                <?php foreach ($definitions as $key => $definition) : ?>
                                    <?php $step_number++; ?>
                                    <label class="smtt-tour-step-admin-card">
                                        <input type="checkbox" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[steps][<?php echo esc_attr($key); ?>]" value="1" <?php checked(!empty($settings['steps'][$key]) && $settings['steps'][$key] === '1'); ?>>
                                        <span class="smtt-tour-step-number"><?php echo esc_html($step_number); ?></span>
                                        <span class="smtt-tour-step-copy">
                                            <strong><?php echo esc_html($definition['admin_title']); ?></strong>
                                            <b><?php echo esc_html($definition['title']); ?></b>
                                            <small><?php echo esc_html($definition['text']); ?></small>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    </div>

                    <aside class="smtt-tour-admin-side">
                        <section class="smtt-admin-panel smtt-tour-panel smtt-tour-status-panel">
                            <div class="smtt-admin-panel-header">
                                <h2><?php echo esc_html__('Publicação', 'smtt-intranet'); ?></h2>
                            </div>

                            <label class="smtt-tour-switch-row">
                                <span>
                                    <strong><?php echo esc_html__('Tour ativo', 'smtt-intranet'); ?></strong>
                                    <small><?php echo esc_html__('Quando ativo, aparece automaticamente para quem ainda não viu esta versão.', 'smtt-intranet'); ?></small>
                                </span>
                                <input type="checkbox" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[enabled]" value="1" <?php checked($settings['enabled'], '1'); ?>>
                            </label>

                            <div class="smtt-tour-side-field">
                                <label for="smtt_tour_version"><?php echo esc_html__('Rodada atual do Tour', 'smtt-intranet'); ?></label>
                                <input type="number" min="1" max="9999" id="smtt_tour_version" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[version]" value="<?php echo esc_attr((int) $settings['version']); ?>">
                                <p><?php echo esc_html__('Esse número identifica a apresentação atual. Normalmente você não precisa alterá-lo manualmente.', 'smtt-intranet'); ?></p>
                            </div>

                            <div class="smtt-tour-force-box">
                                <strong><?php echo esc_html__('Teve novidade importante?', 'smtt-intranet'); ?></strong>
                                <p><?php echo esc_html__('Use este botão para liberar uma nova rodada. Quem já viu ou pulou o Tour volta a recebê-lo uma única vez no próximo acesso.', 'smtt-intranet'); ?></p>
                                <a class="button button-primary smtt-tour-force-button" href="<?php echo esc_url($force_url); ?>" onclick="return window.confirm('Liberar o Tour novamente para todos os colaboradores?');">
                                    <?php echo esc_html__('Disparar Tour novamente para todos', 'smtt-intranet'); ?>
                                </a>
                            </div>

                            <div class="smtt-tour-side-field">
                                <label for="smtt_tour_delay"><?php echo esc_html__('Atraso para abrir', 'smtt-intranet'); ?></label>
                                <select id="smtt_tour_delay" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[delay]">
                                    <option value="0" <?php selected((int) $settings['delay'], 0); ?>><?php echo esc_html__('Imediatamente', 'smtt-intranet'); ?></option>
                                    <option value="500" <?php selected((int) $settings['delay'], 500); ?>><?php echo esc_html__('0,5 segundo', 'smtt-intranet'); ?></option>
                                    <option value="1000" <?php selected((int) $settings['delay'], 1000); ?>><?php echo esc_html__('1 segundo', 'smtt-intranet'); ?></option>
                                    <option value="1500" <?php selected((int) $settings['delay'], 1500); ?>><?php echo esc_html__('1,5 segundo', 'smtt-intranet'); ?></option>
                                    <option value="2000" <?php selected((int) $settings['delay'], 2000); ?>><?php echo esc_html__('2 segundos', 'smtt-intranet'); ?></option>
                                    <option value="3000" <?php selected((int) $settings['delay'], 3000); ?>><?php echo esc_html__('3 segundos', 'smtt-intranet'); ?></option>
                                </select>
                            </div>

                            <div class="smtt-tour-note">
                                <strong><?php echo esc_html__('Como funciona para o colaborador?', 'smtt-intranet'); ?></strong>
                                <p><?php echo esc_html__('Ao começar, concluir, fechar ou pular o Tour, a rodada atual fica registrada no próprio usuário. Atualizar a página, fechar o navegador, sair da conta ou entrar em outro computador não faz a mesma rodada aparecer novamente. O colaborador ainda pode usar o botão discreto “Rever Tour” quando quiser.', 'smtt-intranet'); ?></p>
                            </div>
                        </section>
                    </aside>
                </div>

                <?php submit_button(__('Salvar Tour', 'smtt-intranet')); ?>
            </form>
        </div>
        <?php
    }

    public static function get_preview_url() {
        $page_id = (int) get_option('page_on_front');
        $url     = $page_id ? get_permalink($page_id) : '';

        if (!$url) {
            $home_page = get_page_by_path('home');
            if ($home_page instanceof WP_Post) {
                $url = get_permalink($home_page->ID);
            }
        }

        if (!$url) {
            $url = home_url('/');
        }

        return add_query_arg('smtt_tour_preview', '1', $url);
    }

    private function is_preview_request() {
        return isset($_GET['smtt_tour_preview'])
            && $_GET['smtt_tour_preview'] === '1'
            && current_user_can(self::CAPABILITY);
    }

    private function is_intranet_request() {
        if (is_admin()) {
            return false;
        }

        global $post;

        if ($post instanceof WP_Post && has_shortcode((string) $post->post_content, 'smtt_intranet_home')) {
            return true;
        }

        $queried_id = get_queried_object_id();
        if ($queried_id) {
            $queried_post = get_post($queried_id);
            if ($queried_post instanceof WP_Post && has_shortcode((string) $queried_post->post_content, 'smtt_intranet_home')) {
                return true;
            }

            $elementor_data = get_post_meta($queried_id, '_elementor_data', true);
            if (is_string($elementor_data) && strpos($elementor_data, 'smtt_intranet_home') !== false) {
                return true;
            }
        }

        return is_page('home');
    }
}
