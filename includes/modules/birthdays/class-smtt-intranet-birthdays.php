<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Birthdays {

    const POST_TYPE = 'smtt_birthday_person';

    const OPTION_DISPLAY_MODE   = 'smtt_birthdays_display_mode';
    const OPTION_SHOW_PHOTO     = 'smtt_birthdays_show_photo';
    const OPTION_SHOW_SECTOR    = 'smtt_birthdays_show_sector';
    const OPTION_SHOW_ROLE      = 'smtt_birthdays_show_role';
    const OPTION_MAX_ITEMS      = 'smtt_birthdays_max_items';

    public function register() {
        add_action('init', array($this, 'register_post_type'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_media_scripts'));
    }

    public function register_post_type() {
        $labels = array(
            'name'               => __('Aniversariantes', 'smtt-intranet'),
            'singular_name'      => __('Colaborador', 'smtt-intranet'),
            'menu_name'          => __('Aniversariantes', 'smtt-intranet'),
            'add_new'            => __('Adicionar novo', 'smtt-intranet'),
            'add_new_item'       => __('Adicionar colaborador', 'smtt-intranet'),
            'edit_item'          => __('Editar colaborador', 'smtt-intranet'),
            'new_item'           => __('Novo colaborador', 'smtt-intranet'),
            'view_item'          => __('Ver colaborador', 'smtt-intranet'),
            'search_items'       => __('Buscar colaboradores', 'smtt-intranet'),
            'not_found'          => __('Nenhum colaborador encontrado', 'smtt-intranet'),
            'not_found_in_trash' => __('Nenhum colaborador encontrado na lixeira', 'smtt-intranet'),
        );

        $args = array(
            'labels'          => $labels,
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => 'smtt-intranet',
            'supports'        => array('title'),
            'taxonomies'      => array('smtt_sector'),
            'capability_type' => 'post',
            'map_meta_cap'    => true,
        );

        register_post_type(self::POST_TYPE, $args);
    }

    public function register_settings() {
        register_setting('smtt_birthdays_settings', self::OPTION_DISPLAY_MODE);
        register_setting('smtt_birthdays_settings', self::OPTION_SHOW_PHOTO);
        register_setting('smtt_birthdays_settings', self::OPTION_SHOW_SECTOR);
        register_setting('smtt_birthdays_settings', self::OPTION_SHOW_ROLE);
        register_setting('smtt_birthdays_settings', self::OPTION_MAX_ITEMS);
    }

    public function enqueue_media_scripts($hook) {
        if (strpos((string) $hook, 'smtt_birthday_person') === false) {
            return;
        }

        wp_enqueue_media();
    }

    public function add_meta_boxes() {
        add_meta_box(
            'smtt_birthday_details',
            __('Dados do Colaborador', 'smtt-intranet'),
            array($this, 'render_details_meta_box'),
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render_details_meta_box($post) {
        wp_nonce_field('smtt_save_birthday_person', 'smtt_birthday_person_nonce');

        $role         = get_post_meta($post->ID, '_smtt_role', true);
        $birth_date   = get_post_meta($post->ID, '_smtt_birth_date', true);
        $status       = get_post_meta($post->ID, '_smtt_status', true);
        $photo_id     = get_post_meta($post->ID, '_smtt_photo_id', true);
        $photo_url    = $photo_id ? wp_get_attachment_image_url((int) $photo_id, 'medium') : '';

        $sector_terms = wp_get_object_terms($post->ID, 'smtt_sector', array('fields' => 'ids'));
        $selected_sector = !empty($sector_terms) ? (int) $sector_terms[0] : 0;
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="smtt_sector_term"><?php echo esc_html__('Setor', 'smtt-intranet'); ?></label></th>
                <td>
                    <?php
                    wp_dropdown_categories(array(
                        'taxonomy'         => 'smtt_sector',
                        'name'             => 'smtt_sector_term',
                        'orderby'          => 'name',
                        'show_option_none' => __('Selecione um setor', 'smtt-intranet'),
                        'hide_empty'       => false,
                        'hierarchical'     => true,
                        'selected'         => $selected_sector,
                    ));
                    ?>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smtt_role"><?php echo esc_html__('Função / Cargo', 'smtt-intranet'); ?></label></th>
                <td>
                    <input type="text" name="smtt_role" id="smtt_role" class="regular-text" value="<?php echo esc_attr($role); ?>">
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smtt_birth_date"><?php echo esc_html__('Data de nascimento', 'smtt-intranet'); ?></label></th>
                <td>
                    <input type="date" name="smtt_birth_date" id="smtt_birth_date" value="<?php echo esc_attr($birth_date); ?>">
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smtt_status"><?php echo esc_html__('Status', 'smtt-intranet'); ?></label></th>
                <td>
                    <select name="smtt_status" id="smtt_status">
                        <option value="ativo" <?php selected($status, 'ativo'); ?>><?php echo esc_html__('Ativo', 'smtt-intranet'); ?></option>
                        <option value="inativo" <?php selected($status, 'inativo'); ?>><?php echo esc_html__('Inativo', 'smtt-intranet'); ?></option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php echo esc_html__('Foto', 'smtt-intranet'); ?></th>
                <td>
                    <input type="hidden" name="smtt_photo_id" id="smtt_photo_id" value="<?php echo esc_attr($photo_id); ?>">
                    <button type="button" class="button smtt-upload-birthday-photo"><?php echo esc_html__('Selecionar foto', 'smtt-intranet'); ?></button>
                    <button type="button" class="button smtt-remove-birthday-photo"><?php echo esc_html__('Remover', 'smtt-intranet'); ?></button>

                    <div class="smtt-birthday-photo-preview" style="margin-top:10px;">
                        <?php if ($photo_url) : ?>
                            <img src="<?php echo esc_url($photo_url); ?>" style="max-width:180px;height:auto;border:1px solid #ddd;padding:4px;">
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        </table>

        <script>
        jQuery(function($){
            $('.smtt-upload-birthday-photo').on('click', function(e){
                e.preventDefault();

                const frame = wp.media({
                    title: 'Selecionar foto',
                    button: { text: 'Usar foto' },
                    multiple: false
                });

                frame.on('select', function(){
                    const attachment = frame.state().get('selection').first().toJSON();
                    $('#smtt_photo_id').val(attachment.id);
                    $('.smtt-birthday-photo-preview').html('<img src="' + attachment.url + '" style="max-width:180px;height:auto;border:1px solid #ddd;padding:4px;">');
                });

                frame.open();
            });

            $('.smtt-remove-birthday-photo').on('click', function(e){
                e.preventDefault();
                $('#smtt_photo_id').val('');
                $('.smtt-birthday-photo-preview').html('');
            });
        });
        </script>
        <?php
    }

    public function save_meta_boxes($post_id) {
        if (!isset($_POST['smtt_birthday_person_nonce'])) {
            return;
        }

        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['smtt_birthday_person_nonce'])), 'smtt_save_birthday_person')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (get_post_type($post_id) !== self::POST_TYPE) {
            return;
        }

        update_post_meta($post_id, '_smtt_role', isset($_POST['smtt_role']) ? sanitize_text_field(wp_unslash($_POST['smtt_role'])) : '');
        update_post_meta($post_id, '_smtt_birth_date', isset($_POST['smtt_birth_date']) ? sanitize_text_field(wp_unslash($_POST['smtt_birth_date'])) : '');
        update_post_meta($post_id, '_smtt_status', isset($_POST['smtt_status']) ? sanitize_text_field(wp_unslash($_POST['smtt_status'])) : 'ativo');
        update_post_meta($post_id, '_smtt_photo_id', isset($_POST['smtt_photo_id']) ? intval($_POST['smtt_photo_id']) : 0);

        $sector_term = isset($_POST['smtt_sector_term']) ? intval($_POST['smtt_sector_term']) : 0;

        if ($sector_term > 0) {
            wp_set_object_terms($post_id, array($sector_term), 'smtt_sector');
        } else {
            wp_set_object_terms($post_id, array(), 'smtt_sector');
        }
    }

    public function get_month_birthdays() {
        $items = get_posts(array(
            'post_type'              => self::POST_TYPE,
            'posts_per_page'         => -1,
            'post_status'            => 'publish',
            'update_post_meta_cache' => true,
            'update_post_term_cache' => true,
        ));

        $items = array_values(array_filter($items, function($item) {
            $status     = get_post_meta($item->ID, '_smtt_status', true);
            $birth_date = trim((string) get_post_meta($item->ID, '_smtt_birth_date', true));

            return $status === 'ativo' && $birth_date !== '' && strtotime($birth_date);
        }));

        usort($items, function($a, $b) {
            $date_a = get_post_meta($a->ID, '_smtt_birth_date', true);
            $date_b = get_post_meta($b->ID, '_smtt_birth_date', true);

            $month_a = (int) date('m', strtotime($date_a));
            $month_b = (int) date('m', strtotime($date_b));
            $day_a   = (int) date('d', strtotime($date_a));
            $day_b   = (int) date('d', strtotime($date_b));

            if ($month_a === $month_b) {
                if ($day_a === $day_b) {
                    return strcasecmp(get_the_title($a->ID), get_the_title($b->ID));
                }
                return $day_a <=> $day_b;
            }

            return $month_a <=> $month_b;
        });

        return $items;
    }

    /**
     * Lista usada pelo card do front-end.
     * Exibe o mês corrente e prioriza hoje/próximos aniversários.
     */
    public function get_front_birthdays($month = null, $preloaded_items = null) {
        $month = $month !== null ? (int) $month : (int) current_time('m');

        if ($month < 1 || $month > 12) {
            $month = (int) current_time('m');
        }

        $today_month = (int) current_time('m');
        $today_day   = (int) current_time('d');
        $items       = is_array($preloaded_items) ? $preloaded_items : $this->get_month_birthdays();

        $items = array_values(array_filter($items, function($item) use ($month) {
            $birth_date = get_post_meta($item->ID, '_smtt_birth_date', true);
            $timestamp  = $birth_date ? strtotime($birth_date) : false;

            return $timestamp && (int) date('m', $timestamp) === $month;
        }));

        usort($items, function($a, $b) use ($month, $today_month, $today_day) {
            $date_a = get_post_meta($a->ID, '_smtt_birth_date', true);
            $date_b = get_post_meta($b->ID, '_smtt_birth_date', true);
            $day_a  = (int) date('d', strtotime($date_a));
            $day_b  = (int) date('d', strtotime($date_b));

            if ($month === $today_month) {
                $distance_a = $day_a >= $today_day ? $day_a - $today_day : 100 + $day_a;
                $distance_b = $day_b >= $today_day ? $day_b - $today_day : 100 + $day_b;

                if ($distance_a !== $distance_b) {
                    return $distance_a <=> $distance_b;
                }
            }

            if ($day_a === $day_b) {
                return strcasecmp(get_the_title($a->ID), get_the_title($b->ID));
            }

            return $day_a <=> $day_b;
        });

        return $items;
    }

    private function normalize_csv_text($value) {
        $value = (string) $value;
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);

        if (function_exists('mb_check_encoding') && !mb_check_encoding($value, 'UTF-8')) {
            $converted = @mb_convert_encoding($value, 'UTF-8', array('Windows-1252', 'ISO-8859-1'));
            if (is_string($converted) && $converted !== '') {
                $value = $converted;
            }
        }

        return trim($value);
    }

    private function normalize_csv_header($value) {
        $value = $this->normalize_csv_text($value);
        $value = function_exists('remove_accents') ? remove_accents($value) : $value;
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        return trim((string) $value, '_');
    }

    private function normalize_person_key($value) {
        $value = $this->normalize_csv_text($value);
        $value = function_exists('remove_accents') ? remove_accents($value) : $value;
        $value = strtolower($value);
        $value = preg_replace('/\s+/', ' ', $value);
        return trim((string) $value);
    }

    private function detect_csv_delimiter($file_path) {
        $sample = @file_get_contents($file_path, false, null, 0, 4096);
        if (!is_string($sample) || $sample === '') {
            return ',';
        }

        $first_line = preg_split('/\r\n|\n|\r/', $sample);
        $first_line = isset($first_line[0]) ? $first_line[0] : $sample;

        $candidates = array(',', ';', "\t");
        $best = ',';
        $best_count = -1;

        foreach ($candidates as $candidate) {
            $count = substr_count($first_line, $candidate);
            if ($count > $best_count) {
                $best = $candidate;
                $best_count = $count;
            }
        }

        return $best;
    }

    private function parse_birth_date($raw_date) {
        $raw_date = $this->normalize_csv_text($raw_date);
        if ($raw_date === '') {
            return '';
        }

        $day = 0;
        $month = 0;
        $year = 0;

        if (preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/', $raw_date, $matches)) {
            $year  = (int) $matches[1];
            $month = (int) $matches[2];
            $day   = (int) $matches[3];
        } elseif (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})$/', $raw_date, $matches)) {
            $day   = (int) $matches[1];
            $month = (int) $matches[2];
            $year  = (int) $matches[3];
        } elseif (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{2})$/', $raw_date, $matches)) {
            $day   = (int) $matches[1];
            $month = (int) $matches[2];
            $short_year = (int) $matches[3];
            $current_two_digit_year = (int) current_time('y');
            $year = $short_year <= $current_two_digit_year ? 2000 + $short_year : 1900 + $short_year;
        } else {
            return '';
        }

        if ($year < 1900 || $year > ((int) current_time('Y')) || !checkdate($month, $day, $year)) {
            return '';
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    public function import_from_csv($file_path) {
        if (!file_exists($file_path)) {
            return array(
                'success'  => false,
                'message'  => __('Arquivo CSV não encontrado.', 'smtt-intranet'),
                'inserted' => 0,
                'updated'  => 0,
                'errors'   => array(),
            );
        }

        $delimiter = $this->detect_csv_delimiter($file_path);
        $handle = fopen($file_path, 'r');

        if (!$handle) {
            return array(
                'success'  => false,
                'message'  => __('Não foi possível abrir o arquivo CSV.', 'smtt-intranet'),
                'inserted' => 0,
                'updated'  => 0,
                'errors'   => array(),
            );
        }

        $raw_header = fgetcsv($handle, 0, $delimiter);
        $raw_header = is_array($raw_header) ? $raw_header : array();
        $columns = array();

        $aliases = array(
            'nome'            => array('nome', 'nome_completo', 'colaborador', 'funcionario', 'funcionaria', 'name'),
            'setor'           => array('setor', 'departamento', 'lotacao'),
            'cargo'           => array('cargo', 'funcao', 'funcao_cargo', 'cargo_funcao'),
            'data_nascimento' => array('data_de_nascimento', 'data_nascimento', 'nascimento', 'dt_nascimento', 'data'),
            'status'          => array('status', 'situacao'),
        );

        foreach ($raw_header as $index => $header_value) {
            $normalized = $this->normalize_csv_header($header_value);
            foreach ($aliases as $canonical => $accepted) {
                if (in_array($normalized, $accepted, true) && !isset($columns[$canonical])) {
                    $columns[$canonical] = (int) $index;
                    break;
                }
            }
        }

        if (!isset($columns['nome']) || !isset($columns['data_nascimento'])) {
            fclose($handle);

            return array(
                'success'  => false,
                'message'  => __('Cabeçalho inválido. O CSV precisa conter pelo menos as colunas Nome e Data de Nascimento.', 'smtt-intranet'),
                'inserted' => 0,
                'updated'  => 0,
                'errors'   => array(),
            );
        }

        $has_sector = isset($columns['setor']);
        $has_role   = isset($columns['cargo']);
        $has_status = isset($columns['status']);
        $simple_format = !$has_sector && !$has_role && !$has_status;

        $existing_map = array();
        $existing_ids = get_posts(array(
            'post_type'              => self::POST_TYPE,
            'posts_per_page'         => -1,
            'post_status'            => array('publish', 'draft', 'pending', 'future', 'private', 'trash'),
            'fields'                 => 'ids',
            'orderby'                => 'ID',
            'order'                  => 'ASC',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ));

        foreach ($existing_ids as $existing_id) {
            $key = $this->normalize_person_key(get_the_title($existing_id));
            if ($key !== '' && !isset($existing_map[$key])) {
                $existing_map[$key] = (int) $existing_id;
            }
        }

        $inserted = 0;
        $updated  = 0;
        $errors   = array();
        $row_num  = 1;
        $seen_file_names = array();

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $row_num++;

            if (!is_array($row) || !array_filter($row, static function($value) { return trim((string) $value) !== ''; })) {
                continue;
            }

            $name_raw = isset($row[$columns['nome']]) ? $this->normalize_csv_text($row[$columns['nome']]) : '';
            $date_raw = isset($row[$columns['data_nascimento']]) ? $this->normalize_csv_text($row[$columns['data_nascimento']]) : '';
            $nome = sanitize_text_field($name_raw);
            $data_nascimento = $this->parse_birth_date($date_raw);

            if ($nome === '' || $date_raw === '') {
                $errors[] = sprintf(__('Linha %d ignorada: nome ou data de nascimento vazios.', 'smtt-intranet'), $row_num);
                continue;
            }

            if ($data_nascimento === '') {
                $errors[] = sprintf(__('Linha %d ignorada: data "%s" inválida para %s.', 'smtt-intranet'), $row_num, $date_raw, $nome);
                continue;
            }

            $name_key = $this->normalize_person_key($nome);
            if (isset($seen_file_names[$name_key])) {
                $errors[] = sprintf(__('Linha %d ignorada: %s já apareceu anteriormente no mesmo CSV.', 'smtt-intranet'), $row_num, $nome);
                continue;
            }
            $seen_file_names[$name_key] = true;

            $setor_nome = $has_sector && isset($row[$columns['setor']])
                ? sanitize_text_field($this->normalize_csv_text($row[$columns['setor']]))
                : '';
            $cargo = $has_role && isset($row[$columns['cargo']])
                ? sanitize_text_field($this->normalize_csv_text($row[$columns['cargo']]))
                : '';
            $status = $has_status && isset($row[$columns['status']])
                ? sanitize_key($this->normalize_csv_text($row[$columns['status']]))
                : 'ativo';

            if ($status !== 'ativo' && $status !== 'inativo') {
                $status = 'ativo';
            }

            $is_existing = isset($existing_map[$name_key]);
            if ($is_existing) {
                $post_id = (int) $existing_map[$name_key];
                $result = wp_update_post(array(
                    'ID'          => $post_id,
                    'post_title'  => $nome,
                    'post_status' => 'publish',
                ), true);

                if (is_wp_error($result)) {
                    $errors[] = sprintf(__('Linha %d ignorada: falha ao atualizar %s.', 'smtt-intranet'), $row_num, $nome);
                    continue;
                }

                $updated++;
            } else {
                $post_id = wp_insert_post(array(
                    'post_type'   => self::POST_TYPE,
                    'post_status' => 'publish',
                    'post_title'  => $nome,
                ), true);

                if (is_wp_error($post_id) || !$post_id) {
                    $errors[] = sprintf(__('Linha %d ignorada: falha ao criar %s.', 'smtt-intranet'), $row_num, $nome);
                    continue;
                }

                $post_id = (int) $post_id;
                $existing_map[$name_key] = $post_id;
                $inserted++;
            }

            update_post_meta($post_id, '_smtt_birth_date', $data_nascimento);
            update_post_meta($post_id, '_smtt_status', $status);

            // No CSV simplificado, setor e cargo não existem: preserve os dados já
            // cadastrados em registros existentes e deixe vazio apenas em novos itens.
            if ($has_role || !$is_existing) {
                update_post_meta($post_id, '_smtt_role', $cargo);
            }

            if ($has_sector) {
                if ($setor_nome !== '') {
                    $term = term_exists($setor_nome, 'smtt_sector');
                    if (!$term) {
                        $term = wp_insert_term($setor_nome, 'smtt_sector');
                    }

                    $term_id = 0;
                    if (is_array($term) && !empty($term['term_id'])) {
                        $term_id = (int) $term['term_id'];
                    } elseif (is_int($term)) {
                        $term_id = $term;
                    }

                    if (!is_wp_error($term) && $term_id > 0) {
                        wp_set_object_terms($post_id, array($term_id), 'smtt_sector');
                    }
                }
            }
        }

        fclose($handle);

        return array(
            'success'  => true,
            'message'  => $simple_format
                ? __('Importação concluída. Formato simplificado (Nome + Data de Nascimento) detectado automaticamente.', 'smtt-intranet')
                : __('Importação concluída.', 'smtt-intranet'),
            'inserted' => $inserted,
            'updated'  => $updated,
            'errors'   => $errors,
        );
    }

}