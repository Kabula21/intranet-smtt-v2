<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Access_Gate_Repository {

    public function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'smtt_intranet_access_gate';
    }

    public function record_passage($user_id, $identity, $windows_username = '', $computer_name = '', $ip_address = '', $source = 'browser') {
        global $wpdb;

        $user_id = (int) $user_id;
        $user = get_userdata($user_id);

        if (!$user) {
            return new WP_Error('smtt_gate_invalid_user', __('Usuário inválido para registro do Access Gate.', 'smtt-intranet'));
        }

        $now          = current_time('mysql');
        $access_date  = current_time('Y-m-d');
        $valid_until  = $access_date . ' 23:59:59';
        $table        = $this->table_name();
        $existing_id  = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE user_id = %d AND access_date = %s LIMIT 1",
                $user_id,
                $access_date
            )
        );

        $data = array(
            'identity'         => sanitize_text_field($identity),
            'windows_username' => sanitize_text_field($windows_username),
            'user_login'       => sanitize_user($user->user_login, true),
            'display_name'     => sanitize_text_field($user->display_name),
            'computer_name'    => sanitize_text_field($computer_name),
            'ip_address'       => sanitize_text_field($ip_address),
            'last_seen_at'     => $now,
            'valid_until'      => $valid_until,
            'source'           => sanitize_key($source),
            'status'           => 'authorized',
            'updated_at'       => $now,
        );

        $format = array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');

        if ($existing_id) {
            $updated = $wpdb->update(
                $table,
                $data,
                array('id' => (int) $existing_id),
                $format,
                array('%d')
            );

            if ($updated === false) {
                return new WP_Error('smtt_gate_db_update', __('Não foi possível atualizar a passagem no Access Gate.', 'smtt-intranet'));
            }

            return $this->get_by_id((int) $existing_id);
        }

        $data['user_id']       = $user_id;
        $data['access_date']   = $access_date;
        $data['first_seen_at'] = $now;
        $data['authorized_at'] = $now;
        $data['created_at']    = $now;

        $insert_format = array_merge(
            $format,
            array('%d', '%s', '%s', '%s', '%s')
        );

        // Reordena para refletir a ordem real do array após as inclusões acima.
        $insert_format = array(
            '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
            '%d', '%s', '%s', '%s', '%s'
        );

        $inserted = $wpdb->insert($table, $data, $insert_format);

        if (!$inserted) {
            // Em concorrência (duas abas ao mesmo tempo), a chave única pode vencer em outra requisição.
            $existing_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE user_id = %d AND access_date = %s LIMIT 1",
                    $user_id,
                    $access_date
                )
            );

            if ($existing_id) {
                return $this->get_by_id((int) $existing_id);
            }

            return new WP_Error('smtt_gate_db_insert', __('Não foi possível registrar a passagem no Access Gate.', 'smtt-intranet'));
        }

        return $this->get_by_id((int) $wpdb->insert_id);
    }

    public function get_by_id($id) {
        global $wpdb;
        $table = $this->table_name();

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d LIMIT 1", (int) $id),
            ARRAY_A
        );
    }

    public function get_current_for_user($user_id) {
        global $wpdb;
        $table = $this->table_name();

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE user_id = %d ORDER BY authorized_at DESC, id DESC LIMIT 1",
                (int) $user_id
            ),
            ARRAY_A
        );
    }

    public function get_current_for_identity($identity) {
        global $wpdb;
        $table = $this->table_name();
        $identity = trim((string) $identity);

        if ($identity === '') {
            return null;
        }

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE LOWER(identity) = LOWER(%s)
                    OR LOWER(windows_username) = LOWER(%s)
                    OR LOWER(user_login) = LOWER(%s)
                 ORDER BY authorized_at DESC, id DESC
                 LIMIT 1",
                $identity,
                $identity,
                $identity
            ),
            ARRAY_A
        );
    }

    public function get_current_for_verified_identity($identity) {
        global $wpdb;
        $identity = trim((string) $identity);
        if ($identity === '' || !filter_var($identity, FILTER_VALIDATE_EMAIL)) { return null; }
        $table = $this->table_name();
        // Network identity must match the validated corporate UPN exactly;
        // legacy WordPress logins and manually entered profile aliases do not qualify.
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE LOWER(identity) = LOWER(%s)
             AND source = 'browser_verified' ORDER BY authorized_at DESC, id DESC LIMIT 1",
            $identity
        ), ARRAY_A);
    }

    public function is_row_authorized($row) {
        if (empty($row) || !is_array($row)) {
            return false;
        }

        if (($row['status'] ?? '') !== 'authorized') {
            return false;
        }

        $valid_until = isset($row['valid_until']) ? (string) $row['valid_until'] : '';
        if ($valid_until === '') {
            return false;
        }

        // DATETIME em formato MySQL é ordenável lexicograficamente e ambos os valores
        // são produzidos no fuso configurado do WordPress.
        return $valid_until >= current_time('mysql');
    }

    public function count_authorized_today() {
        global $wpdb;
        $table = $this->table_name();
        $today = current_time('Y-m-d');
        $now   = current_time('mysql');

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT user_id) FROM {$table}
                 WHERE access_date = %s AND status = 'authorized' AND valid_until >= %s",
                $today,
                $now
            )
        );
    }

    public function get_recent($limit = 50, $offset = 0, $search = '') {
        global $wpdb;
        $table = $this->table_name();
        $limit = max(1, min(200, (int) $limit));
        $offset = max(0, (int) $offset);

        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table}
                     WHERE display_name LIKE %s OR user_login LIKE %s OR identity LIKE %s OR windows_username LIKE %s OR ip_address LIKE %s
                     ORDER BY authorized_at DESC, id DESC LIMIT %d OFFSET %d",
                    $like,
                    $like,
                    $like,
                    $like,
                    $like,
                    $limit,
                    $offset
                ),
                ARRAY_A
            );
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} ORDER BY authorized_at DESC, id DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            ),
            ARRAY_A
        );
    }

    public function count_recent($search = '') {
        global $wpdb;
        $table = $this->table_name();

        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            return (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table}
                     WHERE display_name LIKE %s OR user_login LIKE %s OR identity LIKE %s OR windows_username LIKE %s OR ip_address LIKE %s",
                    $like,
                    $like,
                    $like,
                    $like,
                    $like
                )
            );
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    }

    public function prune($days) {
        global $wpdb;
        $table = $this->table_name();
        $days = max(1, (int) $days);
        $cutoff = date_i18n('Y-m-d H:i:s', current_time('timestamp') - ($days * DAY_IN_SECONDS));

        return $wpdb->query(
            $wpdb->prepare("DELETE FROM {$table} WHERE created_at < %s", $cutoff)
        );
    }
}
