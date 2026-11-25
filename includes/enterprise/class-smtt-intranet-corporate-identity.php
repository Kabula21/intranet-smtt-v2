<?php
/** Verified SSO identity; never trust HTTP headers or editable WordPress profile data. */
if (!defined('ABSPATH')) { exit; }
class SMTT_Intranet_Corporate_Identity {
    public static function current() {
        $user_id = get_current_user_id();
        if (!$user_id) { return new WP_Error('smtt_sso_login', 'Faça login por SSO corporativo.'); }
        /**
         * A trusted server-side SSO integration must return:
         * array('verified'=>true, 'upn'=>'user@domain') OR
         * array('verified'=>true, 'samaccountname'=>'user', 'sid'=>'S-1-...').
         * No WordPress username, browser-provided header or editable meta is accepted.
         */
        $claim = apply_filters('smtt_intranet_verified_ad_identity', null, $user_id);
        if (!is_array($claim) || ($claim['verified'] ?? false) !== true) {
            return new WP_Error('smtt_sso_not_configured', 'O SSO ainda não forneceu uma identidade AD validada.');
        }
        $upn = isset($claim['upn']) ? (string) $claim['upn'] : '';
        $sam = isset($claim['samaccountname']) ? (string) $claim['samaccountname'] : '';
        if (($upn === '' && $sam === '') || strlen($upn) > 190 || strlen($sam) > 190) {
            return new WP_Error('smtt_sso_invalid', 'O SSO forneceu dados incompletos.');
        }
        // A short per-session cache bounds AD queries, but never grants stale admin rights beyond its TTL.
        $key = 'smtt_id_' . md5($user_id . '|' . wp_get_session_token() . '|' . $upn . '|' . $sam);
        $cached = get_transient($key);
        if (is_array($cached) && !empty($cached['sid'])) { return $cached; }
        $link = SMTT_Intranet_AD::connect();
        if (is_wp_error($link)) { return $link; }
        $user = SMTT_Intranet_AD::find_user($link, $claim);
        if (is_wp_error($user)) { @ldap_unbind($link); return $user; }
        $groups = SMTT_Intranet_AD::user_group_sids($link, $user['dn']);
        @ldap_unbind($link);
        if (is_wp_error($groups)) { return $groups; }
        $user['groups'] = $groups;
        set_transient($key, $user, 60);
        return $user;
    }

    /**
     * Prévia local de homologação: somente administradores WordPress em um
     * host de desenvolvimento identificado no site_url/home_url (não em HTTP_HOST).
     * Nunca é usada em domínio corporativo ou com ambiente explicitamente de
     * produção. NÃO cria identidade AD ou libera Access Gate/FortiGate.
     */
    public static function is_local_preview_admin() {
        if (!is_user_logged_in() || !current_user_can('manage_options')) { return false; }
        if (defined('SMTT_INTRANET_DISABLE_LOCAL_PREVIEW') && SMTT_INTRANET_DISABLE_LOCAL_PREVIEW) { return false; }
        // WordPress assumes production when WP_ENVIRONMENT_TYPE is not explicitly set.
        // Never grant global CPD powers based on a .local/.test hostname alone.
        if (!defined('WP_ENVIRONMENT_TYPE') || !in_array(WP_ENVIRONMENT_TYPE, array('local','development'), true)) { return false; }
        $host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
        if ($host === '') { return false; }
        if (in_array($host, array('localhost','127.0.0.1','::1'), true)) { return true; }
        return (bool) preg_match('/\.(?:local|localhost|test)$/', $host);
    }

    public static function is_admin() {
        if (self::is_local_preview_admin()) { return true; }
        $cpd = (string) get_option(SMTT_Intranet_AD::OPT_CPD, '');
        $admin = (string) get_option(SMTT_Intranet_AD::OPT_ADMINS, '');
        if ($cpd === '' || $admin === '' || $cpd === $admin) { return false; }
        $identity = self::current();
        if (is_wp_error($identity)) { return false; }
        return in_array($cpd, $identity['groups'], true) && in_array($admin, $identity['groups'], true);
    }

    public static function is_verified() {
        return !is_wp_error(self::current());
    }
}
