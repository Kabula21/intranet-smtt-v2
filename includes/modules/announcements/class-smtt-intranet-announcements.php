<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Announcements {

    const OPTION_SITE_URL       = 'smtt_announcements_site_url';
    const TRANSIENT_KEY         = 'smtt_announcements_remote_posts_v63'; // legado/migração.
    const REST_BASE_CACHE       = 'smtt_announcements_rest_bases'; // compatibilidade com versões anteriores.
    const LAST_GOOD_KEY         = 'smtt_announcements_last_good'; // legado/migração.
    const SNAPSHOT_OPTION       = 'smtt_announcements_snapshot_v65';
    const REFRESH_META_OPTION   = 'smtt_announcements_refresh_meta_v65';
    const REFRESH_LOCK_OPTION   = 'smtt_announcements_refresh_lock_v65';
    const CRON_HOOK             = 'smtt_intranet_refresh_news_background';
    const CRON_NOW_HOOK         = 'smtt_intranet_refresh_news_background_now';
    const CRON_IMAGES_HOOK      = 'smtt_intranet_refresh_news_image_background'; // legado v2.0.26; mantido só para limpar eventos antigos.
    const IMAGE_JOBS_DISABLED_OPTION = 'smtt_announcements_image_jobs_disabled_v67';
    const SYNC_REVISION_OPTION  = 'smtt_announcements_sync_revision_v72';
    const SYNC_REVISION         = 'rest-smt-noticia-article-details-v1';
    const CRON_SCHEDULE         = 'smtt_every_15_minutes';
    const DEFAULT_SITE_URL      = 'https://smtt.aracaju.se.gov.br';

    public function register() {
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('cron_schedules', array($this, 'register_cron_schedule'));
        add_action(self::CRON_HOOK, array($this, 'refresh_news_background'));
        add_action(self::CRON_NOW_HOOK, array($this, 'refresh_news_background'));
        add_action(self::CRON_IMAGES_HOOK, array($this, 'refresh_single_news_image'), 10, 2);
        add_action('init', array($this, 'ensure_refresh_schedule'));
        add_action('update_option_' . self::OPTION_SITE_URL, array($this, 'handle_source_update'), 10, 3);
    }

    public function register_cron_schedule($schedules) {
        if (!isset($schedules[self::CRON_SCHEDULE])) {
            $schedules[self::CRON_SCHEDULE] = array(
                'interval' => 15 * MINUTE_IN_SECONDS,
                'display'  => __('A cada 15 minutos (SMTT Notícias)', 'smtt-intranet'),
            );
        }

        return $schedules;
    }

    public function ensure_refresh_schedule() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 60, self::CRON_SCHEDULE, self::CRON_HOOK);
        }

        // A partir da v2.0.27 as imagens não entram mais na fila do servidor.
        // Elas usam a URL remota diretamente (com fallback para cache local antigo),
        // eliminando jobs lentos e evitando manter processos PHP presos ao portal.
        if (!get_option(self::IMAGE_JOBS_DISABLED_OPTION, false)) {
            wp_clear_scheduled_hook(self::CRON_IMAGES_HOOK);
            add_option(self::IMAGE_JOBS_DISABLED_OPTION, 1, '', false);
        }

        // Em atualização do plugin pode ainda não existir o snapshot persistente.
        // Agenda uma carga imediata, mas nunca faz HTTP durante a abertura da Intranet.
        if (!$this->get_snapshot_raw() && !wp_next_scheduled(self::CRON_NOW_HOOK)) {
            wp_schedule_single_event(time() + 5, self::CRON_NOW_HOOK);
        }

        // v2.0.32: força uma única nova sincronização ao instalar esta correção.
        // O snapshot antigo continua visível até a nova coleta terminar, evitando
        // qualquer tela vazia durante a atualização do plugin.
        $sync_revision = (string) get_option(self::SYNC_REVISION_OPTION, '');
        if ($sync_revision !== self::SYNC_REVISION) {
            update_option(self::SYNC_REVISION_OPTION, self::SYNC_REVISION, false);
            delete_option(self::REFRESH_LOCK_OPTION);
            $this->schedule_background_refresh(1);

            if (function_exists('spawn_cron')) {
                spawn_cron(time());
            }
        }
    }

    public function handle_source_update($old_value = '', $new_value = '', $option = '') {
        $this->clear_cache();
        $this->schedule_background_refresh(3);
    }

    public function register_settings() {
        register_setting(
            'smtt_announcements_settings',
            self::OPTION_SITE_URL,
            array(
                'type'              => 'string',
                'sanitize_callback' => array($this, 'sanitize_site_url'),
                'default'           => self::DEFAULT_SITE_URL,
            )
        );
    }

    public function sanitize_site_url($value) {
        $value = trim((string) $value);

        if ($value === '') {
            return self::DEFAULT_SITE_URL;
        }

        // Facilita a configuração: aceita também "smtt.aracaju.se.gov.br".
        if (!preg_match('#^https?://#i', $value)) {
            $value = 'https://' . ltrim($value, '/');
        }

        $value = $this->normalize_external_url($value);

        if (!$value || !$this->is_allowed_external_url($value)) {
            return self::DEFAULT_SITE_URL;
        }

        return untrailingslashit($value);
    }

    public function get_site_url() {
        $value = trim((string) get_option(self::OPTION_SITE_URL, self::DEFAULT_SITE_URL));

        if ($value === '') {
            $value = self::DEFAULT_SITE_URL;
        }

        if (!preg_match('#^https?://#i', $value)) {
            $value = 'https://' . ltrim($value, '/');
        }

        $value = $this->normalize_external_url($value);

        if (!$value || !$this->is_allowed_external_url($value)) {
            return self::DEFAULT_SITE_URL;
        }

        return untrailingslashit($value);
    }

    public function clear_cache($unused = null) {
        delete_transient(self::TRANSIENT_KEY);
        delete_transient(self::REST_BASE_CACHE);
        // O snapshot persistente NÃO é apagado. Assim a Home nunca fica dependente
        // do portal externo e continua mostrando a última versão válida.
    }

    /**
     * Retorna a melhor URL disponível para a foto da notícia sem fazer HTTP no PHP.
     *
     * Prioriza um arquivo local já existente de versões anteriores. Se não houver,
     * devolve a própria URL remota para o navegador carregar de forma independente.
     * Assim a Home e o WP-Cron nunca esperam o download binário de uma foto.
     */
    public function get_news_image_url($remote_url) {
        $remote_url = $this->normalize_external_url($remote_url);

        if ($remote_url === '' || !$this->is_allowed_external_url($remote_url)) {
            return '';
        }

        $local = $this->find_cached_news_image($remote_url);
        return $local !== '' ? $local : esc_url_raw($remote_url);
    }

    private function get_news_image_cache_context($remote_url) {
        $uploads = wp_upload_dir();

        if (!empty($uploads['error']) || empty($uploads['basedir']) || empty($uploads['baseurl'])) {
            return array();
        }

        $hash = hash('sha256', $remote_url);

        return array(
            'dir'  => trailingslashit($uploads['basedir']) . 'smtt-intranet/news-static',
            'url'  => trailingslashit($uploads['baseurl']) . 'smtt-intranet/news-static',
            'hash' => $hash,
        );
    }

    private function find_cached_news_image($remote_url) {
        $context = $this->get_news_image_cache_context($remote_url);
        if (empty($context)) {
            return '';
        }

        foreach (array('jpg', 'jpeg', 'png', 'webp', 'gif', 'avif') as $extension) {
            $path = trailingslashit($context['dir']) . $context['hash'] . '.' . $extension;
            if (is_file($path) && filesize($path) > 128) {
                return esc_url_raw(trailingslashit($context['url']) . basename($path));
            }
        }

        return '';
    }

    private function cache_remote_news_image($remote_url) {
        $context = $this->get_news_image_cache_context($remote_url);
        if (empty($context) || !wp_mkdir_p($context['dir'])) {
            return '';
        }

        $temp_file = wp_tempnam($remote_url);
        if (!$temp_file) {
            return '';
        }

        $response = wp_remote_get($remote_url, array(
            'timeout'             => 20,
            'redirection'         => 4,
            'sslverify'           => false,
            'reject_unsafe_urls'  => false,
            'stream'              => true,
            'filename'            => $temp_file,
            'limit_response_size' => 12 * 1024 * 1024,
            'headers'             => array(
                'Accept'          => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                'Accept-Language' => 'pt-BR,pt;q=0.9,en;q=0.6',
                // Referer do próprio portal ajuda servidores que bloqueiam downloads
                // sem origem, sem criar hotlink no navegador do colaborador.
                'Referer'         => trailingslashit($this->get_base_site_url($this->get_site_url())),
                'User-Agent'      => 'Mozilla/5.0 (compatible; SMTT-Intranet-News/' . (defined('SMTT_INTRANET_VERSION') ? SMTT_INTRANET_VERSION : '1.0.0') . ')',
            ),
        ));

        if (is_wp_error($response)) {
            @unlink($temp_file);
            return '';
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $size = is_file($temp_file) ? (int) filesize($temp_file) : 0;
        if ($code < 200 || $code >= 300 || $size < 128 || $size > (12 * 1024 * 1024)) {
            @unlink($temp_file);
            return '';
        }

        $mime = '';
        if (function_exists('wp_get_image_mime')) {
            $mime = (string) wp_get_image_mime($temp_file);
        }
        if ($mime === '' && function_exists('mime_content_type')) {
            $detected = @mime_content_type($temp_file);
            $mime = is_string($detected) ? $detected : '';
        }

        $extensions = array(
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            'image/avif' => 'avif',
        );

        if (!isset($extensions[$mime])) {
            @unlink($temp_file);
            return '';
        }

        // Rejeita pixels/placeholder minúsculos usados por lazy-load. Se isso
        // acontecer, o job de imagem tenta a foto da página individual.
        $dimensions = @getimagesize($temp_file);
        if (is_array($dimensions) && isset($dimensions[0], $dimensions[1])) {
            $width  = (int) $dimensions[0];
            $height = (int) $dimensions[1];
            if ($width < 120 || $height < 70 || ($width * $height) < 12000) {
                @unlink($temp_file);
                return '';
            }
        }

        $filename = $context['hash'] . '.' . $extensions[$mime];
        $target   = trailingslashit($context['dir']) . $filename;

        if (!@rename($temp_file, $target)) {
            if (!@copy($temp_file, $target)) {
                @unlink($temp_file);
                return '';
            }
            @unlink($temp_file);
        }

        @chmod($target, 0644);
        delete_transient('smtt_news_img_fail_' . md5($remote_url));

        return esc_url_raw(trailingslashit($context['url']) . $filename);
    }

    public function get_latest_posts($limit = 3, $force_refresh = false) {
        $limit = max(1, min(12, (int) $limit));

        // O force_refresh só é usado por telas administrativas. A Home chama sempre
        // com false e, portanto, nunca espera o portal institucional responder.
        if ($force_refresh) {
            return $this->refresh_now($limit);
        }

        $snapshot = $this->get_snapshot();

        if (is_array($snapshot) && !empty($snapshot['success']) && !empty($snapshot['items'])) {
            $snapshot['items'] = array_slice($snapshot['items'], 0, $limit);

            $updated_at = !empty($snapshot['updated_at']) ? (int) $snapshot['updated_at'] : 0;
            if ($updated_at <= 0 || (time() - $updated_at) > (20 * MINUTE_IN_SECONDS)) {
                $this->schedule_background_refresh(3);
            }

            return $snapshot;
        }

        // Primeira carga após instalar/atualizar: agenda o preenchimento e devolve
        // imediatamente. Nenhuma conexão externa entra no tempo de resposta da Home.
        $this->schedule_background_refresh(3);

        return array(
            'success' => false,
            'source'  => 'local',
            'items'   => array(),
            'message' => __('As notícias estão sendo sincronizadas em segundo plano.', 'smtt-intranet'),
        );
    }

    /**
     * Atualização síncrona destinada ao WP-Cron e à ação administrativa manual.
     * Nunca é executada no caminho normal de renderização da Intranet.
     */
    public function refresh_now($limit = 3) {
        $limit = max(1, min(12, (int) $limit));

        if (!$this->acquire_refresh_lock()) {
            $snapshot = $this->get_snapshot();
            if (is_array($snapshot) && !empty($snapshot['items'])) {
                $snapshot['refresh_skipped'] = true;
                $snapshot['message'] = __('Já existe uma atualização de notícias em andamento.', 'smtt-intranet');
                return $snapshot;
            }

            return array(
                'success' => false,
                'source'  => 'local',
                'items'   => array(),
                'message' => __('Já existe uma atualização de notícias em andamento.', 'smtt-intranet'),
            );
        }

        $started_at = time();

        try {
            $fresh = $this->fetch_fresh_result($limit);

            if (!empty($fresh['success']) && !empty($fresh['items'])) {
                // Completa cada item com o subtítulo real da página individual e,
                // quando necessário, com a imagem principal. Isso roda somente no
                // WP-Cron/atualização administrativa e nunca na abertura da Home.
                $fresh = $this->enrich_news_items_from_articles($fresh);

                // Preserva apenas cache local já existente; a imagem remota correta
                // vem do REST do próprio WordPress ou da página individual.
                $fresh = $this->reuse_existing_news_images($fresh);
                $fresh['updated_at'] = time();
                $fresh['source_url'] = $this->get_site_url();
                $fresh['refreshed']  = true;
                $fresh['images_pending'] = false;

                // A sincronização termina aqui. Imagens não geram mais downloads/jobs
                // no servidor; o front usa a URL remota diretamente e mantém qualquer
                // cache local válido que já exista.
                $this->store_snapshot($fresh);
                $this->store_refresh_meta(array(
                    'last_attempt' => $started_at,
                    'last_success' => time(),
                    'last_error'   => '',
                    'source'       => !empty($fresh['source']) ? $fresh['source'] : '',
                ));

                return $fresh;
            }

            $error_message = !empty($fresh['message'])
                ? (string) $fresh['message']
                : __('Falha ao consultar o portal institucional.', 'smtt-intranet');

            $this->store_refresh_meta(array(
                'last_attempt' => $started_at,
                'last_success' => $this->get_last_success_timestamp(),
                'last_error'   => $error_message,
                'source'       => '',
            ));

            $snapshot = $this->get_snapshot();
            if (is_array($snapshot) && !empty($snapshot['items'])) {
                $snapshot['refreshed'] = false;
                $snapshot['refresh_error'] = $error_message;
                $snapshot['message'] = __('O portal não respondeu agora; a última atualização válida foi mantida.', 'smtt-intranet');
                return $snapshot;
            }

            return $fresh;
        } finally {
            $this->release_refresh_lock();
        }
    }

    public function refresh_news_background() {
        $this->refresh_now(3);
    }

    /**
     * Solicita uma sincronização sem fazer o administrador esperar pelo portal externo.
     * O evento roda fora da requisição atual e mantém a Home sempre desacoplada da internet.
     */
    public function request_refresh_now($delay = 1) {
        $this->schedule_background_refresh(max(1, (int) $delay));

        // Tenta disparar o WP-Cron imediatamente, sem bloquear a tela do administrador.
        if (function_exists('spawn_cron')) {
            spawn_cron(time());
        }

        return true;
    }

    public function get_refresh_status() {
        $meta = get_option(self::REFRESH_META_OPTION, array());
        return is_array($meta) ? $meta : array();
    }

    private function fetch_fresh_result($limit) {
        $site_url = $this->get_site_url();
        $messages = array();

        // Fonte preferencial: REST nativo do WordPress. O plugin institucional já
        // publica o CPT smt_noticia com show_in_rest=true, então não é necessário
        // alterar o plugin de Notícias nem depender do HTML visual da listagem.
        $rest_result = $this->fetch_from_rest($site_url, $limit);
        if (!empty($rest_result['success']) && !empty($rest_result['items'])) {
            return $rest_result;
        }
        if (!empty($rest_result['message'])) {
            $messages[] = 'REST: ' . $rest_result['message'];
        }

        // Fallback 1: página pública de Notícias.
        $html_result = $this->fetch_from_html($site_url, $limit);
        if (!empty($html_result['success']) && !empty($html_result['items'])) {
            return $html_result;
        }
        if (!empty($html_result['message'])) {
            $messages[] = 'PORTAL: ' . $html_result['message'];
        }

        // Fallback 2: RSS direto.
        $rss_result = $this->fetch_from_rss($site_url, $limit);
        if (!empty($rss_result['success']) && !empty($rss_result['items'])) {
            return $rss_result;
        }
        if (!empty($rss_result['message'])) {
            $messages[] = 'RSS: ' . $rss_result['message'];
        }

        return array(
            'success' => false,
            'source'  => '',
            'items'   => array(),
            'message' => sprintf(
                __('Não foi possível atualizar as notícias. %s', 'smtt-intranet'),
                implode(' | ', array_filter($messages))
            ),
        );
    }

    private function result_has_timeout($result) {
        if (!is_array($result) || empty($result['message'])) {
            return false;
        }

        $message = strtolower((string) $result['message']);
        return strpos($message, 'timed out') !== false
            || strpos($message, 'timeout') !== false
            || strpos($message, 'curl error 28') !== false;
    }

    /**
     * Reaproveita fotos locais do snapshot anterior sem acessar a internet.
     */
    private function reuse_existing_news_images($result) {
        $previous = $this->get_snapshot_raw();
        $previous_by_link = array();

        if (is_array($previous) && !empty($previous['items']) && is_array($previous['items'])) {
            foreach ($previous['items'] as $old_item) {
                if (!empty($old_item['link'])) {
                    $previous_by_link[(string) $old_item['link']] = $old_item;
                }
            }
        }

        $items = array();
        foreach ((array) $result['items'] as $item) {
            $link = !empty($item['link']) ? esc_url_raw($item['link']) : '';
            $item['image_local'] = '';

            if ($link !== '' && isset($previous_by_link[$link])) {
                if (!empty($previous_by_link[$link]['image_local'])) {
                    $item['image_local'] = esc_url_raw($previous_by_link[$link]['image_local']);
                }
                if (empty($item['image']) && !empty($previous_by_link[$link]['image'])) {
                    $item['image'] = esc_url_raw($previous_by_link[$link]['image']);
                }
            }

            $items[] = $item;
        }

        $result['items'] = $items;
        return $result;
    }

    /**
     * Agenda uma tarefa pequena por notícia. Assim as imagens não formam uma fila longa
     * dentro de uma única execução do PHP quando o portal público está lento.
     */
    private function schedule_news_image_refreshes($items) {
        $offset = 2;
        foreach ((array) $items as $item) {
            $link = !empty($item['link']) ? esc_url_raw($item['link']) : '';
            if ($link === '') {
                continue;
            }

            $args = array($link, 1);
            if (!wp_next_scheduled(self::CRON_IMAGES_HOOK, $args)) {
                wp_schedule_single_event(time() + $offset, self::CRON_IMAGES_HOOK, $args);
            }
            $offset += 4;
        }

        if (function_exists('spawn_cron')) {
            spawn_cron(time());
        }
    }

    /**
     * Hidrata a foto de uma única notícia. É executado apenas em background.
     */
    public function refresh_single_news_image($article_link, $attempt = 1) {
        // Legado da v2.0.26. Eventos antigos podem ainda existir no cron até a
        // primeira inicialização da v2.0.27; não fazemos mais qualquer HTTP aqui.
        return;
    }

    /**
     * Completa subtítulo e imagem a partir da página individual da notícia.
     *
     * O subtítulo do plugin institucional é um post_meta privado e, por isso,
     * não aparece no REST nativo. Entretanto ele já é renderizado publicamente
     * como .smt-single-subtitle. A coleta é feita apenas durante a sincronização.
     */
    private function enrich_news_items_from_articles($result) {
        if (!is_array($result) || empty($result['items']) || !is_array($result['items'])) {
            return $result;
        }

        $items = array();

        foreach ($result['items'] as $item) {
            $item = is_array($item) ? $item : array();
            $item['subtitle'] = !empty($item['subtitle']) ? $this->clean_text($item['subtitle']) : '';
            $link = !empty($item['link']) ? esc_url_raw($item['link']) : '';

            if ($link !== '') {
                $details = $this->fetch_article_details($link);

                if ($item['subtitle'] === '' && !empty($details['subtitle'])) {
                    $item['subtitle'] = $details['subtitle'];
                }

                if (empty($item['image']) && !empty($details['image'])) {
                    $item['image'] = $details['image'];
                }
            }

            $items[] = $item;
        }

        $result['items'] = $items;
        return $result;
    }

    /**
     * Lê somente os dois dados que não são garantidos pelo endpoint REST:
     * subtítulo público e imagem principal de fallback.
     */
    private function fetch_article_details($article_url) {
        $details = array(
            'subtitle' => '',
            'image'    => '',
        );

        $article_url = $this->normalize_external_url($article_url);
        if ($article_url === '' || !$this->is_allowed_external_url($article_url)) {
            return $details;
        }

        $response = $this->remote_get($article_url, 12, 'text/html,application/xhtml+xml');
        if (is_wp_error($response)) {
            return $details;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $html = (string) wp_remote_retrieve_body($response);
        if ($code < 200 || $code >= 300 || trim($html) === '') {
            return $details;
        }

        if (class_exists('DOMDocument')) {
            $previous = libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            if ($loaded) {
                $xpath = new DOMXPath($dom);

                $subtitle_nodes = $xpath->query(
                    '//*[contains(concat(" ", normalize-space(@class), " "), " smt-single-subtitle ")]'
                );
                if ($subtitle_nodes && $subtitle_nodes->length) {
                    $details['subtitle'] = $this->clean_text($subtitle_nodes->item(0)->textContent);
                }

                // O template do SMT Notícias envolve a imagem principal em
                // .smt-single-cover. Preferimos essa imagem ao logo/ícones da página.
                $cover_images = $xpath->query(
                    '//*[contains(concat(" ", normalize-space(@class), " "), " smt-single-cover ")]//img'
                );
                if ($cover_images && $cover_images->length) {
                    foreach ($cover_images as $img) {
                        if (!($img instanceof DOMElement)) {
                            continue;
                        }

                        $tag = $dom->saveHTML($img);
                        $candidate = $this->extract_image_url_from_tag($tag, $article_url);
                        if ($candidate !== '') {
                            $details['image'] = esc_url_raw($candidate);
                            break;
                        }
                    }
                }
            }
        }

        // Fallback para servidores sem DOMDocument ou pequenas alterações no template.
        if ($details['subtitle'] === '' && preg_match(
            '#<[^>]+class=["\'][^"\']*smt-single-subtitle[^"\']*["\'][^>]*>(.*?)</[^>]+>#is',
            $html,
            $subtitle_match
        )) {
            $details['subtitle'] = $this->clean_text($subtitle_match[1]);
        }

        if ($details['image'] === '') {
            $patterns = array(
                '#<meta[^>]+property=["\']og:image(?::secure_url)?["\'][^>]+content=["\']([^"\']+)["\']#i',
                '#<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image(?::secure_url)?["\']#i',
                '#<meta[^>]+name=["\']twitter:image(?::src)?["\'][^>]+content=["\']([^"\']+)["\']#i',
                '#<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']twitter:image(?::src)?["\']#i',
            );

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $html, $match)) {
                    $candidate = $this->normalize_remote_url($match[1], $article_url);
                    if ($candidate !== '' && $this->is_allowed_external_url($candidate)) {
                        $details['image'] = esc_url_raw($candidate);
                        break;
                    }
                }
            }
        }

        return $details;
    }

    private function prepare_news_images($result) {
        $previous = $this->get_snapshot_raw();
        $previous_by_link = array();

        if (is_array($previous) && !empty($previous['items']) && is_array($previous['items'])) {
            foreach ($previous['items'] as $old_item) {
                if (!empty($old_item['link'])) {
                    $previous_by_link[(string) $old_item['link']] = $old_item;
                }
            }
        }

        $keep_local_urls = array();
        $prepared = array();

        foreach ((array) $result['items'] as $item) {
            $link = !empty($item['link']) ? esc_url_raw($item['link']) : '';
            $remote_image = !empty($item['image']) ? $this->normalize_external_url($item['image']) : '';
            $local_image = '';

            if ($remote_image !== '' && $this->is_allowed_external_url($remote_image)) {
                $local_image = $this->cache_remote_news_image($remote_image);
            }

            // Alguns temas usam placeholders/lazy-load na listagem. Se a imagem não
            // veio ou não pôde ser baixada, consulta a página individual e procura
            // og:image/twitter:image/primeira imagem real. Isso ocorre só no background.
            if ($local_image === '' && $link !== '') {
                $article_image = $this->fetch_article_image($link);
                if ($article_image !== '') {
                    $remote_image = $article_image;
                    $local_image = $this->cache_remote_news_image($remote_image);
                }
            }

            // Se a notícia permanece a mesma e o portal oscila durante a atualização,
            // conserva a foto local que já estava válida.
            if ($local_image === '' && $link !== '' && isset($previous_by_link[$link])) {
                $old = $previous_by_link[$link];
                if (!empty($old['image_local'])) {
                    $local_image = esc_url_raw($old['image_local']);
                }
                if ($remote_image === '' && !empty($old['image'])) {
                    $remote_image = esc_url_raw($old['image']);
                }
            }

            $item['image'] = $remote_image !== '' ? esc_url_raw($remote_image) : '';
            $item['image_local'] = $local_image !== '' ? esc_url_raw($local_image) : '';

            if ($item['image_local'] !== '') {
                $keep_local_urls[] = $item['image_local'];
            }

            $prepared[] = $item;
        }

        $result['items'] = $prepared;
        $this->cleanup_news_image_cache($keep_local_urls);

        return $result;
    }

    private function fetch_article_image($article_url) {
        $details = $this->fetch_article_details($article_url);
        return !empty($details['image']) ? esc_url_raw($details['image']) : '';
    }

    private function extract_image_url_from_tag($tag, $base_url) {
        foreach (array('data-src', 'data-lazy-src', 'data-original', 'src') as $attr) {
            if (preg_match('#\\s' . preg_quote($attr, '#') . '=["\']([^"\']+)["\']#i', $tag, $match)) {
                $candidate = trim(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($this->is_real_news_image_candidate($candidate)) {
                    return $this->normalize_remote_url($candidate, $base_url);
                }
            }
        }

        foreach (array('data-srcset', 'data-lazy-srcset', 'srcset') as $attr) {
            if (preg_match('#\\s' . preg_quote($attr, '#') . '=["\']([^"\']+)["\']#i', $tag, $match)) {
                $parts = array_map('trim', explode(',', html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                for ($i = count($parts) - 1; $i >= 0; $i--) {
                    $url = preg_replace('/\\s+\\d+(?:w|x)$/i', '', $parts[$i]);
                    if ($this->is_real_news_image_candidate($url)) {
                        return $this->normalize_remote_url($url, $base_url);
                    }
                }
            }
        }

        return '';
    }

    private function is_real_news_image_candidate($url) {
        $url = trim((string) $url);
        if ($url === '' || stripos($url, 'data:image') === 0) {
            return false;
        }

        $lower = strtolower($url);
        foreach (array('placeholder', 'spacer.gif', 'blank.gif', 'lazy-placeholder', 'loading.svg') as $needle) {
            if (strpos($lower, $needle) !== false) {
                return false;
            }
        }

        return true;
    }

    private function cleanup_news_image_cache($keep_urls) {
        $uploads = wp_upload_dir();
        if (!empty($uploads['error']) || empty($uploads['basedir'])) {
            return;
        }

        $dir = trailingslashit($uploads['basedir']) . 'smtt-intranet/news-static';
        if (!is_dir($dir)) {
            return;
        }

        $keep = array();
        foreach ((array) $keep_urls as $url) {
            $path = (string) wp_parse_url($url, PHP_URL_PATH);
            $name = $path !== '' ? basename($path) : '';
            if ($name !== '') {
                $keep[$name] = true;
            }
        }

        foreach ((array) glob(trailingslashit($dir) . '*') as $file) {
            if (!is_file($file)) {
                continue;
            }
            if (!isset($keep[basename($file)])) {
                // Mantém uma pequena janela de segurança para páginas que já foram
                // entregues ao navegador enquanto uma sincronização troca o snapshot.
                $modified = (int) @filemtime($file);
                if ($modified <= 0 || (time() - $modified) > (2 * DAY_IN_SECONDS)) {
                    @unlink($file);
                }
            }
        }
    }

    private function get_snapshot() {
        $snapshot = $this->get_snapshot_raw();
        if (is_array($snapshot) && !empty($snapshot['success']) && !empty($snapshot['items'])) {
            return $snapshot;
        }

        // Migração suave das versões anteriores: reaproveita o último cache existente
        // sem acessar a internet e deixa o cron substituir por um snapshot novo.
        foreach (array(self::TRANSIENT_KEY, self::LAST_GOOD_KEY) as $legacy_key) {
            $legacy = get_transient($legacy_key);
            if (is_array($legacy) && !empty($legacy['success']) && !empty($legacy['items'])) {
                $legacy['updated_at'] = 0;
                $legacy['source_url'] = $this->get_site_url();
                $legacy['migrated_legacy_cache'] = true;
                $this->store_snapshot($legacy);
                return $legacy;
            }
        }

        return array();
    }

    private function get_snapshot_raw() {
        $snapshot = get_option(self::SNAPSHOT_OPTION, array());
        return is_array($snapshot) ? $snapshot : array();
    }

    private function store_snapshot($result) {
        if (!is_array($result) || empty($result['success']) || empty($result['items'])) {
            return false;
        }

        if (get_option(self::SNAPSHOT_OPTION, null) === null) {
            add_option(self::SNAPSHOT_OPTION, $result, '', 'no');
        } else {
            update_option(self::SNAPSHOT_OPTION, $result, false);
        }

        // Mantém chaves antigas preenchidas por compatibilidade com eventuais hooks
        // de versões anteriores, mas a Home lê prioritariamente o snapshot persistente.
        set_transient(self::TRANSIENT_KEY, $result, 30 * MINUTE_IN_SECONDS);
        set_transient(self::LAST_GOOD_KEY, $result, 7 * DAY_IN_SECONDS);

        return true;
    }

    private function schedule_background_refresh($delay = 5) {
        if (wp_next_scheduled(self::CRON_NOW_HOOK)) {
            return;
        }

        wp_schedule_single_event(time() + max(1, (int) $delay), self::CRON_NOW_HOOK);
    }

    private function acquire_refresh_lock() {
        $now = time();
        $existing = (int) get_option(self::REFRESH_LOCK_OPTION, 0);

        if ($existing > 0 && ($now - $existing) < (10 * MINUTE_IN_SECONDS)) {
            return false;
        }

        if ($existing > 0) {
            delete_option(self::REFRESH_LOCK_OPTION);
        }

        return (bool) add_option(self::REFRESH_LOCK_OPTION, $now, '', 'no');
    }

    private function release_refresh_lock() {
        delete_option(self::REFRESH_LOCK_OPTION);
    }

    private function store_refresh_meta($meta) {
        $current = get_option(self::REFRESH_META_OPTION, array());
        $current = is_array($current) ? $current : array();
        $meta = array_merge($current, (array) $meta);

        if (get_option(self::REFRESH_META_OPTION, null) === null) {
            add_option(self::REFRESH_META_OPTION, $meta, '', 'no');
        } else {
            update_option(self::REFRESH_META_OPTION, $meta, false);
        }
    }

    private function get_last_success_timestamp() {
        $meta = get_option(self::REFRESH_META_OPTION, array());
        return is_array($meta) && !empty($meta['last_success']) ? (int) $meta['last_success'] : 0;
    }

    /**
     * Retorna a base do portal, removendo caminhos de configuração comuns.
     */
    private function get_base_site_url($site_url) {
        $site_url = trim((string) $site_url);
        if ($site_url === '') {
            return self::DEFAULT_SITE_URL;
        }

        $parts = wp_parse_url($site_url);
        if (empty($parts['scheme']) || empty($parts['host'])) {
            return self::DEFAULT_SITE_URL;
        }

        $base = $parts['scheme'] . '://' . $parts['host'];
        if (!empty($parts['port'])) {
            $base .= ':' . absint($parts['port']);
        }

        $path = !empty($parts['path']) ? '/' . ltrim($parts['path'], '/') : '';
        $path = preg_replace('#/(noticias|wp-json|feed)(/.*)?$#i', '', $path);

        if ($path && $path !== '/') {
            $base .= '/' . trim($path, '/');
        }

        return untrailingslashit($base);
    }

    private function get_news_listing_url($site_url) {
        $parts = wp_parse_url($site_url);
        $path  = !empty($parts['path']) ? $parts['path'] : '';

        if (preg_match('#/noticias(?:/|$)#i', $path)) {
            $base = $parts['scheme'] . '://' . $parts['host'];
            if (!empty($parts['port'])) {
                $base .= ':' . absint($parts['port']);
            }

            $noticias_path = preg_replace('#(/noticias)(/.*)?$#i', '$1', $path);
            return trailingslashit($base . '/' . ltrim($noticias_path, '/'));
        }

        return trailingslashit($this->get_base_site_url($site_url)) . 'noticias/';
    }

    /**
     * Normaliza uma URL externa sem depender de wp_http_validate_url().
     *
     * Em alguns ambientes locais/intranets, filtros de segurança do WordPress ou
     * da hospedagem podem fazer wp_http_validate_url() rejeitar até um domínio
     * público válido. Aqui validamos apenas a estrutura e a segurança básica da
     * URL e deixamos a conexão para a HTTP API do WordPress.
     */
    private function normalize_external_url($url) {
        $url = trim((string) $url);

        if ($url === '') {
            return '';
        }

        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }

        return esc_url_raw($url, array('http', 'https'));
    }

    /**
     * Validação local e determinística para a origem externa das notícias.
     * Não faz resolução DNS e não depende de filtros globais do WordPress.
     */
    private function is_allowed_external_url($url) {
        $url = $this->normalize_external_url($url);

        if ($url === '') {
            return false;
        }

        $parts = wp_parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        $scheme = strtolower((string) $parts['scheme']);
        if (!in_array($scheme, array('http', 'https'), true)) {
            return false;
        }

        // Nunca aceitar credenciais embutidas em URL remota.
        if (!empty($parts['user']) || !empty($parts['pass'])) {
            return false;
        }

        if (!empty($parts['port'])) {
            $port = (int) $parts['port'];
            if (!in_array($port, array(80, 443), true)) {
                return false;
            }
        }

        $host = strtolower(rtrim((string) $parts['host'], '.'));
        if ($host === '' || $host === 'localhost') {
            return false;
        }

        // Bloqueia nomes tipicamente locais/internos.
        foreach (array('.localhost', '.local', '.internal', '.invalid', '.test') as $suffix) {
            if (substr($host, -strlen($suffix)) === $suffix) {
                return false;
            }
        }

        // Se for IP literal, exige IP público (nem privado nem reservado).
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return (bool) filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );
        }

        // Hostname público: labels DNS válidos e pelo menos um ponto.
        if (strpos($host, '.') === false || strlen($host) > 253) {
            return false;
        }

        $labels = explode('.', $host);
        foreach ($labels as $label) {
            if ($label === '' || strlen($label) > 63 || !preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/i', $label)) {
                return false;
            }
        }

        return true;
    }

    private function remote_get($url, $timeout, $accept = '*/*') {
        $url = $this->normalize_external_url($url);

        if (!$url || !$this->is_allowed_external_url($url)) {
            return new WP_Error('smtt_invalid_remote_url', __('URL externa inválida.', 'smtt-intranet'));
        }

        return wp_remote_get($url, array(
            'timeout'             => max(5, (int) $timeout),
            'redirection'         => 4,
            'sslverify'           => false,
            'httpversion'         => '1.1',
            'decompress'          => true,
            'limit_response_size' => 3 * 1024 * 1024,
            // A validação segura é feita acima. Não usar wp_safe_remote_get() aqui,
            // pois ele reaplica wp_http_validate_url() e reproduz o falso bloqueio.
            'reject_unsafe_urls' => false,
            'headers'     => array(
                'Accept'          => $accept,
                'Accept-Language' => 'pt-BR,pt;q=0.9,en;q=0.6',
                // Não enviamos Cache-Control:no-cache: aproveitar o cache do portal
                // reduz muito o custo quando o servidor institucional está lento.
                'Connection'      => 'close',
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124 Safari/537.36 SMTT-Intranet/' . (defined('SMTT_INTRANET_VERSION') ? SMTT_INTRANET_VERSION : '1.0.0'),
            ),
        ));
    }

    private function fetch_from_html($site_url, $limit) {
        $url = $this->get_news_listing_url($site_url);
        $response = $this->remote_get($url, 15, 'text/html,application/xhtml+xml');

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'source'  => 'html',
                'items'   => array(),
                'message' => $response->get_error_message(),
            );
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $body = (string) wp_remote_retrieve_body($response);

        if ($code < 200 || $code >= 300 || trim($body) === '') {
            return array(
                'success' => false,
                'source'  => 'html',
                'items'   => array(),
                'message' => 'Página de notícias sem retorno válido. HTTP: ' . $code,
            );
        }

        $items = $this->parse_news_html($body, $url, $limit);

        if (empty($items)) {
            return array(
                'success' => false,
                'source'  => 'html',
                'items'   => array(),
                'message' => __('A página respondeu, mas não foi possível identificar as notícias.', 'smtt-intranet'),
            );
        }

        return array(
            'success' => true,
            'source'  => 'portal',
            'items'   => array_slice($items, 0, $limit),
            'message' => __('Notícias carregadas diretamente do portal institucional.', 'smtt-intranet'),
        );
    }

    private function parse_news_html($html, $listing_url, $limit) {
        if (!class_exists('DOMDocument')) {
            return $this->parse_news_html_regex($html, $listing_url, $limit);
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return $this->parse_news_html_regex($html, $listing_url, $limit);
        }

        $xpath = new DOMXPath($dom);
        $anchors = $xpath->query('//a[contains(@href, "/noticias/")]');
        $items = array();
        $seen = array();
        $listing_path = rtrim((string) wp_parse_url($listing_url, PHP_URL_PATH), '/');

        if (!$anchors) {
            return array();
        }

        foreach ($anchors as $anchor) {
            if (!($anchor instanceof DOMElement)) {
                continue;
            }

            $href = trim($anchor->getAttribute('href'));
            $link = $this->normalize_remote_url($href, $listing_url);
            $path = rtrim((string) wp_parse_url($link, PHP_URL_PATH), '/');

            // Aceita somente páginas individuais /noticias/slug.
            if (!$link || $path === '' || $path === $listing_path || !preg_match('#/noticias/[^/]+$#i', $path)) {
                continue;
            }

            $key = strtolower($link);
            if (isset($seen[$key])) {
                continue;
            }

            $container = $this->find_news_container($anchor);
            $title = '';

            if ($container) {
                $heading = $xpath->query('.//h1|.//h2|.//h3|.//h4|.//h5', $container);
                if ($heading && $heading->length) {
                    $title = $this->clean_text($heading->item(0)->textContent);
                }
            }

            if ($title === '' || preg_match('/^(leia|saiba|ver)\s+/iu', $title)) {
                $title = $this->clean_text($anchor->textContent);
            }

            if ($title === '' || $this->text_length($title) < 8 || preg_match('/^(leia mais|not[ií]cias recentes)$/iu', $title)) {
                continue;
            }

            $excerpt = '';
            $date = '';
            $image = '';

            if ($container) {
                $paragraphs = $xpath->query('.//p', $container);
                if ($paragraphs) {
                    foreach ($paragraphs as $paragraph) {
                        $candidate = $this->clean_text($paragraph->textContent);
                        if ($this->text_length($candidate) >= 35 && stripos($candidate, 'leia mais') === false) {
                            $excerpt = $candidate;
                            break;
                        }
                    }
                }

                $times = $xpath->query('.//time', $container);
                if ($times && $times->length) {
                    $time = $times->item(0);
                    if ($time instanceof DOMElement && $time->hasAttribute('datetime')) {
                        $date = trim($time->getAttribute('datetime'));
                    }
                    if ($date === '') {
                        $date = $this->parse_portuguese_date($time->textContent);
                    }
                }

                if ($date === '') {
                    $date = $this->parse_portuguese_date($container->textContent);
                }

                $image = $this->extract_image_from_dom_container($xpath, $container, $listing_url);
            }

            $items[] = array(
                'title'   => $title,
                'excerpt' => wp_trim_words($excerpt, 28, '...'),
                'link'    => esc_url_raw($link),
                'date'    => $date,
                'image'   => esc_url_raw($image),
            );
            $seen[$key] = true;

            if (count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }

    private function extract_image_from_dom_container($xpath, $container, $base_url) {
        if (!($xpath instanceof DOMXPath) || !($container instanceof DOMElement)) {
            return '';
        }

        $images = $xpath->query('.//img', $container);
        if ($images && $images->length) {
            foreach ($images as $img) {
                if (!($img instanceof DOMElement)) {
                    continue;
                }

                foreach (array('data-src', 'data-lazy-src', 'data-original', 'data-image', 'src') as $attr) {
                    $candidate = trim($img->getAttribute($attr));
                    if ($this->is_real_news_image_candidate($candidate)) {
                        $normalized = $this->normalize_remote_url($candidate, $base_url);
                        if ($normalized !== '') {
                            return $normalized;
                        }
                    }
                }

                foreach (array('data-srcset', 'data-lazy-srcset', 'srcset') as $attr) {
                    $srcset = trim($img->getAttribute($attr));
                    $candidate = $this->pick_largest_srcset_url($srcset);
                    if ($this->is_real_news_image_candidate($candidate)) {
                        $normalized = $this->normalize_remote_url($candidate, $base_url);
                        if ($normalized !== '') {
                            return $normalized;
                        }
                    }
                }
            }
        }

        $sources = $xpath->query('.//source[@srcset or @data-srcset]', $container);
        if ($sources && $sources->length) {
            foreach ($sources as $source) {
                if (!($source instanceof DOMElement)) {
                    continue;
                }
                $srcset = $source->getAttribute('srcset');
                if ($srcset === '') {
                    $srcset = $source->getAttribute('data-srcset');
                }
                $candidate = $this->pick_largest_srcset_url($srcset);
                if ($this->is_real_news_image_candidate($candidate)) {
                    $normalized = $this->normalize_remote_url($candidate, $base_url);
                    if ($normalized !== '') {
                        return $normalized;
                    }
                }
            }
        }

        $hint_nodes = $xpath->query('.//*[@data-image or @data-thumbnail or @data-thumb or @content or @href]', $container);
        if ($hint_nodes && $hint_nodes->length) {
            foreach ($hint_nodes as $node) {
                if (!($node instanceof DOMElement)) {
                    continue;
                }

                foreach (array('data-image', 'data-thumbnail', 'data-thumb') as $attr) {
                    $candidate = trim($node->getAttribute($attr));
                    if ($this->is_real_news_image_candidate($candidate)) {
                        $normalized = $this->normalize_remote_url($candidate, $base_url);
                        if ($normalized !== '') {
                            return $normalized;
                        }
                    }
                }

                $tag = strtolower($node->tagName);
                if ($tag === 'meta' && $node->hasAttribute('content')) {
                    $property = strtolower($node->getAttribute('property') . ' ' . $node->getAttribute('name') . ' ' . $node->getAttribute('itemprop'));
                    if (strpos($property, 'image') !== false) {
                        $candidate = trim($node->getAttribute('content'));
                        if ($this->is_real_news_image_candidate($candidate)) {
                            $normalized = $this->normalize_remote_url($candidate, $base_url);
                            if ($normalized !== '') {
                                return $normalized;
                            }
                        }
                    }
                }

                if ($tag === 'link' && $node->hasAttribute('href')) {
                    $rel = strtolower($node->getAttribute('rel') . ' ' . $node->getAttribute('itemprop'));
                    if (strpos($rel, 'image') !== false || strpos($rel, 'thumbnail') !== false) {
                        $candidate = trim($node->getAttribute('href'));
                        if ($this->is_real_news_image_candidate($candidate)) {
                            $normalized = $this->normalize_remote_url($candidate, $base_url);
                            if ($normalized !== '') {
                                return $normalized;
                            }
                        }
                    }
                }
            }
        }

        $styled = $xpath->query('.//*[@style]', $container);
        if ($styled && $styled->length) {
            foreach ($styled as $node) {
                if (!($node instanceof DOMElement)) {
                    continue;
                }
                $style = $node->getAttribute('style');
                if (preg_match('#background(?:-image)?\s*:\s*url\(["\']?([^"\')]+)#i', $style, $match)) {
                    $candidate = trim(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    if ($this->is_real_news_image_candidate($candidate)) {
                        $normalized = $this->normalize_remote_url($candidate, $base_url);
                        if ($normalized !== '') {
                            return $normalized;
                        }
                    }
                }
            }
        }

        $background_nodes = $xpath->query('.//*[@data-bg or @data-background-image or @data-background]', $container);
        if ($background_nodes && $background_nodes->length) {
            foreach ($background_nodes as $node) {
                if (!($node instanceof DOMElement)) {
                    continue;
                }
                foreach (array('data-bg', 'data-background-image', 'data-background') as $attr) {
                    $candidate = trim($node->getAttribute($attr));
                    if ($this->is_real_news_image_candidate($candidate)) {
                        $normalized = $this->normalize_remote_url($candidate, $base_url);
                        if ($normalized !== '') {
                            return $normalized;
                        }
                    }
                }
            }
        }

        return '';
    }

    private function pick_largest_srcset_url($srcset) {
        $srcset = trim((string) $srcset);
        if ($srcset === '') {
            return '';
        }

        $parts = array_map('trim', explode(',', html_entity_decode($srcset, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        for ($i = count($parts) - 1; $i >= 0; $i--) {
            $candidate = preg_replace('/\s+\d+(?:\.\d+)?(?:w|x)\s*$/i', '', $parts[$i]);
            $candidate = trim((string) $candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    private function find_news_container($anchor) {
        $node = $anchor;

        for ($i = 0; $i < 6 && $node; $i++) {
            if ($node instanceof DOMElement) {
                $tag = strtolower($node->tagName);
                $class = ' ' . strtolower($node->getAttribute('class')) . ' ';

                if (
                    $tag === 'article' ||
                    $tag === 'li' ||
                    preg_match('/(?:post|noticia|notícia|news|item|card|elementor-post)/iu', $class)
                ) {
                    return $node;
                }
            }

            $node = $node->parentNode;
        }

        return $anchor->parentNode instanceof DOMElement ? $anchor->parentNode : null;
    }

    private function parse_news_html_regex($html, $listing_url, $limit) {
        $items = array();
        $seen = array();

        // Primeiro tenta blocos de conteúdo completos para preservar resumo/data/imagem.
        $blocks = array();
        if (preg_match_all('#<article\b[^>]*>.*?</article>#is', $html, $article_matches)) {
            $blocks = $article_matches[0];
        }

        // Alguns temas não usam <article>; captura containers com classes relacionadas.
        if (empty($blocks) && preg_match_all('#<(?:div|li)\b[^>]*class=["\'][^"\']*(?:noticia|news|post|card|item)[^"\']*["\'][^>]*>.*?</(?:div|li)>#is', $html, $container_matches)) {
            $blocks = $container_matches[0];
        }

        foreach ($blocks as $block) {
            if (!preg_match('#href=["\']([^"\']*/noticias/[^"\'\\#?]+/?)["\']#i', $block, $link_match)) {
                continue;
            }

            $link = $this->normalize_remote_url($link_match[1], $listing_url);
            $key = strtolower($link);
            if (!$link || isset($seen[$key])) {
                continue;
            }

            $title = '';
            if (preg_match('#<h[1-5]\b[^>]*>(.*?)</h[1-5]>#is', $block, $title_match)) {
                $title = $this->clean_text($title_match[1]);
            }
            if ($title === '' && preg_match('#<a\b[^>]*href=["\'][^"\']*/noticias/[^"\']+["\'][^>]*>(.*?)</a>#is', $block, $anchor_match)) {
                $title = $this->clean_text($anchor_match[1]);
            }
            if ($title === '' || $this->text_length($title) < 8 || preg_match('/^(leia mais|not[ií]cias recentes)$/iu', $title)) {
                continue;
            }

            $excerpt = '';
            if (preg_match_all('#<p\b[^>]*>(.*?)</p>#is', $block, $paragraph_matches)) {
                foreach ($paragraph_matches[1] as $paragraph_html) {
                    $candidate = $this->clean_text($paragraph_html);
                    if ($this->text_length($candidate) >= 35 && stripos($candidate, 'leia mais') === false) {
                        $excerpt = $candidate;
                        break;
                    }
                }
            }

            $date = '';
            if (preg_match('#<time\b[^>]*datetime=["\']([^"\']+)["\'][^>]*>#i', $block, $date_match)) {
                $date = trim($date_match[1]);
            }
            if ($date === '') {
                $date = $this->parse_portuguese_date($block);
            }

            $image = '';
            if (preg_match('#<img\b[^>]*>#i', $block, $image_tag_match)) {
                $image = $this->extract_image_url_from_tag($image_tag_match[0], $listing_url);
            }
            if ($image === '' && preg_match('#<(?:img|source)\b[^>]*(?:data-srcset|srcset)=["\']([^"\']+)["\'][^>]*>#i', $block, $srcset_match)) {
                $candidate = $this->pick_largest_srcset_url($srcset_match[1]);
                if ($this->is_real_news_image_candidate($candidate)) {
                    $image = $this->normalize_remote_url($candidate, $listing_url);
                }
            }
            if ($image === '' && preg_match('#background(?:-image)?\s*:\s*url\(["\']?([^"\')]+)#i', $block, $background_match)) {
                $candidate = trim(html_entity_decode($background_match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($this->is_real_news_image_candidate($candidate)) {
                    $image = $this->normalize_remote_url($candidate, $listing_url);
                }
            }

            $items[] = array(
                'title'   => $title,
                'excerpt' => wp_trim_words($excerpt, 28, '...'),
                'link'    => esc_url_raw($link),
                'date'    => $date,
                'image'   => esc_url_raw($image),
            );
            $seen[$key] = true;

            if (count($items) >= $limit) {
                return $items;
            }
        }

        // Fallback mínimo: links individuais de notícia.
        if (!preg_match_all('#<a[^>]+href=["\']([^"\']*/noticias/[^"\'\\#?]+/?)["\'][^>]*>(.*?)</a>#is', $html, $matches, PREG_SET_ORDER)) {
            return $items;
        }

        foreach ($matches as $match) {
            $link = $this->normalize_remote_url($match[1], $listing_url);
            $title = $this->clean_text($match[2]);
            $key = strtolower($link);

            if (!$link || !$title || $this->text_length($title) < 8 || isset($seen[$key])) {
                continue;
            }

            $items[] = array(
                'title'   => $title,
                'excerpt' => '',
                'link'    => esc_url_raw($link),
                'date'    => '',
                'image'   => '',
            );
            $seen[$key] = true;

            if (count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }

    private function fetch_from_rss($site_url, $limit) {
        $feed_url = trailingslashit($this->get_base_site_url($site_url)) . 'feed/';
        $response = $this->remote_get($feed_url, 12, 'application/rss+xml,application/atom+xml,application/xml,text/xml');

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'source'  => 'rss',
                'items'   => array(),
                'message' => $response->get_error_message(),
            );
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $body = (string) wp_remote_retrieve_body($response);

        if ($code < 200 || $code >= 300 || trim($body) === '') {
            return array(
                'success' => false,
                'source'  => 'rss',
                'items'   => array(),
                'message' => 'Feed sem retorno válido. HTTP: ' . $code,
            );
        }

        $items = $this->parse_feed_xml($body, $feed_url, $limit);

        if (empty($items)) {
            return array(
                'success' => false,
                'source'  => 'rss',
                'items'   => array(),
                'message' => __('Feed respondeu, mas nenhum item válido foi identificado.', 'smtt-intranet'),
            );
        }

        return array(
            'success' => true,
            'source'  => 'rss',
            'items'   => array_slice($items, 0, $limit),
            'message' => __('Notícias carregadas via RSS.', 'smtt-intranet'),
        );
    }

    private function parse_feed_xml($xml, $feed_url, $limit) {
        if (!class_exists('DOMDocument')) {
            return array();
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $loaded = $dom->loadXML($xml, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return array();
        }

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//*[local-name()="item"] | //*[local-name()="entry"]');
        $items = array();

        if (!$nodes) {
            return array();
        }

        foreach ($nodes as $node) {
            $title = $this->xpath_first_text($xpath, './/*[local-name()="title"]', $node);
            $link = $this->xpath_first_text($xpath, './/*[local-name()="link"]', $node);

            if ($link === '') {
                $link_node = $xpath->query('.//*[local-name()="link"][@href]', $node);
                if ($link_node && $link_node->length && $link_node->item(0) instanceof DOMElement) {
                    $link = trim($link_node->item(0)->getAttribute('href'));
                }
            }

            $description = $this->xpath_first_text($xpath, './/*[local-name()="description"] | .//*[local-name()="summary"] | .//*[local-name()="encoded"]', $node);
            $date = $this->xpath_first_text($xpath, './/*[local-name()="pubDate"] | .//*[local-name()="published"] | .//*[local-name()="updated"]', $node);
            $image = '';

            $enclosure = $xpath->query('.//*[local-name()="enclosure"][@url]', $node);
            if ($enclosure && $enclosure->length && $enclosure->item(0) instanceof DOMElement) {
                $image = $this->normalize_remote_url($enclosure->item(0)->getAttribute('url'), $feed_url);
            }

            if ($image === '' && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $description, $matches)) {
                $image = $this->normalize_remote_url($matches[1], $feed_url);
            }

            $title = $this->clean_text($title);
            $excerpt = wp_trim_words($this->clean_text($description), 28, '...');
            $link = $this->normalize_remote_url($link, $feed_url);

            if ($title === '' || $link === '') {
                continue;
            }

            $items[] = array(
                'title'   => $title,
                'excerpt' => $excerpt,
                'link'    => esc_url_raw($link),
                'date'    => $date,
                'image'   => esc_url_raw($image),
            );

            if (count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }

    private function xpath_first_text($xpath, $query, $context) {
        $nodes = $xpath->query($query, $context);
        if (!$nodes || !$nodes->length) {
            return '';
        }

        return trim((string) $nodes->item(0)->textContent);
    }

    private function fetch_from_rest($site_url, $limit) {
        $base = $this->get_base_site_url($site_url);
        $messages = array();

        // smt_noticia é o post type real criado pelo plugin SMT Notícias.
        // Os demais permanecem apenas como fallback para instalações antigas.
        foreach (array('smt_noticia', 'noticias', 'posts') as $rest_base) {
            $query_args = array(
                'per_page' => $limit,
                'orderby'  => 'date',
                'order'    => 'desc',
                'status'   => 'publish',
                '_embed'   => '1',
            );

            $url = add_query_arg($query_args, $base . '/wp-json/wp/v2/' . $rest_base);
            $response = $this->remote_get($url, 12, 'application/json');

            if (is_wp_error($response)) {
                $messages[] = strtoupper($rest_base) . ': ' . $response->get_error_message();
                continue;
            }

            $code = (int) wp_remote_retrieve_response_code($response);
            $body = (string) wp_remote_retrieve_body($response);

            if ($code < 200 || $code >= 300 || trim($body) === '') {
                $messages[] = strtoupper($rest_base) . ': HTTP ' . $code;
                continue;
            }

            $data = json_decode($body, true);
            if (!is_array($data) || empty($data)) {
                $messages[] = strtoupper($rest_base) . ': sem itens';
                continue;
            }

            $items = array();
            foreach ($data as $post) {
                if (!is_array($post)) {
                    continue;
                }

                $title = isset($post['title']['rendered']) ? $this->clean_text($post['title']['rendered']) : '';
                $excerpt = isset($post['excerpt']['rendered']) ? $this->clean_text($post['excerpt']['rendered']) : '';
                $content = isset($post['content']['rendered']) ? (string) $post['content']['rendered'] : '';
                $link = isset($post['link']) ? esc_url_raw($post['link']) : '';
                $date = isset($post['date']) ? (string) $post['date'] : '';
                $image = $this->extract_rest_image($post, $site_url);

                if ($excerpt === '' && $content !== '') {
                    $excerpt = wp_trim_words($this->clean_text($content), 28, '...');
                }

                if ($title === '' || $link === '') {
                    continue;
                }

                $items[] = array(
                    'title'    => $title,
                    'subtitle' => '', // preenchido pela página individual na sincronização.
                    'excerpt'  => $excerpt,
                    'link'     => $link,
                    'date'     => $date,
                    'image'    => $image,
                );
            }

            if (!empty($items)) {
                return array(
                    'success' => true,
                    'source'  => 'rest:' . $rest_base,
                    'items'   => array_slice($items, 0, $limit),
                    'message' => 'Notícias carregadas pelo endpoint: ' . $rest_base,
                );
            }
        }

        return array(
            'success' => false,
            'source'  => 'rest',
            'items'   => array(),
            'message' => !empty($messages) ? implode(' | ', $messages) : __('Nenhum endpoint REST retornou itens.', 'smtt-intranet'),
        );
    }

    private function extract_rest_image($post, $site_url) {
        $image = '';

        if (!empty($post['_embedded']['wp:featuredmedia'][0])) {
            $media = $post['_embedded']['wp:featuredmedia'][0];

            if (!empty($media['source_url'])) {
                $image = esc_url_raw($media['source_url']);
            } elseif (!empty($media['media_details']['sizes']['large']['source_url'])) {
                $image = esc_url_raw($media['media_details']['sizes']['large']['source_url']);
            } elseif (!empty($media['media_details']['sizes']['medium']['source_url'])) {
                $image = esc_url_raw($media['media_details']['sizes']['medium']['source_url']);
            }
        }

        if ($image === '' && !empty($post['yoast_head_json']['og_image'][0]['url'])) {
            $image = esc_url_raw($post['yoast_head_json']['og_image'][0]['url']);
        }

        if ($image === '' && !empty($post['content']['rendered']) && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $post['content']['rendered'], $matches)) {
            $image = $this->normalize_remote_url($matches[1], $site_url);
        }

        return esc_url_raw($image);
    }

    private function text_length($value) {
        return function_exists('mb_strlen') ? mb_strlen((string) $value, 'UTF-8') : strlen((string) $value);
    }

    private function clean_text($value) {
        $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = wp_strip_all_tags($value, true);
        $value = preg_replace('/\s+/u', ' ', $value);
        return trim((string) $value);
    }

    private function parse_portuguese_date($text) {
        $text = remove_accents(strtolower($this->clean_text($text)));

        if (preg_match('/\b(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})\b/', $text, $m)) {
            return sprintf('%04d-%02d-%02dT12:00:00', (int) $m[3], (int) $m[2], (int) $m[1]);
        }

        if (!preg_match('/\b(\d{1,2})\s+(jan(?:eiro)?|fev(?:ereiro)?|mar(?:co)?|abr(?:il)?|mai(?:o)?|jun(?:ho)?|jul(?:ho)?|ago(?:sto)?|set(?:embro)?|out(?:ubro)?|nov(?:embro)?|dez(?:embro)?)[,\s]+(\d{4})\b/', $text, $m)) {
            return '';
        }

        $months = array(
            'jan' => 1, 'janeiro' => 1,
            'fev' => 2, 'fevereiro' => 2,
            'mar' => 3, 'marco' => 3,
            'abr' => 4, 'abril' => 4,
            'mai' => 5, 'maio' => 5,
            'jun' => 6, 'junho' => 6,
            'jul' => 7, 'julho' => 7,
            'ago' => 8, 'agosto' => 8,
            'set' => 9, 'setembro' => 9,
            'out' => 10, 'outubro' => 10,
            'nov' => 11, 'novembro' => 11,
            'dez' => 12, 'dezembro' => 12,
        );

        $month_key = $m[2];
        if (!isset($months[$month_key])) {
            $month_key = substr($month_key, 0, 3);
        }

        if (!isset($months[$month_key])) {
            return '';
        }

        return sprintf('%04d-%02d-%02dT12:00:00', (int) $m[3], $months[$month_key], (int) $m[1]);
    }

    private function normalize_remote_url($url, $base_url) {
        $url = trim(html_entity_decode((string) $url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($url === '') {
            return '';
        }

        if (strpos($url, '//') === 0) {
            $scheme = wp_parse_url($base_url, PHP_URL_SCHEME);
            return ($scheme ? $scheme : 'https') . ':' . $url;
        }

        if (preg_match('#^https?://#i', $url)) {
            return esc_url_raw($url);
        }

        $parts = wp_parse_url($base_url);
        if (empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        $origin = $parts['scheme'] . '://' . $parts['host'];
        if (!empty($parts['port'])) {
            $origin .= ':' . absint($parts['port']);
        }

        if (strpos($url, '/') === 0) {
            return esc_url_raw($origin . $url);
        }

        $base_path = !empty($parts['path']) ? $parts['path'] : '/';
        $base_dir = trailingslashit(dirname($base_path));

        return esc_url_raw($origin . $base_dir . ltrim($url, '/'));
    }


    // Mantidos para compatibilidade caso algum hook antigo ainda os referencie.
    public function feed_cache_lifetime() {
        return 10 * MINUTE_IN_SECONDS;
    }

    public function configure_feed_options($feed, $url) {
        if (is_object($feed) && method_exists($feed, 'set_timeout')) {
            $feed->set_timeout(12);
        }
    }
}
