<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Capabilities {

    public static function register() {
        $admin = get_role('administrator');

        if ($admin) {
            $caps = array(
                'smtt_access_intranet',
                'smtt_manage_intranet',
                'smtt_manage_announcements',
                'smtt_manage_documents',
                'smtt_manage_directory',
                'smtt_manage_calendar',
                'smtt_manage_access_center',
                'smtt_manage_diary',
                'smtt_manage_knowledge_base',
                'smtt_manage_mural',
                'smtt_manage_birthdays',
                'smtt_manage_tour',
                'smtt_manage_access_gate',
            );

            foreach ($caps as $cap) {
                if (!$admin->has_cap($cap)) {
                    $admin->add_cap($cap);
                }
            }
        }
    }
}