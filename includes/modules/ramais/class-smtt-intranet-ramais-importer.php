<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Ramais_Importer {

    const CFB_FREESECT   = 0xFFFFFFFF;
    const CFB_ENDOFCHAIN = 0xFFFFFFFE;
    const CFB_FATSECT    = 0xFFFFFFFD;
    const CFB_DIFSECT    = 0xFFFFFFFC;

    private static function expected_headers() {
        return array(
            'nome'            => array('nome', 'nome completo', 'servidor', 'funcionario', 'funcionário'),
            'setor'           => array('setor', 'departamento', 'unidade administrativa'),
            'cargo'           => array('cargo', 'funcao', 'função'),
            'ramal'           => array('ramal', 'telefone interno'),
            'telefone_direto' => array('telefone direto', 'telefone', 'fixo'),
            'whatsapp'        => array('whatsapp', 'zap', 'celular'),
            'email'           => array('email', 'e-mail', 'correio eletrônico'),
            'unidade'         => array('unidade', 'local'),
            'sala'            => array('sala', 'localizacao', 'localização'),
            'observacao'      => array('observacao', 'observação', 'obs'),
            'status'          => array('status', 'situacao', 'situação'),
        );
    }

    private static function normalize_text($text) {
        $text = trim((string) $text);

        if (function_exists('mb_convert_encoding')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        } elseif ($text !== '' && !preg_match('//u', $text) && function_exists('iconv')) {
            $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $text);
            if ($converted !== false) {
                $text = $converted;
            }
        }

        $normalized = preg_replace('/\s+/u', ' ', $text);
        return $normalized !== null ? $normalized : trim($text);
    }

    private static function normalize_header($header) {
        $header = self::normalize_text($header);
        $header = strtolower($header);

        $header = str_replace(
            array('á', 'à', 'â', 'ã', 'é', 'ê', 'í', 'ó', 'ô', 'õ', 'ú', 'ç'),
            array('a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c'),
            $header
        );

        $header = preg_replace('/[^a-z0-9\s_-]/', '', $header);
        $header = preg_replace('/\s+/', ' ', $header);

        return trim($header);
    }

    private static function detect_delimiter($line) {
        $semicolon_count = substr_count($line, ';');
        $comma_count     = substr_count($line, ',');

        return $semicolon_count >= $comma_count ? ';' : ',';
    }

    private static function build_header_map($headers) {
        $map = array();
        $expected = self::expected_headers();

        foreach ($headers as $index => $header) {
            $normalized_header = self::normalize_header($header);

            foreach ($expected as $field => $aliases) {
                foreach ($aliases as $alias) {
                    if ($normalized_header === self::normalize_header($alias)) {
                        $map[$field] = $index;
                        break 2;
                    }
                }
            }
        }

        return $map;
    }

    private static function get_value($row, $map, $field) {
        if (!isset($map[$field])) {
            return '';
        }

        $index = $map[$field];
        return isset($row[$index]) ? self::normalize_text($row[$index]) : '';
    }

    /**
     * O modelo oficial recebido pela SMTT usa apenas SETOR + RAMAL.
     * Para manter compatibilidade com o banco atual, o nome recebe a descrição
     * completa e o setor/cargo são separados quando houver " - " ou " / ".
     */
    private static function split_sector_label($label) {
        $label = self::normalize_text($label);

        if ($label === '') {
            return array('', '');
        }

        foreach (array(' - ', ' / ') as $separator) {
            $pos = strpos($label, $separator);
            if ($pos !== false) {
                $setor = trim(substr($label, 0, $pos));
                $cargo = trim(substr($label, $pos + strlen($separator)));

                if ($setor !== '' && $cargo !== '') {
                    return array($setor, $cargo);
                }
            }
        }

        return array($label, '');
    }

    private static function rows_from_matrix($matrix, $source = 'planilha') {
        if (empty($matrix) || !is_array($matrix)) {
            return new WP_Error('smtt_ramais_empty_sheet', 'A planilha está vazia.');
        }

        $header_row_index = null;
        $header_map = array();
        $headers = array();

        $scan_limit = min(20, count($matrix));
        for ($i = 0; $i < $scan_limit; $i++) {
            $candidate = isset($matrix[$i]) && is_array($matrix[$i]) ? $matrix[$i] : array();
            $candidate_map = self::build_header_map($candidate);

            if (isset($candidate_map['ramal']) && (isset($candidate_map['nome']) || isset($candidate_map['setor']))) {
                $header_row_index = $i;
                $header_map = $candidate_map;
                $headers = $candidate;
                break;
            }
        }

        if ($header_row_index === null) {
            return new WP_Error(
                'smtt_ramais_required_headers',
                'A planilha precisa ter a coluna RAMAL e uma coluna NOME ou SETOR.'
            );
        }

        $rows = array();

        for ($i = $header_row_index + 1; $i < count($matrix); $i++) {
            $row = isset($matrix[$i]) && is_array($matrix[$i]) ? $matrix[$i] : array();

            $non_empty = array_filter($row, static function ($value) {
                return trim((string) $value) !== '';
            });

            if (empty($non_empty)) {
                continue;
            }

            $nome  = self::get_value($row, $header_map, 'nome');
            $setor = self::get_value($row, $header_map, 'setor');
            $cargo = self::get_value($row, $header_map, 'cargo');
            $ramal = self::get_value($row, $header_map, 'ramal');

            // Excel costuma entregar ramal numérico como "1429.0" em alguns leitores.
            if (preg_match('/^\d+\.0+$/', $ramal)) {
                $ramal = preg_replace('/\.0+$/', '', $ramal);
            }

            // Modelo SETOR + RAMAL: converte automaticamente para o modelo interno.
            if ($nome === '' && $setor !== '') {
                $nome_original = $setor;
                list($setor_base, $cargo_detectado) = self::split_sector_label($setor);
                $nome = $nome_original;
                $setor = $setor_base;

                if ($cargo === '' && $cargo_detectado !== '') {
                    $cargo = $cargo_detectado;
                }
            }

            // CSV legado com Nome, mas sem Setor: mantém o comportamento antigo.
            if ($setor === '' && $nome !== '') {
                $setor = $nome;
            }

            $data = array(
                'linha'           => $i + 1,
                'nome'            => $nome,
                'setor'           => $setor,
                'cargo'           => $cargo,
                'ramal'           => $ramal,
                'telefone_direto' => self::get_value($row, $header_map, 'telefone_direto'),
                'whatsapp'        => self::get_value($row, $header_map, 'whatsapp'),
                'email'           => self::get_value($row, $header_map, 'email'),
                'unidade'         => self::get_value($row, $header_map, 'unidade'),
                'sala'            => self::get_value($row, $header_map, 'sala'),
                'observacao'      => self::get_value($row, $header_map, 'observacao'),
                'status'          => self::get_value($row, $header_map, 'status'),
            );

            if (empty($data['status'])) {
                $data['status'] = 'ativo';
            }

            $rows[] = $data;
        }

        return array(
            'headers' => $headers,
            'rows'    => $rows,
            'source'  => $source,
        );
    }

    public static function parse_csv($file_path) {
        if (empty($file_path) || !file_exists($file_path)) {
            return new WP_Error('smtt_ramais_csv_not_found', 'Arquivo CSV não encontrado.');
        }

        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return new WP_Error('smtt_ramais_csv_open_error', 'Não foi possível abrir o arquivo CSV.');
        }

        $first_line = fgets($handle);
        if ($first_line === false) {
            fclose($handle);
            return new WP_Error('smtt_ramais_csv_empty', 'O arquivo CSV está vazio.');
        }

        $delimiter = self::detect_delimiter($first_line);
        rewind($handle);

        $matrix = array();
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (!empty($matrix) || !empty($row)) {
                if (isset($row[0])) {
                    $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]);
                }
                $matrix[] = $row;
            }
        }
        fclose($handle);

        return self::rows_from_matrix($matrix, 'csv');
    }

    /* ---------------------------------------------------------------------
     * Leitor XLS BIFF8 leve
     * ------------------------------------------------------------------ */

    private static function u16($data, $offset) {
        if ($offset < 0 || $offset + 2 > strlen($data)) {
            return 0;
        }
        $v = unpack('vvalue', substr($data, $offset, 2));
        return (int) $v['value'];
    }

    private static function u32($data, $offset) {
        if ($offset < 0 || $offset + 4 > strlen($data)) {
            return self::CFB_FREESECT;
        }
        $v = unpack('Vvalue', substr($data, $offset, 4));
        return (int) $v['value'];
    }

    private static function decode_utf16le($bytes) {
        if ($bytes === '') {
            return '';
        }

        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($bytes, 'UTF-8', 'UTF-16LE');
        }

        if (function_exists('iconv')) {
            $decoded = @iconv('UTF-16LE', 'UTF-8//IGNORE', $bytes);
            if ($decoded !== false) {
                return $decoded;
            }
        }

        return $bytes;
    }

    private static function cfb_sector($data, $sector_size, $sid) {
        if ($sid < 0) {
            return '';
        }
        $offset = ($sid + 1) * $sector_size;
        return substr($data, $offset, $sector_size);
    }

    private static function cfb_read_chain($data, $fat, $sector_size, $start_sid, $limit = 10000) {
        $result = '';
        $sid = $start_sid;
        $seen = array();
        $steps = 0;

        while (
            $sid !== self::CFB_ENDOFCHAIN &&
            $sid !== self::CFB_FREESECT &&
            $sid >= 0 &&
            isset($fat[$sid]) &&
            $steps < $limit
        ) {
            if (isset($seen[$sid])) {
                break;
            }
            $seen[$sid] = true;
            $result .= self::cfb_sector($data, $sector_size, $sid);
            $sid = $fat[$sid];
            $steps++;
        }

        return $result;
    }

    private static function cfb_read_mini_chain($mini_stream, $mini_fat, $mini_sector_size, $start_sid, $limit = 10000) {
        $result = '';
        $sid = $start_sid;
        $seen = array();
        $steps = 0;

        while (
            $sid !== self::CFB_ENDOFCHAIN &&
            $sid !== self::CFB_FREESECT &&
            $sid >= 0 &&
            isset($mini_fat[$sid]) &&
            $steps < $limit
        ) {
            if (isset($seen[$sid])) {
                break;
            }
            $seen[$sid] = true;
            $offset = $sid * $mini_sector_size;
            $result .= substr($mini_stream, $offset, $mini_sector_size);
            $sid = $mini_fat[$sid];
            $steps++;
        }

        return $result;
    }

    private static function cfb_extract_workbook_stream($file_path) {
        $data = @file_get_contents($file_path);
        if ($data === false || strlen($data) < 512) {
            return new WP_Error('smtt_ramais_xls_read_error', 'Não foi possível ler o arquivo XLS.');
        }

        if (substr($data, 0, 8) !== "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") {
            return new WP_Error('smtt_ramais_xls_signature', 'O arquivo não é um XLS clássico válido.');
        }

        $sector_shift     = self::u16($data, 30);
        $mini_shift       = self::u16($data, 32);
        $sector_size      = 1 << $sector_shift;
        $mini_sector_size = 1 << $mini_shift;
        $num_fat          = self::u32($data, 44);
        $first_dir        = self::u32($data, 48);
        $mini_cutoff      = self::u32($data, 56);
        $first_mini_fat   = self::u32($data, 60);
        $num_mini_fat     = self::u32($data, 64);
        $first_difat      = self::u32($data, 68);
        $num_difat        = self::u32($data, 72);

        if ($sector_size < 512 || $sector_size > 4096 || $mini_sector_size < 32 || $mini_sector_size > 256) {
            return new WP_Error('smtt_ramais_xls_structure', 'Estrutura XLS não suportada.');
        }

        $difat = array();
        for ($i = 0; $i < 109; $i++) {
            $sid = self::u32($data, 76 + ($i * 4));
            if ($sid !== self::CFB_FREESECT) {
                $difat[] = $sid;
            }
        }

        // Arquivos maiores podem continuar a DIFAT em setores próprios.
        $difat_sid = $first_difat;
        for ($d = 0; $d < $num_difat && $difat_sid !== self::CFB_ENDOFCHAIN && $difat_sid !== self::CFB_FREESECT; $d++) {
            $sector = self::cfb_sector($data, $sector_size, $difat_sid);
            $entries_per_sector = ($sector_size / 4) - 1;
            for ($i = 0; $i < $entries_per_sector; $i++) {
                $sid = self::u32($sector, $i * 4);
                if ($sid !== self::CFB_FREESECT) {
                    $difat[] = $sid;
                }
            }
            $difat_sid = self::u32($sector, $entries_per_sector * 4);
        }

        $fat = array();
        $fat_sector_count = min($num_fat, count($difat));
        for ($i = 0; $i < $fat_sector_count; $i++) {
            $sector = self::cfb_sector($data, $sector_size, $difat[$i]);
            for ($j = 0; $j < $sector_size; $j += 4) {
                $fat[] = self::u32($sector, $j);
            }
        }

        if (empty($fat)) {
            return new WP_Error('smtt_ramais_xls_fat', 'Não foi possível ler a estrutura interna do XLS.');
        }

        $directory = self::cfb_read_chain($data, $fat, $sector_size, $first_dir);
        $entries = array();
        $root = null;
        $workbook = null;

        for ($offset = 0; $offset + 128 <= strlen($directory); $offset += 128) {
            $entry = substr($directory, $offset, 128);
            $name_len = self::u16($entry, 64);
            $type = ord($entry[66]);

            if ($type === 0 || $name_len < 2 || $name_len > 64) {
                continue;
            }

            $name = self::decode_utf16le(substr($entry, 0, $name_len - 2));
            $start = self::u32($entry, 116);
            // Para nossos arquivos o tamanho é muito menor que 4GB; usar low dword é suficiente e evita dependência de inteiro 64-bit no unpack.
            $size = self::u32($entry, 120);

            $item = array(
                'name'  => $name,
                'type'  => $type,
                'start' => $start,
                'size'  => $size,
            );
            $entries[] = $item;

            if ($type === 5) {
                $root = $item;
            }

            if ($type === 2 && ($name === 'Workbook' || $name === 'Book')) {
                $workbook = $item;
            }
        }

        if (!$workbook) {
            return new WP_Error('smtt_ramais_xls_workbook', 'Não foi encontrada uma planilha do Excel dentro do arquivo XLS.');
        }

        if ($workbook['size'] >= $mini_cutoff) {
            $stream = self::cfb_read_chain($data, $fat, $sector_size, $workbook['start']);
            return substr($stream, 0, $workbook['size']);
        }

        if (!$root || $num_mini_fat < 1) {
            return new WP_Error('smtt_ramais_xls_ministream', 'XLS usa mini-stream não suportado neste arquivo.');
        }

        $mini_fat_bytes = self::cfb_read_chain($data, $fat, $sector_size, $first_mini_fat);
        $mini_fat = array();
        for ($i = 0; $i + 4 <= strlen($mini_fat_bytes); $i += 4) {
            $mini_fat[] = self::u32($mini_fat_bytes, $i);
        }

        $root_stream = self::cfb_read_chain($data, $fat, $sector_size, $root['start']);
        $root_stream = substr($root_stream, 0, $root['size']);

        $stream = self::cfb_read_mini_chain($root_stream, $mini_fat, $mini_sector_size, $workbook['start']);
        return substr($stream, 0, $workbook['size']);
    }

    private static function biff_parse_sst($payload) {
        if (strlen($payload) < 8) {
            return array();
        }

        $unique = self::u32($payload, 4);
        $offset = 8;
        $strings = array();

        for ($i = 0; $i < $unique; $i++) {
            if ($offset + 3 > strlen($payload)) {
                break;
            }

            $cch = self::u16($payload, $offset);
            $offset += 2;
            $flags = ord($payload[$offset]);
            $offset++;

            $is_16bit = ($flags & 0x01) !== 0;
            $has_ext  = ($flags & 0x04) !== 0;
            $has_rich = ($flags & 0x08) !== 0;
            $rich_runs = 0;
            $ext_size = 0;

            if ($has_rich) {
                $rich_runs = self::u16($payload, $offset);
                $offset += 2;
            }

            if ($has_ext) {
                $ext_size = self::u32($payload, $offset);
                $offset += 4;
            }

            $byte_len = $cch * ($is_16bit ? 2 : 1);
            if ($offset + $byte_len > strlen($payload)) {
                break;
            }

            $raw = substr($payload, $offset, $byte_len);
            $offset += $byte_len;

            if ($is_16bit) {
                $text = self::decode_utf16le($raw);
            } else {
                if (function_exists('mb_convert_encoding')) {
                    $text = mb_convert_encoding($raw, 'UTF-8', 'Windows-1252');
                } elseif (function_exists('iconv')) {
                    $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $raw);
                    $text = $converted !== false ? $converted : $raw;
                } else {
                    $text = $raw;
                }
            }

            $strings[] = self::normalize_text($text);
            $offset += ($rich_runs * 4) + $ext_size;
        }

        return $strings;
    }

    private static function biff_decode_rk($rk) {
        $divide_100 = ($rk & 0x01) !== 0;
        $is_integer = ($rk & 0x02) !== 0;

        if ($is_integer) {
            // O valor ocupa 30 bits com sinal nos bits 2..31.
            $value = $rk >> 2;
            if ($value & 0x20000000) {
                $value -= 0x40000000;
            }
        } else {
            // Reconstrói o double usando os 30 bits superiores armazenados.
            $high = $rk & 0xFFFFFFFC;
            $packed = pack('V2', 0, $high);
            $decoded = unpack('dvalue', $packed);
            $value = isset($decoded['value']) ? $decoded['value'] : 0;
        }

        if ($divide_100) {
            $value /= 100;
        }

        return $value;
    }

    private static function biff_read_record($stream, $offset) {
        if ($offset + 4 > strlen($stream)) {
            return null;
        }

        $id = self::u16($stream, $offset);
        $length = self::u16($stream, $offset + 2);
        $next = $offset + 4 + $length;

        if ($next > strlen($stream)) {
            return null;
        }

        return array(
            'id'      => $id,
            'length'  => $length,
            'payload' => substr($stream, $offset + 4, $length),
            'next'    => $next,
        );
    }

    public static function parse_xls($file_path) {
        if (empty($file_path) || !file_exists($file_path)) {
            return new WP_Error('smtt_ramais_xls_not_found', 'Arquivo XLS não encontrado.');
        }

        $stream = self::cfb_extract_workbook_stream($file_path);
        if (is_wp_error($stream)) {
            return $stream;
        }

        $sst = array();
        $sheet_offset = null;
        $offset = 0;
        $seen_global_eof = false;

        while ($offset + 4 <= strlen($stream)) {
            $record = self::biff_read_record($stream, $offset);
            if (!$record) {
                break;
            }

            if ($record['id'] === 0x00FC) { // SST
                // O modelo fornecido é pequeno e traz o SST em um único registro.
                // Caso um arquivo futuro use CONTINUE complexo, retornamos erro claro em vez de importar dados errados.
                $sst_payload = $record['payload'];
                $next_offset = $record['next'];
                $next_record = self::biff_read_record($stream, $next_offset);
                if ($next_record && $next_record['id'] === 0x003C) {
                    return new WP_Error(
                        'smtt_ramais_xls_sst_continue',
                        'Este XLS possui uma tabela de textos muito grande. Salve-o novamente como XLS simples ou CSV antes de importar.'
                    );
                }
                $sst = self::biff_parse_sst($sst_payload);
            }

            if ($record['id'] === 0x0085 && $sheet_offset === null && $record['length'] >= 6) { // BOUNDSHEET
                $sheet_offset = self::u32($record['payload'], 0);
            }

            if ($record['id'] === 0x000A) {
                $seen_global_eof = true;
                if ($sheet_offset === null) {
                    $sheet_offset = $record['next'];
                }
                break;
            }

            $offset = $record['next'];
        }

        if (!$seen_global_eof || $sheet_offset === null || $sheet_offset >= strlen($stream)) {
            return new WP_Error('smtt_ramais_xls_sheet', 'Não foi possível localizar a aba da planilha XLS.');
        }

        $cells = array();
        $max_row = 0;
        $max_col = 0;
        $offset = $sheet_offset;

        while ($offset + 4 <= strlen($stream)) {
            $record = self::biff_read_record($stream, $offset);
            if (!$record) {
                break;
            }

            $id = $record['id'];
            $p = $record['payload'];

            if ($id === 0x000A) {
                break;
            }

            if ($id === 0x00FD && strlen($p) >= 10) { // LABELSST
                $row = self::u16($p, 0);
                $col = self::u16($p, 2);
                $sst_index = self::u32($p, 6);
                $value = isset($sst[$sst_index]) ? $sst[$sst_index] : '';
                $cells[$row][$col] = $value;
                $max_row = max($max_row, $row);
                $max_col = max($max_col, $col);
            } elseif ($id === 0x027E && strlen($p) >= 10) { // RK
                $row = self::u16($p, 0);
                $col = self::u16($p, 2);
                $rk = self::u32($p, 6);
                $value = self::biff_decode_rk($rk);
                if (is_float($value) && floor($value) == $value) {
                    $value = (int) $value;
                }
                $cells[$row][$col] = (string) $value;
                $max_row = max($max_row, $row);
                $max_col = max($max_col, $col);
            } elseif ($id === 0x0203 && strlen($p) >= 14) { // NUMBER
                $row = self::u16($p, 0);
                $col = self::u16($p, 2);
                $number_bytes = substr($p, 6, 8);
                $decoded = unpack('dvalue', $number_bytes);
                $value = isset($decoded['value']) ? $decoded['value'] : 0;
                if (is_float($value) && floor($value) == $value) {
                    $value = (int) $value;
                }
                $cells[$row][$col] = (string) $value;
                $max_row = max($max_row, $row);
                $max_col = max($max_col, $col);
            } elseif ($id === 0x00BD && strlen($p) >= 6) { // MULRK
                $row = self::u16($p, 0);
                $first_col = self::u16($p, 2);
                $last_col = self::u16($p, strlen($p) - 2);
                $cursor = 4;
                for ($col = $first_col; $col <= $last_col && $cursor + 6 <= strlen($p) - 2; $col++) {
                    $rk = self::u32($p, $cursor + 2);
                    $value = self::biff_decode_rk($rk);
                    if (is_float($value) && floor($value) == $value) {
                        $value = (int) $value;
                    }
                    $cells[$row][$col] = (string) $value;
                    $cursor += 6;
                    $max_row = max($max_row, $row);
                    $max_col = max($max_col, $col);
                }
            }

            $offset = $record['next'];
        }

        if (empty($cells)) {
            return new WP_Error('smtt_ramais_xls_no_cells', 'Nenhum dado foi encontrado na primeira aba do XLS.');
        }

        $matrix = array();
        for ($row = 0; $row <= $max_row; $row++) {
            $matrix_row = array();
            for ($col = 0; $col <= $max_col; $col++) {
                $matrix_row[] = isset($cells[$row][$col]) ? $cells[$row][$col] : '';
            }
            $matrix[] = $matrix_row;
        }

        return self::rows_from_matrix($matrix, 'xls');
    }

    private static function clear_public_cache() {
        delete_transient('smtt_ramais_public_ativos_v1');
        delete_transient('smtt_ramais_public_ativos_v2');
        delete_transient('smtt_ramais_public_payload_v3');
    }

    public static function import_uploaded_file($file) {
        if (empty($file) || !isset($file['tmp_name'])) {
            return new WP_Error('smtt_ramais_no_file', 'Nenhum arquivo foi enviado.');
        }

        if (!empty($file['error'])) {
            return new WP_Error('smtt_ramais_upload_error', 'Erro ao enviar o arquivo de ramais.');
        }

        $file_name = isset($file['name']) ? sanitize_file_name($file['name']) : 'ramais.csv';
        $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($extension, array('csv', 'xls'), true)) {
            return new WP_Error(
                'smtt_ramais_invalid_extension',
                'Envie um arquivo XLS (modelo SETOR/RAMAL) ou CSV.'
            );
        }

        $parsed = $extension === 'xls'
            ? self::parse_xls($file['tmp_name'])
            : self::parse_csv($file['tmp_name']);

        if (is_wp_error($parsed)) {
            return $parsed;
        }

        $rows = $parsed['rows'];
        $import_id = SMTT_Intranet_Ramais_Repository::create_import_log($file_name);

        $result = array(
            'total_linhas'      => count($rows),
            'total_inseridos'   => 0,
            'total_atualizados' => 0,
            'total_ignorados'   => 0,
            'total_erros'       => 0,
            'relatorio'         => array(),
            'formato'           => $extension,
        );

        foreach ($rows as $row) {
            $linha = isset($row['linha']) ? absint($row['linha']) : 0;

            if (empty($row['nome']) || empty($row['ramal'])) {
                $result['total_ignorados']++;
                $result['total_erros']++;
                $result['relatorio'][] = array(
                    'linha'    => $linha,
                    'nome'     => $row['nome'] ?? '',
                    'ramal'    => $row['ramal'] ?? '',
                    'status'   => 'erro',
                    'mensagem' => 'Setor/Nome e ramal são obrigatórios.',
                );
                continue;
            }

            if (!preg_match('/^[0-9A-Za-z._-]{2,20}$/', (string) $row['ramal'])) {
                $result['total_ignorados']++;
                $result['total_erros']++;
                $result['relatorio'][] = array(
                    'linha'    => $linha,
                    'nome'     => $row['nome'],
                    'ramal'    => $row['ramal'],
                    'status'   => 'erro',
                    'mensagem' => 'Formato de ramal inválido.',
                );
                continue;
            }

            if (!empty($row['email']) && !is_email($row['email'])) {
                $result['total_ignorados']++;
                $result['total_erros']++;
                $result['relatorio'][] = array(
                    'linha'    => $linha,
                    'nome'     => $row['nome'],
                    'ramal'    => $row['ramal'],
                    'status'   => 'erro',
                    'mensagem' => 'E-mail inválido.',
                );
                continue;
            }

            $existing = SMTT_Intranet_Ramais_Repository::get_by_ramal($row['ramal']);
            $saved = SMTT_Intranet_Ramais_Repository::upsert_from_import($row, $import_id);

            if (is_wp_error($saved)) {
                $result['total_ignorados']++;
                $result['total_erros']++;
                $result['relatorio'][] = array(
                    'linha'    => $linha,
                    'nome'     => $row['nome'],
                    'ramal'    => $row['ramal'],
                    'status'   => 'erro',
                    'mensagem' => $saved->get_error_message(),
                );
                continue;
            }

            if (!empty($existing)) {
                $result['total_atualizados']++;
                $result['relatorio'][] = array(
                    'linha'    => $linha,
                    'nome'     => $row['nome'],
                    'ramal'    => $row['ramal'],
                    'status'   => 'atualizado',
                    'mensagem' => 'Ramal atualizado com sucesso.',
                );
            } else {
                $result['total_inseridos']++;
                $result['relatorio'][] = array(
                    'linha'    => $linha,
                    'nome'     => $row['nome'],
                    'ramal'    => $row['ramal'],
                    'status'   => 'inserido',
                    'mensagem' => 'Ramal cadastrado com sucesso.',
                );
            }
        }

        SMTT_Intranet_Ramais_Repository::update_import_log($import_id, $result);
        self::clear_public_cache();

        $result['import_id'] = $import_id;
        return $result;
    }
}
