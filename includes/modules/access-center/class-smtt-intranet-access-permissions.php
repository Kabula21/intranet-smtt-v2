<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Access_Permissions {

    const USER_SECTOR_META = 'smtt_user_sector';

    public static function get_user_sector_id($user_id = 0) {
        $user_id = $user_id ? (int) $user_id : get_current_user_id();

        if (!$user_id) {
            return 0;
        }

        return (int) get_user_meta($user_id, self::USER_SECTOR_META, true);
    }

    public static function user_is_manager($user_id = 0) {
        $user_id = $user_id ? (int) $user_id : get_current_user_id();

        if (!$user_id) {
            return false;
        }

        return user_can($user_id, 'manage_options') || user_can($user_id, 'smtt_manage_access_center');
    }

    public static function can_access_sector($sector_id, $user_id = 0) {
        $sector_id = (int) $sector_id;

        if (!$sector_id) {
            return false;
        }

        if (self::user_is_manager($user_id)) {
            return true;
        }

        return self::get_user_sector_id($user_id) === $sector_id;
    }

    public static function can_access_post($post_id, $user_id = 0) {
        $post_id = (int) $post_id;

        if (!$post_id) {
            return false;
        }

        if (self::user_is_manager($user_id)) {
            return true;
        }

        $user_sector_id = self::get_user_sector_id($user_id);

        if (!$user_sector_id) {
            return false;
        }

        $sector_ids = wp_get_object_terms($post_id, 'smtt_sector', array('fields' => 'ids'));

        if (empty($sector_ids) || is_wp_error($sector_ids)) {
            return false;
        }

        return in_array($user_sector_id, array_map('intval', $sector_ids), true);
    }

    public static function get_user_sector_name($user_id = 0) {
        $sector_id = self::get_user_sector_id($user_id);

        if (!$sector_id) {
            return '';
        }

        $term = get_term($sector_id, 'smtt_sector');

        if (!$term || is_wp_error($term)) {
            return '';
        }

        return $term->name;
    }

    public static function denied_message($target_sector_name = '') {
        $user_sector_name = self::get_user_sector_name();

        if (!$user_sector_name) {
            return __('Seu usuário ainda não possui um setor configurado. Procure o administrador da intranet.', 'smtt-intranet');
        }

        if ($target_sector_name) {
            return sprintf(
                __('Acesso restrito ao setor %1$s. Seu usuário está vinculado ao setor %2$s.', 'smtt-intranet'),
                $target_sector_name,
                $user_sector_name
            );
        }

        return sprintf(
            __('Você não possui permissão para este acesso. Seu setor atual é %s.', 'smtt-intranet'),
            $user_sector_name
        );
    }
}
