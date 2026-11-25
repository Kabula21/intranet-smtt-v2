<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Plugin {

    public function run() {
        $sector_folders            = new SMTT_Intranet_Sector_Folders();
        $internal_systems          = new SMTT_Intranet_Internal_Systems();
        $sectors_taxonomy          = new SMTT_Intranet_Sectors_Taxonomy();
        $user_sector               = new SMTT_Intranet_User_Sector();
        $useful_links              = new SMTT_Intranet_Useful_Links();
        $documents                 = new SMTT_Intranet_Documents();
        $document_categories       = new SMTT_Intranet_Document_Categories();
        $agenda                    = new SMTT_Intranet_Agenda();
        $agenda_spaces             = new SMTT_Intranet_Agenda_Spaces();
        $agenda_categories         = new SMTT_Intranet_Agenda_Categories();
        $knowledge_base            = new SMTT_Intranet_Knowledge_Base();
        $knowledge_base_categories = new SMTT_Intranet_Knowledge_Base_Categories();
        $mural                     = new SMTT_Intranet_Mural();
        $mural_categories          = new SMTT_Intranet_Mural_Categories();
        $announcements = new SMTT_Intranet_Announcements();
        $birthdays = new SMTT_Intranet_Birthdays();
        $tour = new SMTT_Intranet_Tour();
        $access_gate = new SMTT_Intranet_Access_Gate();
        $public = new SMTT_Intranet_Public();


        $sector_folders->register();
        $internal_systems->register();
        $sectors_taxonomy->register();
        $user_sector->register();
        $useful_links->register();
        $documents->register();
        $document_categories->register();
        $agenda->register();
        $agenda_spaces->register();
        $agenda_categories->register();
        $knowledge_base->register();
        $knowledge_base_categories->register();
        $mural->register();
        $mural_categories->register();
        $announcements->register();
        $birthdays->register();
        $tour->register();
        $access_gate->register();
        $public->register();

        add_action('admin_init', array('SMTT_Intranet_Capabilities', 'register'));
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_menu', array($this, 'cleanup_admin_menu'), 999);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function register_admin_menu() {
        $menu = new SMTT_Intranet_Admin_Menu();
        $menu->register();
    }

    public function cleanup_admin_menu() {
        /*
         * O menu lateral do WordPress deve exibir apenas o item principal
         * "SMTT Intranet". Todos os gerenciadores continuam acessíveis pelos
         * cards do dashboard e por suas URLs administrativas; apenas os
         * submenus visuais são removidos para evitar uma lista redundante.
         */
        $submenu_slugs = array(
            'smtt-intranet',
            'smtt-intranet-access-center',
            'smtt-intranet-announcements',
            'smtt-intranet-documents',
            'smtt-intranet-directory',
            'smtt-intranet-calendar',
            'smtt-intranet-mural',
            'smtt-intranet-knowledge-base',
            'smtt-intranet-birthdays',
            'smtt-intranet-tour',
            'edit.php?post_type=smtt_sector_folder',
            'edit.php?post_type=smtt_internal_system',
            'edit.php?post_type=smtt_useful_link',
            'edit-tags.php?taxonomy=smtt_sector&post_type=smtt_sector_folder',
            'edit.php?post_type=smtt_document',
            'edit.php?post_type=smtt_agenda_item',
            'edit-tags.php?taxonomy=smtt_agenda_space&post_type=smtt_agenda_item',
            'edit-tags.php?taxonomy=smtt_agenda_category&post_type=smtt_agenda_item',
            'edit.php?post_type=smtt_kb_article',
            'edit-tags.php?taxonomy=smtt_kb_category&post_type=smtt_kb_article',
            'edit.php?post_type=smtt_mural_item',
            'edit-tags.php?taxonomy=smtt_mural_category&post_type=smtt_mural_item',
            'edit.php?post_type=smtt_birthday_person',
        );

        foreach ($submenu_slugs as $submenu_slug) {
            remove_submenu_page('smtt-intranet', $submenu_slug);
        }
    }

    public function enqueue_admin_assets($hook) {
        if (
            strpos((string) $hook, 'smtt-intranet') === false &&
            strpos((string) $hook, 'smtt_sector_folder') === false &&
            strpos((string) $hook, 'smtt_internal_system') === false &&
            strpos((string) $hook, 'smtt_useful_link') === false &&
            strpos((string) $hook, 'smtt_document') === false &&
            strpos((string) $hook, 'smtt_agenda_item') === false &&
            strpos((string) $hook, 'smtt_kb_article') === false &&
            strpos((string) $hook, 'smtt_mural_item') === false &&
            $hook !== 'profile.php' &&
            $hook !== 'user-edit.php'
        ) {
            return;
        }

        wp_enqueue_style(
            'smtt-intranet-admin',
            SMTT_INTRANET_URL . 'assets/css/admin.css',
            array(),
            SMTT_INTRANET_VERSION
        );
    }
}