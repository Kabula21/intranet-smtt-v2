<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Access_Gate_Identity {

    const META_WINDOWS_USERNAME = '_smtt_access_gate_windows_username';
    const META_UPN              = '_smtt_access_gate_upn';

    public function get_for_user($user_id) {
        $user_id = (int) $user_id;
        $user = get_userdata($user_id);

        if (!$user) {
            return array(
                'identity'         => '',
                'windows_username' => '',
                'upn'              => '',
            );
        }

        $windows_username = trim((string) get_user_meta($user_id, self::META_WINDOWS_USERNAME, true));
        $upn              = trim((string) get_user_meta($user_id, self::META_UPN, true));
        $identity         = $windows_username !== '' ? $windows_username : ($upn !== '' ? $upn : $user->user_login);

        /**
         * Permite que a futura camada de SSO injete uma identidade corporativa
         * já validada (Kerberos/OIDC/SAML) sem alterar o Access Gate.
         */
        $identity = apply_filters('smtt_intranet_access_gate_identity', $identity, $user_id, $windows_username, $upn);

        return array(
            'identity'         => sanitize_text_field((string) $identity),
            'windows_username' => sanitize_text_field($windows_username),
            'upn'              => sanitize_text_field($upn),
        );
    }

    public function get_client_ip() {
        // REMOTE_ADDR é deliberadamente usado como fonte padrão. Cabeçalhos de proxy
        // só devem ser confiados quando a infraestrutura estiver explicitamente configurada.
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        return apply_filters('smtt_intranet_access_gate_client_ip', $ip);
    }

    public function get_computer_name() {
        // Navegadores modernos não expõem o hostname do Windows. A futura integração
        // (agente/proxy/GPO) pode preencher este valor por filtro servidor-a-servidor.
        return sanitize_text_field((string) apply_filters('smtt_intranet_access_gate_computer_name', ''));
    }
}
