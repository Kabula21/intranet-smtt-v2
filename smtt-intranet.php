<?php
/**
 * Plugin Name: SMTT Intranet v.2.0
 * Description: Intranet institucional da SMTT de Aracaju.
 * Version: 2.0.35.23
 * Author: SMTT Aracaju
 * Text Domain: smtt-intranet
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SMTT_INTRANET_VERSION', '2.0.35.23');
define('SMTT_INTRANET_FILE', __FILE__);
define('SMTT_INTRANET_PATH', plugin_dir_path(__FILE__));
define('SMTT_INTRANET_URL', plugin_dir_url(__FILE__));

require_once SMTT_INTRANET_PATH . 'includes/core/class-smtt-intranet-capabilities.php';
require_once SMTT_INTRANET_PATH . 'includes/core/class-smtt-intranet-activator.php';
require_once SMTT_INTRANET_PATH . 'includes/core/class-smtt-intranet-deactivator.php';
require_once SMTT_INTRANET_PATH . 'includes/core/class-smtt-intranet-plugin.php';

require_once SMTT_INTRANET_PATH . 'includes/enterprise/class-smtt-intranet-ad.php';
require_once SMTT_INTRANET_PATH . 'includes/enterprise/class-smtt-intranet-corporate-identity.php';
require_once SMTT_INTRANET_PATH . 'includes/enterprise/class-smtt-intranet-enterprise.php';
require_once SMTT_INTRANET_PATH . 'includes/enterprise/class-smtt-intranet-policy.php';
require_once SMTT_INTRANET_PATH . 'includes/enterprise/class-smtt-intranet-manager.php';
require_once SMTT_INTRANET_PATH . 'includes/enterprise/class-smtt-intranet-content-files.php';
require_once SMTT_INTRANET_PATH . 'includes/enterprise/class-smtt-intranet-icon-library.php';
require_once SMTT_INTRANET_PATH . 'includes/enterprise/class-smtt-intranet-fortigate-simulator.php';
require_once SMTT_INTRANET_PATH . 'includes/enterprise/class-smtt-intranet-custom-modules.php';
require_once SMTT_INTRANET_PATH . 'includes/enterprise/class-smtt-intranet-home-layout.php';
require_once SMTT_INTRANET_PATH . 'includes/enterprise/class-smtt-intranet-app-launcher.php';

require_once SMTT_INTRANET_PATH . 'includes/admin/class-smtt-intranet-admin-menu.php';

require_once SMTT_INTRANET_PATH . 'includes/modules/access-center/class-smtt-intranet-sector-folders.php';
require_once SMTT_INTRANET_PATH . 'includes/modules/access-center/class-smtt-intranet-internal-systems.php';
require_once SMTT_INTRANET_PATH . 'includes/modules/access-center/class-smtt-intranet-sectors-taxonomy.php';
require_once SMTT_INTRANET_PATH . 'includes/modules/access-center/class-smtt-intranet-user-sector.php';
require_once SMTT_INTRANET_PATH . 'includes/modules/access-center/class-smtt-intranet-access-permissions.php';
require_once SMTT_INTRANET_PATH . 'includes/modules/access-center/class-smtt-intranet-useful-links.php';

require_once SMTT_INTRANET_PATH . 'includes/modules/announcements/class-smtt-intranet-announcements.php';
require_once SMTT_INTRANET_PATH . 'includes/modules/documents/class-smtt-intranet-documents.php';
require_once SMTT_INTRANET_PATH . 'includes/modules/documents/class-smtt-intranet-document-categories.php';
require_once SMTT_INTRANET_PATH . 'includes/modules/agenda/class-smtt-intranet-agenda.php';
require_once SMTT_INTRANET_PATH . 'includes/modules/agenda/class-smtt-intranet-agenda-spaces.php';
require_once SMTT_INTRANET_PATH . 'includes/modules/agenda/class-smtt-intranet-agenda-categories.php';
require_once SMTT_INTRANET_PATH . 'includes/modules/knowledge-base/class-smtt-intranet-knowledge-base.php';
require_once SMTT_INTRANET_PATH . 'includes/modules/knowledge-base/class-smtt-intranet-knowledge-base-categories.php';
require_once SMTT_INTRANET_PATH . 'includes/modules/mural/class-smtt-intranet-mural.php';
require_once SMTT_INTRANET_PATH . 'includes/modules/mural/class-smtt-intranet-mural-categories.php';
require_once SMTT_INTRANET_PATH . 'includes/modules/birthdays/class-smtt-intranet-birthdays.php';
require_once SMTT_INTRANET_PATH . 'includes/modules/tour/class-smtt-intranet-tour.php';
require_once SMTT_INTRANET_PATH . 'includes/modules/access-gate/class-smtt-intranet-access-gate-repository.php';
require_once SMTT_INTRANET_PATH . 'includes/modules/access-gate/class-smtt-intranet-access-gate-identity.php';
require_once SMTT_INTRANET_PATH . 'includes/modules/access-gate/class-smtt-intranet-access-gate-api.php';
require_once SMTT_INTRANET_PATH . 'includes/modules/access-gate/class-smtt-intranet-access-gate.php';
require_once SMTT_INTRANET_PATH . 'includes/public/class-smtt-intranet-public.php';
require_once SMTT_INTRANET_PATH . 'includes/public/class-smtt-intranet-home-routing.php';
require_once SMTT_INTRANET_PATH . 'includes/modules/ramais/class-smtt-intranet-ramais-repository.php';
require_once SMTT_INTRANET_PATH . 'includes/modules/ramais/class-smtt-intranet-ramais-importer.php';
require_once SMTT_INTRANET_PATH . 'includes/modules/ramais/class-smtt-intranet-ramais-admin.php';
require_once SMTT_INTRANET_PATH . 'includes/modules/ramais/class-smtt-intranet-ramais-public.php';
require_once SMTT_INTRANET_PATH . 'includes/public/class-smtt-chat-header.php';
require_once SMTT_INTRANET_PATH . 'includes/modules/chat/class-smtt-intranet-native-chat.php';
require_once SMTT_INTRANET_PATH . 'includes/public/class-smtt-intranet-chat-integration.php';


register_activation_hook(__FILE__, array('SMTT_Intranet_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('SMTT_Intranet_Deactivator', 'deactivate'));

function smtt_intranet_run() {
    $plugin = new SMTT_Intranet_Plugin();
    $plugin->run();
    SMTT_Intranet_Home_Routing::register();
    $enterprise = new SMTT_Intranet_Enterprise();
    $enterprise->register();
    SMTT_Intranet_FortiGate_Simulator::register();
    SMTT_Intranet_Custom_Modules::register();
    SMTT_Intranet_Home_Layout::register();
    SMTT_Intranet_App_Launcher::register();
    SMTT_Intranet_Content_Files::register();
    $smtt_ramais_public = new SMTT_Intranet_Ramais_Public();
    $smtt_ramais_public->register_hooks();
    $chat_header = new SMTT_Intranet_Chat_Header();
    $chat_header->register();
    SMTT_Intranet_Native_Chat::register();
    SMTT_Intranet_Chat_Integration::register();
}

smtt_intranet_run();