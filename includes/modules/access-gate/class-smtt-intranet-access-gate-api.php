<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Access_Gate_API {

    private $repository;
    private $identity;

    public function __construct($repository, $identity) {
        $this->repository = $repository;
        $this->identity   = $identity;
    }

    public function register_routes() {
        register_rest_route('smtt-intranet/v1', '/gate/start', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array($this, 'start_passage'),
            'permission_callback' => array($this, 'logged_in_permission'),
        ));

        register_rest_route('smtt-intranet/v1', '/gate/pass', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array($this, 'passage'),
            'permission_callback' => array($this, 'logged_in_permission'),
        ));

        register_rest_route('smtt-intranet/v1', '/gate/status', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'current_status'),
            'permission_callback' => array($this, 'logged_in_permission'),
        ));

        register_rest_route('smtt-intranet/v1', '/gate/network/status', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'network_status'),
            'permission_callback' => array($this, 'network_permission'),
            'args'                => array(
                'identity' => array(
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
    }

    public function logged_in_permission() {
        return is_user_logged_in();
    }

    public function network_permission($request) {
        if (!is_ssl()) {
            return new WP_Error('smtt_gate_https_required', 'A integração de rede exige HTTPS.', array('status' => 403));
        }
        $expected = (string) get_option(SMTT_Intranet_Access_Gate::OPTION_API_KEY, '');
        $provided = (string) $request->get_header('X-SMTT-Gate-Key');

        if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
            return new WP_Error(
                'smtt_gate_forbidden',
                __('Credencial de integração do Access Gate inválida.', 'smtt-intranet'),
                array('status' => 403)
            );
        }

        return true;
    }

    private function ticket_key($user_id, $ticket) {
        // Bound to the actual authenticated WordPress session, not just a username or IP.
        return 'smtt_gate_' . hash_hmac('sha256', $user_id . '|' . wp_get_session_token() . '|' . $ticket, wp_salt('auth'));
    }

    public function start_passage() {
        if (!SMTT_Intranet_Access_Gate::is_enabled()) {
            return new WP_Error('smtt_gate_disabled', 'Access Gate desativado.', array('status' => 409));
        }
        $ticket = wp_generate_password(40, false, false);
        set_transient($this->ticket_key(get_current_user_id(), $ticket), time(), 120);
        return rest_ensure_response(array('ticket' => $ticket, 'visibility_seconds' => SMTT_Intranet_Access_Gate::visibility_seconds()));
    }

    public function passage($request) {
        if (!SMTT_Intranet_Access_Gate::is_enabled()) {
            return rest_ensure_response(array(
                'enabled'    => false,
                'authorized' => false,
            ));
        }

        $user_id = get_current_user_id();
        $ticket = trim((string) $request->get_param('ticket'));
        if (!preg_match('/^[A-Za-z0-9]{40}$/', $ticket)) {
            return new WP_Error('smtt_gate_ticket', 'Inicie a passagem pela Home da Intranet.', array('status' => 403));
        }
        $key = $this->ticket_key($user_id, $ticket);
        $started = get_transient($key);
        if (!$started || (time() - (int) $started) < SMTT_Intranet_Access_Gate::visibility_seconds()) {
            return new WP_Error('smtt_gate_wait', 'Passagem não validada: tempo mínimo não cumprido.', array('status' => 409));
        }
        delete_transient($key);
        $identity_data = $this->identity->get_for_user($user_id);
        // Only server-verified corporate identities may later authorize network access.
        $verified = class_exists('SMTT_Intranet_Corporate_Identity') ? SMTT_Intranet_Corporate_Identity::current() : null;
        $source = 'browser';
        if (is_array($verified) && !empty($verified['sid']) && !empty($verified['upn'])) {
            $identity_data['identity'] = $verified['upn'];
            $identity_data['windows_username'] = $verified['samaccountname'];
            $source = 'browser_verified';
        }

        $row = $this->repository->record_passage(
            $user_id,
            $identity_data['identity'],
            $identity_data['windows_username'],
            $this->identity->get_computer_name(),
            $this->identity->get_client_ip(),
            $source
        );

        if (is_wp_error($row)) {
            return $row;
        }
        // Ledger-only event: verified identity only; no network privileges or FortiGate command.
        if ($source === 'browser_verified' && is_array($verified)) {
            do_action('smtt_intranet_gate_verified_pass', $verified, $row);
        }

        return rest_ensure_response($this->format_status($row));
    }

    public function current_status() {
        if (!SMTT_Intranet_Access_Gate::is_enabled()) {
            return rest_ensure_response(array(
                'enabled'    => false,
                'authorized' => false,
            ));
        }

        $row = $this->repository->get_current_for_user(get_current_user_id());
        return rest_ensure_response($this->format_status($row));
    }

    public function network_status($request) {
        $identity = trim((string) $request->get_param('identity'));
        $row = $this->repository->get_current_for_verified_identity($identity);
        $status = $this->format_status($row);
        // Audit mode only: do not turn logged-in WordPress sessions into network authorizations.
        // v2.0.34 intentionally cannot authorize the network: there is no
        // tested FortiGate enforcement adapter yet. Never enable by a constant.
        $ready = false;
        $network_authorized = false;

        // Resposta reduzida para integração de rede.
        return rest_ensure_response(array(
            'enabled'       => SMTT_Intranet_Access_Gate::is_enabled(),
            'identity'      => $identity,
            'authorized'    => $network_authorized,
            'enforcement_ready' => $ready,
            'authorized_at' => $status['authorized_at'] ?? null,
            'valid_until'   => $status['valid_until'] ?? null,
            'status'        => !$ready ? 'audit_only' : ($network_authorized ? 'authorized' : 'pending'),
        ));
    }

    private function format_status($row) {
        if (empty($row)) {
            return array(
                'enabled'       => SMTT_Intranet_Access_Gate::is_enabled(),
                'authorized'    => false,
                'status'        => 'pending',
                'authorized_at' => null,
                'valid_until'   => null,
            );
        }

        $authorized = SMTT_Intranet_Access_Gate::is_enabled() && $this->repository->is_row_authorized($row);

        return array(
            'enabled'       => SMTT_Intranet_Access_Gate::is_enabled(),
            'authorized'    => $authorized,
            'status'        => $authorized ? 'authorized' : 'pending',
            'authorized_at' => $this->to_iso8601($row['authorized_at'] ?? ''),
            'valid_until'   => $this->to_iso8601($row['valid_until'] ?? ''),
        );
    }

    private function to_iso8601($mysql_datetime) {
        if (!$mysql_datetime) {
            return null;
        }

        $timezone = wp_timezone();
        $date = date_create_from_format('Y-m-d H:i:s', $mysql_datetime, $timezone);

        return $date ? $date->format(DATE_ATOM) : $mysql_datetime;
    }
}
