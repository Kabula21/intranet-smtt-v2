<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Ramais_Repository {

    private static function table_ramais() {
        global $wpdb;
        return $wpdb->prefix . 'smtt_intranet_ramais';
    }

    private static function table_imports() {
        global $wpdb;
        return $wpdb->prefix . 'smtt_intranet_ramais_imports';
    }

    public static function get_all($args = array()) {
        global $wpdb;

        $defaults = array(
            'search' => '',
            'setor'  => '',
            'status' => '',
            'limit'  => 100,
            'offset' => 0,
        );

        $args = wp_parse_args($args, $defaults);

        $table = self::table_ramais();

        $where  = array('1=1');
        $params = array();

        if (!empty($args['search'])) {
            $like = '%' . $wpdb->esc_like($args['search']) . '%';

            $where[] = "(nome LIKE %s OR setor LIKE %s OR cargo LIKE %s OR ramal LIKE %s OR email LIKE %s)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if (!empty($args['setor'])) {
            $where[] = "setor = %s";
            $params[] = $args['setor'];
        }

        if (!empty($args['status'])) {
            $where[] = "status = %s";
            $params[] = $args['status'];
        }

        $limit  = absint($args['limit']);
        $offset = absint($args['offset']);

        $sql = "SELECT *
                FROM {$table}
                WHERE " . implode(' AND ', $where) . "
                ORDER BY setor ASC, nome ASC
                LIMIT %d OFFSET %d";

        $params[] = $limit;
        $params[] = $offset;

        return $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
    }

    /**
     * Consulta enxuta usada no drawer público. Evita SELECT * e transfere do
     * banco apenas os três campos realmente renderizados no frontend.
     */
    public static function get_public_active($limit = 500) {
        global $wpdb;

        $table = self::table_ramais();
        $limit = max(1, min(1000, absint($limit)));

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT setor, cargo, ramal
                 FROM {$table}
                 WHERE status = %s
                 ORDER BY setor ASC, nome ASC
                 LIMIT %d",
                'ativo',
                $limit
            ),
            ARRAY_A
        );
    }

    public static function count_all($args = array()) {
        global $wpdb;

        $defaults = array(
            'search' => '',
            'setor'  => '',
            'status' => '',
        );

        $args = wp_parse_args($args, $defaults);

        $table = self::table_ramais();

        $where  = array('1=1');
        $params = array();

        if (!empty($args['search'])) {
            $like = '%' . $wpdb->esc_like($args['search']) . '%';

            $where[] = "(nome LIKE %s OR setor LIKE %s OR cargo LIKE %s OR ramal LIKE %s OR email LIKE %s)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if (!empty($args['setor'])) {
            $where[] = "setor = %s";
            $params[] = $args['setor'];
        }

        if (!empty($args['status'])) {
            $where[] = "status = %s";
            $params[] = $args['status'];
        }

        $sql = "SELECT COUNT(*)
                FROM {$table}
                WHERE " . implode(' AND ', $where);

        if (!empty($params)) {
            return (int) $wpdb->get_var($wpdb->prepare($sql, $params));
        }

        return (int) $wpdb->get_var($sql);
    }

    public static function get_by_id($id) {
        global $wpdb;

        $table = self::table_ramais();

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d",
                absint($id)
            ),
            ARRAY_A
        );
    }

    public static function get_by_ramal($ramal) {
        global $wpdb;

        $table = self::table_ramais();

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE ramal = %s LIMIT 1",
                sanitize_text_field($ramal)
            ),
            ARRAY_A
        );
    }

    public static function save($data) {
        global $wpdb;

        $table = self::table_ramais();

        $id = isset($data['id']) ? absint($data['id']) : 0;

        $payload = array(
            'nome'            => sanitize_text_field($data['nome'] ?? ''),
            'setor'           => sanitize_text_field($data['setor'] ?? ''),
            'cargo'           => sanitize_text_field($data['cargo'] ?? ''),
            'ramal'           => sanitize_text_field($data['ramal'] ?? ''),
            'telefone_direto' => sanitize_text_field($data['telefone_direto'] ?? ''),
            'whatsapp'        => sanitize_text_field($data['whatsapp'] ?? ''),
            'email'           => sanitize_email($data['email'] ?? ''),
            'unidade'         => sanitize_text_field($data['unidade'] ?? ''),
            'sala'            => sanitize_text_field($data['sala'] ?? ''),
            'observacao'      => sanitize_textarea_field($data['observacao'] ?? ''),
            'status'          => sanitize_text_field($data['status'] ?? 'ativo'),
            'import_batch_id' => isset($data['import_batch_id']) ? absint($data['import_batch_id']) : null,
            'updated_at'      => current_time('mysql'),
        );

        if (empty($payload['nome']) || empty($payload['ramal'])) {
            return new WP_Error(
                'smtt_ramais_invalid_data',
                'Nome e ramal são obrigatórios.'
            );
        }

        if (!empty($payload['email']) && !is_email($payload['email'])) {
            return new WP_Error(
                'smtt_ramais_invalid_email',
                'E-mail inválido.'
            );
        }

        if (!in_array($payload['status'], array('ativo', 'inativo'), true)) {
            $payload['status'] = 'ativo';
        }

        if ($id > 0) {
            $updated = $wpdb->update(
                $table,
                $payload,
                array('id' => $id)
            );

            if ($updated === false) {
                return new WP_Error(
                    'smtt_ramais_update_error',
                    'Erro ao atualizar o ramal.'
                );
            }

            return $id;
        }

        $payload['created_at'] = current_time('mysql');

        $inserted = $wpdb->insert($table, $payload);

        if (!$inserted) {
            return new WP_Error(
                'smtt_ramais_insert_error',
                'Erro ao cadastrar o ramal.'
            );
        }

        return (int) $wpdb->insert_id;
    }

    public static function upsert_from_import($data, $import_batch_id = null) {
        $existing = self::get_by_ramal($data['ramal'] ?? '');

        $data['import_batch_id'] = $import_batch_id;

        if (!empty($existing['id'])) {
            $data['id'] = (int) $existing['id'];
        }

        return self::save($data);
    }

    public static function delete($id) {
        global $wpdb;

        $table = self::table_ramais();

        return $wpdb->delete(
            $table,
            array('id' => absint($id)),
            array('%d')
        );
    }

    public static function create_import_log($arquivo_nome = '') {
        global $wpdb;

        $table = self::table_imports();

        $wpdb->insert(
            $table,
            array(
                'arquivo_nome'  => sanitize_file_name($arquivo_nome),
                'importado_por' => get_current_user_id(),
                'created_at'    => current_time('mysql'),
            )
        );

        return (int) $wpdb->insert_id;
    }

    public static function update_import_log($id, $data) {
        global $wpdb;

        $table = self::table_imports();

        $payload = array(
            'total_linhas'      => absint($data['total_linhas'] ?? 0),
            'total_inseridos'   => absint($data['total_inseridos'] ?? 0),
            'total_atualizados' => absint($data['total_atualizados'] ?? 0),
            'total_ignorados'   => absint($data['total_ignorados'] ?? 0),
            'total_erros'       => absint($data['total_erros'] ?? 0),
            'relatorio_json'    => wp_json_encode($data['relatorio'] ?? array()),
        );

        return $wpdb->update(
            $table,
            $payload,
            array('id' => absint($id))
        );
    }

    public static function get_imports($limit = 20) {
        global $wpdb;

        $table = self::table_imports();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                 FROM {$table}
                 ORDER BY created_at DESC
                 LIMIT %d",
                absint($limit)
            ),
            ARRAY_A
        );
    }

    public static function get_setores() {
        global $wpdb;

        $table = self::table_ramais();

        return $wpdb->get_col(
            "SELECT DISTINCT setor
             FROM {$table}
             WHERE setor IS NOT NULL
             AND setor != ''
             ORDER BY setor ASC"
        );
    }
}