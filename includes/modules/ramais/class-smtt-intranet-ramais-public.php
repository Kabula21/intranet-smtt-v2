<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Ramais_Public {

    const CACHE_KEY = 'smtt_ramais_public_payload_v3';
    const CACHE_TTL = HOUR_IN_SECONDS;

    public function register_hooks() {
        add_action('wp_footer', array($this, 'render_drawer'));

        // Carrega os ramais via AJAX. O frontend faz prefetch em segundo plano
        // depois que a Home termina de carregar, deixando o drawer praticamente
        // instantâneo quando o usuário clicar.
        add_action('wp_ajax_smtt_ramais_public_list', array($this, 'ajax_get_ramais'));
        // Diretório interno: não expor ramais por endpoint anônimo.
    }

    /**
     * Remove o payload público já renderizado.
     * Deve ser chamado quando um ramal for criado, alterado, excluído ou importado.
     */
    public static function clear_cache() {
        delete_transient(self::CACHE_KEY);
        // Mantém compatibilidade com os caches das versões anteriores.
        delete_transient('smtt_ramais_public_ativos_v1');
        delete_transient('smtt_ramais_public_ativos_v2');
    }

    public function render_drawer() {
        if (is_admin()) {
            return;
        }

        // v2.0.35.3: a Home pode ter slug /intranet/ ou ser a página inicial.
        // Não restringir o drawer ao antigo slug /home/.
        if (!SMTT_Intranet_Home_Routing::is_home_request() || !is_user_logged_in()) {
            return;
        }

        $ajax_url = admin_url('admin-ajax.php');
        $nonce    = wp_create_nonce('smtt_ramais_public_nonce');
        ?>

        <div
            class="smtt-ramais-public"
            data-smtt-ramais-root
            data-ajax-url="<?php echo esc_url($ajax_url); ?>"
            data-nonce="<?php echo esc_attr($nonce); ?>"
        >
            <button
                type="button"
                class="smtt-ramais-floating-button"
                data-smtt-ramais-open
                aria-label="Abrir ramais internos"
                title="Ramais internos"
            >
                <span class="smtt-ramais-floating-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                        <path d="M6.62 10.79C8.06 13.62 10.38 15.94 13.21 17.38L15.41 15.18C15.68 14.91 16.08 14.82 16.43 14.94C17.55 15.31 18.75 15.5 20 15.5C20.55 15.5 21 15.95 21 16.5V20C21 20.55 20.55 21 20 21C10.61 21 3 13.39 3 4C3 3.45 3.45 3 4 3H7.5C8.05 3 8.5 3.45 8.5 4C8.5 5.25 8.69 6.45 9.06 7.57C9.17 7.92 9.09 8.31 8.81 8.59L6.62 10.79Z" fill="currentColor"/>
                    </svg>
                </span>
            </button>

            <div class="smtt-ramais-backdrop" data-smtt-ramais-close></div>

            <aside
                class="smtt-ramais-drawer"
                role="dialog"
                aria-modal="true"
                aria-label="Ramais internos"
            >
                <div class="smtt-ramais-drawer-header">
                    <div>
                        <br>
                        <span class="smtt-ramais-eyebrow">Diretório interno</span>
                        <h2>Ramais Internos</h2>
                        <p>Consulte rapidamente os ramais por setor ou suporte.</p>
                    </div>
                </div>

                <div class="smtt-ramais-search-wrap">
                    <input
                        type="search"
                        class="smtt-ramais-search"
                        data-smtt-ramais-search
                        placeholder="Buscar setor, suporte ou ramal..."
                        autocomplete="off"
                    >
                </div>

                <div class="smtt-ramais-list" data-smtt-ramais-list>
                    <div class="smtt-ramais-empty" data-smtt-ramais-loading hidden>
                        Carregando ramais...
                    </div>

                    <div class="smtt-ramais-empty" data-smtt-ramais-empty hidden>
                        Nenhum ramal encontrado.
                    </div>
                </div>
            </aside>

            <?php $this->render_inline_loader_script(); ?>
        </div>

        <?php
    }

    /**
     * Retorna o HTML já pronto para o frontend. Além de evitar SELECT *,
     * o HTML final também é cacheado; em requisições seguintes o servidor
     * não precisa consultar o banco nem reconstruir centenas de cards.
     */
    private function get_cached_payload() {
        $payload = get_transient(self::CACHE_KEY);

        if (is_array($payload) && isset($payload['html'], $payload['count'])) {
            return $payload;
        }

        $ramais = SMTT_Intranet_Ramais_Repository::get_public_active(500);

        if (!is_array($ramais)) {
            $ramais = array();
        }

        ob_start();

        foreach ($ramais as $ramal) {
            $setor        = !empty($ramal['setor']) ? $ramal['setor'] : 'Setor não informado';
            $cargo        = !empty($ramal['cargo']) ? $ramal['cargo'] : '';
            $numero_ramal = !empty($ramal['ramal']) ? $ramal['ramal'] : '';

            $search_text = strtolower(
                remove_accents(
                    wp_strip_all_tags(
                        $setor . ' ' . $cargo . ' ' . $numero_ramal
                    )
                )
            );
            ?>
            <div
                class="smtt-ramais-item"
                data-smtt-ramais-item
                data-search="<?php echo esc_attr($search_text); ?>"
            >
                <div class="smtt-ramais-item-main">
                    <strong><?php echo esc_html($setor); ?></strong>

                    <?php if (!empty($cargo)) : ?>
                        <span><?php echo esc_html($cargo); ?></span>
                    <?php else : ?>
                        <span>Ramal interno</span>
                    <?php endif; ?>
                </div>

                <div class="smtt-ramais-number">
                    <span>Ramal</span>
                    <strong><?php echo esc_html($numero_ramal); ?></strong>
                </div>
            </div>
            <?php
        }

        $payload = array(
            'html'  => ob_get_clean(),
            'count' => count($ramais),
        );

        set_transient(self::CACHE_KEY, $payload, self::CACHE_TTL);

        return $payload;
    }

    public function ajax_get_ramais() {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Sessão corporativa necessária.'), 401);
        }
        check_ajax_referer('smtt_ramais_public_nonce', 'nonce');

        $payload = $this->get_cached_payload();

        wp_send_json_success($payload);
    }

    private function render_inline_loader_script() {
        ?>
        <script>
        (function() {
            var root = document.querySelector('[data-smtt-ramais-root]');

            if (!root || root.dataset.smttRamaisBound === '1') {
                return;
            }

            var openButton = root.querySelector('[data-smtt-ramais-open]');
            var closeButtons = root.querySelectorAll('[data-smtt-ramais-close]');
            var searchInput = root.querySelector('[data-smtt-ramais-search]');
            var list = root.querySelector('[data-smtt-ramais-list]');
            var empty = root.querySelector('[data-smtt-ramais-empty]');
            var loading = root.querySelector('[data-smtt-ramais-loading]');
            var phoneIcon = root.querySelector('.smtt-ramais-floating-icon svg');

            if (!openButton || !list) {
                return;
            }

            root.dataset.smttRamaisBound = '1';

            var loaded = false;
            var loadingNow = false;
            var loadPromise = null;
            var phoneRingTimer = null;

            function ringPhoneIcon() {
                if (!phoneIcon || document.hidden || root.classList.contains('is-open')) {
                    return;
                }

                if (typeof phoneIcon.animate !== 'function') {
                    return;
                }

                phoneIcon.getAnimations().forEach(function(animation) {
                    animation.cancel();
                });

                phoneIcon.animate([
                    { transform: 'rotate(0deg) scale(1)' },
                    { transform: 'rotate(-16deg) scale(1.04)' },
                    { transform: 'rotate(14deg) scale(1.04)' },
                    { transform: 'rotate(-12deg) scale(1.03)' },
                    { transform: 'rotate(10deg) scale(1.03)' },
                    { transform: 'rotate(-7deg) scale(1.02)' },
                    { transform: 'rotate(5deg) scale(1.01)' },
                    { transform: 'rotate(0deg) scale(1)' }
                ], {
                    duration: 720,
                    easing: 'ease-in-out'
                });
            }

            window.setTimeout(ringPhoneIcon, 800);
            phoneRingTimer = window.setInterval(ringPhoneIcon, 3600);

            function normalizeText(value) {
                return String(value || '')
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '');
            }

            function setOpen(open) {
                root.classList.toggle('is-open', open);
                document.body.classList.toggle('smtt-ramais-lock', open);
                openButton.setAttribute('aria-expanded', open ? 'true' : 'false');

                if (open && searchInput) {
                    window.setTimeout(function() {
                        searchInput.focus({ preventScroll: true });
                    }, 260);
                }
            }

            function filterRamais() {
                var query = searchInput ? normalizeText(searchInput.value) : '';
                var items = list.querySelectorAll('[data-smtt-ramais-item]');
                var visibleCount = 0;

                items.forEach(function(item) {
                    var searchText = normalizeText(item.getAttribute('data-search'));
                    var visible = !query || searchText.indexOf(query) !== -1;

                    item.hidden = !visible;
                    if (visible) {
                        visibleCount++;
                    }
                });

                if (empty) {
                    empty.hidden = loadingNow || visibleCount > 0;
                }
            }

            function showLoadingIfNeeded() {
                if (!root.classList.contains('is-open')) {
                    return;
                }

                if (loading) {
                    loading.hidden = false;
                }

                if (empty) {
                    empty.hidden = true;
                }
            }

            function loadRamais() {
                if (loaded) {
                    filterRamais();
                    return Promise.resolve();
                }

                if (loadPromise) {
                    showLoadingIfNeeded();
                    return loadPromise;
                }

                loadingNow = true;
                showLoadingIfNeeded();

                var formData = new FormData();
                formData.append('action', 'smtt_ramais_public_list');
                formData.append('nonce', root.getAttribute('data-nonce') || '');

                loadPromise = fetch(root.getAttribute('data-ajax-url'), {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                })
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(function(response) {
                    if (!response || !response.success || !response.data) {
                        throw new Error('Resposta inválida');
                    }

                    if (response.data.html) {
                        list.insertAdjacentHTML('afterbegin', response.data.html);
                    }

                    loaded = true;
                    loadingNow = false;
                    loadPromise = null;

                    if (loading) {
                        loading.hidden = true;
                    }

                    filterRamais();
                })
                .catch(function(error) {
                    loadingNow = false;
                    loadPromise = null;

                    if (loading) {
                        loading.hidden = true;
                    }

                    if (root.classList.contains('is-open') && empty) {
                        empty.hidden = false;
                        empty.textContent = 'Não foi possível carregar os ramais agora.';
                    }

                    throw error;
                });

                // Evita "Uncaught (in promise)" no prefetch silencioso.
                loadPromise.catch(function() {});

                return loadPromise;
            }

            function preloadRamais() {
                if (loaded || loadPromise || document.hidden) {
                    return;
                }
                loadRamais().catch(function() {});
            }

            // A diferença de percepção vem daqui: o diretório começa a ser
            // carregado quando o navegador está ocioso, antes do primeiro clique.
            function schedulePreload() {
                if ('requestIdleCallback' in window) {
                    window.requestIdleCallback(preloadRamais, { timeout: 2200 });
                } else {
                    window.setTimeout(preloadRamais, 1200);
                }
            }

            if (document.readyState === 'complete') {
                schedulePreload();
            } else {
                window.addEventListener('load', schedulePreload, { once: true });
            }

            openButton.setAttribute('aria-expanded', 'false');
            openButton.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();

                var shouldOpen = !root.classList.contains('is-open');
                setOpen(shouldOpen);

                if (shouldOpen) {
                    loadRamais().catch(function() {});
                }
            });

            closeButtons.forEach(function(button) {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    setOpen(false);
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    if (!loaded) {
                        loadRamais().catch(function() {});
                        return;
                    }

                    filterRamais();
                });
            }

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && root.classList.contains('is-open')) {
                    setOpen(false);
                }
            });
        })();
        </script>
        <?php
    }
}
