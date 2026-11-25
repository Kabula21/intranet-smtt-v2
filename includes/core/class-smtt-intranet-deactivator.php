<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Deactivator {

    public static function deactivate() {
        if (class_exists('SMTT_Intranet_Announcements')) {
            wp_clear_scheduled_hook(SMTT_Intranet_Announcements::CRON_HOOK);
            wp_clear_scheduled_hook(SMTT_Intranet_Announcements::CRON_NOW_HOOK);
            wp_clear_scheduled_hook(SMTT_Intranet_Announcements::CRON_IMAGES_HOOK);
        }

        if (class_exists('SMTT_Intranet_Access_Gate')) {
            wp_clear_scheduled_hook(SMTT_Intranet_Access_Gate::CRON_HOOK);
        }

        flush_rewrite_rules();
    }
}