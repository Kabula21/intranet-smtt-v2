(function () {
    'use strict';

    var config = window.smttIntranetTour || null;
    if (!config || !config.show) return;

    var root = null;
    var currentIndex = -1;
    var visibleSteps = Array.isArray(config.steps) ? config.steps.slice() : [];
    var activeTarget = null;
    var activeCleanup = null;
    var repositionTimer = null;
    var keepAliveTimer = null;
    var transitionToken = 0;
    var replayButton = null;
    var viewedMarked = false;

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function iconSvg(name) {
        var icons = {
            systems: '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="16" height="6" rx="2"></rect><rect x="4" y="14" width="16" height="6" rx="2"></rect><path d="M8 7h.01M8 17h.01"></path></svg>',
            documents: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l4 4v14H7z"></path><path d="M14 3v5h5M10 12h5M10 16h5"></path></svg>',
            departments: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h7l2 2h9v10H3z"></path><path d="M3 7V5h7l2 2"></path></svg>',
            chat: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5h14v10H9l-4 4z"></path><path d="M8 9h8M8 12h5"></path></svg>',
            theme: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 1 0 9.8 9.8z"></path></svg>',
            apps: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="5" cy="5" r="1.4"></circle><circle cx="12" cy="5" r="1.4"></circle><circle cx="19" cy="5" r="1.4"></circle><circle cx="5" cy="12" r="1.4"></circle><circle cx="12" cy="12" r="1.4"></circle><circle cx="19" cy="12" r="1.4"></circle><circle cx="5" cy="19" r="1.4"></circle><circle cx="12" cy="19" r="1.4"></circle><circle cx="19" cy="19" r="1.4"></circle></svg>',
            search: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"></circle><path d="m15.5 15.5 4.5 4.5"></path></svg>',
            user: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21c0-4.2 3.4-7 8-7s8 2.8 8 7"></path></svg>',
            access: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"></path></svg>',
            agenda: '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M7 3v4M17 3v4M3 10h18M8 14h3M13 14h3M8 18h3"></path></svg>',
            mural: '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="3" width="16" height="18" rx="2"></rect><path d="M8 8h8M8 12h8M8 16h5"></path></svg>',
            wiki: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.5A3.5 3.5 0 0 1 7.5 2H12v18H7.5A3.5 3.5 0 0 0 4 23z"></path><path d="M20 5.5A3.5 3.5 0 0 0 16.5 2H12v18h4.5A3.5 3.5 0 0 1 20 23z"></path></svg>',
            links: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.5.5l2-2a5 5 0 0 0-7-7l-1.1 1.1"></path><path d="M14 11a5 5 0 0 0-7.5-.5l-2 2a5 5 0 0 0 7 7l1.1-1.1"></path></svg>',
            news: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v16H5z"></path><path d="M8 8h8M8 12h8M8 16h5"></path></svg>',
            birthday: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 11h16v10H4z"></path><path d="M3 11h18M12 11v10M12 11H7.5a2.5 2.5 0 1 1 2.2-3.7L12 11Zm0 0h4.5a2.5 2.5 0 1 0-2.2-3.7L12 11Z"></path></svg>',
            phone: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8c1.4 2.8 3.8 5.2 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.4 0 .8-.2 1z"></path></svg>',
            spark: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 1.7 4.3L18 9l-4.3 1.7L12 15l-1.7-4.3L6 9l4.3-1.7zM18.5 14l.9 2.1 2.1.9-2.1.9-.9 2.1-.9-2.1-2.1-.9 2.1-.9z"></path></svg>'
        };
        return icons[name] || icons.spark;
    }

    function closeIconSvg() {
        return '<svg class="smtt-tour-close-icon" viewBox="0 0 20 20" aria-hidden="true"><path d="M5 5l10 10M15 5 5 15"></path></svg>';
    }

    function getRoot() {
        if (root) return root;
        root = document.createElement('div');
        root.className = 'smtt-tour-root';
        root.setAttribute('data-smtt-tour-root', '');
        document.body.appendChild(root);
        return root;
    }

    function activateBody() {
        document.body.classList.add('smtt-tour-active');
    }

    function deactivateBody() {
        document.body.classList.remove('smtt-tour-active');
    }

    function clearKeepAlive() {
        if (keepAliveTimer) {
            window.clearInterval(keepAliveTimer);
            keepAliveTimer = null;
        }
    }

    function cleanupActiveDemo() {
        clearKeepAlive();
        document.querySelectorAll('.smtt-tour-demo-emphasis').forEach(function (element) {
            element.classList.remove('smtt-tour-demo-emphasis');
        });

        if (typeof activeCleanup === 'function') {
            try { activeCleanup(); } catch (error) { /* mantém o Tour funcionando */ }
        }
        activeCleanup = null;
    }

    function removeRoot() {
        ++transitionToken;
        cleanupActiveDemo();
        deactivateBody();
        if (root && root.parentNode) root.parentNode.removeChild(root);
        root = null;
        activeTarget = null;
        createReplayButton();
    }

    function hasLocalSeen() {
        if (config.preview || !config.storageKey) return false;
        try {
            if (window.localStorage.getItem(config.storageKey) === '1') return true;
        } catch (error) {
            /* segue para o cookie de contingência */
        }

        return document.cookie.split(';').some(function (part) {
            return part.trim() === encodeURIComponent(config.storageKey) + '=1';
        });
    }

    function rememberLocalSeen() {
        if (!config.storageKey) return;
        try {
            window.localStorage.setItem(config.storageKey, '1');
        } catch (error) {
            /* O registro principal continua sendo o perfil do usuário no WordPress. */
        }

        try {
            document.cookie = encodeURIComponent(config.storageKey) + '=1; path=/; max-age=31536000; SameSite=Lax';
        } catch (error) {
            /* contingência silenciosa */
        }
    }

    function beaconViewed() {
        if (!navigator.sendBeacon || !config.ajaxUrl || !config.nonce) return false;

        try {
            var beaconBody = new FormData();
            beaconBody.append('action', 'smtt_intranet_mark_tour_viewed');
            beaconBody.append('nonce', config.nonce);
            beaconBody.append('version', String(config.version || 1));
            return navigator.sendBeacon(config.ajaxUrl, beaconBody);
        } catch (error) {
            return false;
        }
    }

    function markViewed() {
        rememberLocalSeen();
        if (config.preview) return;
        if (viewedMarked || !config.ajaxUrl || !config.nonce) return;
        viewedMarked = true;

        var body = new URLSearchParams();
        body.append('action', 'smtt_intranet_mark_tour_viewed');
        body.append('nonce', config.nonce);
        body.append('version', String(config.version || 1));

        fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        }).then(function (response) {
            if (!response.ok) throw new Error('Falha ao registrar Tour');
        }).catch(function () {
            viewedMarked = beaconViewed();
        });
    }

    function createReplayButton() {
        if (!config.replayAvailable) return;

        // v2.0.24: o replay vive na navbar. Não criamos mais botão flutuante
        // para evitar cobrir cards da home ou competir com outros atalhos.
        replayButton = document.querySelector('[data-smtt-tour-replay]');
        if (!replayButton) return;

        if (replayButton.getAttribute('data-smtt-tour-bound') !== '1') {
            replayButton.setAttribute('data-smtt-tour-bound', '1');
            replayButton.addEventListener('click', function () {
                if (root) return;
                showWelcome();
            });
        }
    }

    function dismiss() {
        markViewed();
        removeRoot();
    }

    function focusFirstButton(container) {
        window.setTimeout(function () {
            var button = container && container.querySelector('button:not([disabled])');
            if (button) button.focus({ preventScroll: true });
        }, 40);
    }

    function closeNavMenus() {
        document.querySelectorAll('[data-smtt-nav-group]').forEach(function (group) {
            var button = group.querySelector('.smtt-topbar-nav-item[aria-controls]');
            var menu = button ? document.getElementById(button.getAttribute('aria-controls')) : null;
            group.classList.remove('is-open', 'smtt-tour-force-open');
            if (button) {
                button.classList.remove('is-active');
                button.setAttribute('aria-expanded', 'false');
            }
            if (menu) menu.hidden = true;
        });
    }

    function openNavMenu(buttonId, menuId) {
        var button = document.getElementById(buttonId);
        var menu = document.getElementById(menuId);
        var group = button ? button.closest('[data-smtt-nav-group]') : null;
        if (!button || !menu || !group) return button || menu;

        closeNavMenus();

        function enforceOpen() {
            group.classList.add('is-open', 'smtt-tour-force-open');
            button.classList.add('is-active');
            button.setAttribute('aria-expanded', 'true');
            menu.hidden = false;
        }

        enforceOpen();
        keepAliveTimer = window.setInterval(enforceOpen, 180);
        activeCleanup = function () { closeNavMenus(); };
        return menu;
    }

    function demoTheme() {
        var button = document.getElementById('smttThemeToggle');
        var wasDark = document.body.classList.contains('smtt-dark-mode');
        document.body.classList.toggle('smtt-dark-mode', !wasDark);
        if (button) button.classList.add('smtt-tour-demo-emphasis');
        activeCleanup = function () {
            document.body.classList.toggle('smtt-dark-mode', wasDark);
        };
        return button;
    }

    function openAppsDemo() {
        var button = document.getElementById('smttAppsToggle');
        var panel = document.getElementById('smttAppsPanel');
        var wrap = button ? button.closest('.smtt-topbar-apps') : null;
        if (!button || !panel || !wrap) return button || panel;

        var wasOpen = wrap.classList.contains('is-open');
        wrap.classList.add('is-open');
        button.setAttribute('aria-expanded', 'true');
        activeCleanup = function () {
            if (!wasOpen) {
                wrap.classList.remove('is-open');
                button.setAttribute('aria-expanded', 'false');
            }
        };
        return panel;
    }

    function forceCloseOverlay(modal, backdrop, bodyClass) {
        if (modal) {
            modal.classList.remove('is-open');
            modal.hidden = true;
        }
        if (backdrop) {
            backdrop.classList.remove('is-open');
            backdrop.hidden = true;
        }
        if (bodyClass) document.body.classList.remove(bodyClass);
    }

    function findAllowedSectorButton() {
        var allowedButtons = Array.from(document.querySelectorAll('#central-acessos-card [data-smtt-sector-access][data-access-allowed="1"]'));
        return allowedButtons.find(function (candidate) {
            var sectorId = candidate.getAttribute('data-sector-id');
            return !!document.querySelector('.smtt-access-sector-panel[data-sector-panel="' + sectorId + '"]');
        }) || allowedButtons[0] || null;
    }

    function highlightAccessOverview() {
        var card = document.getElementById('central-acessos-card');
        var button = findAllowedSectorButton();
        if (button) button.classList.add('smtt-tour-demo-emphasis');
        activeCleanup = function () {};
        return card;
    }

    function openAccessDemo() {
        var card = document.getElementById('central-acessos-card');
        var button = findAllowedSectorButton();
        var modal = document.getElementById('smttSectorAccessModal');
        var backdrop = document.getElementById('smttSectorAccessBackdrop');

        if (!button || !modal) return highlightAccessOverview() || card;

        button.click();
        activeCleanup = function () {
            forceCloseOverlay(modal, backdrop, 'smtt-access-modal-open');
            document.querySelectorAll('.smtt-access-sector-panel').forEach(function (panel) { panel.hidden = true; });
        };
        return modal;
    }

    function snapshotElement(element) {
        return element ? {
            element: element,
            hidden: !!element.hidden,
            active: element.classList.contains('is-active')
        } : null;
    }

    function restoreElement(snapshot) {
        if (!snapshot || !snapshot.element) return;
        snapshot.element.hidden = snapshot.hidden;
        snapshot.element.classList.toggle('is-active', snapshot.active);
    }

    function getAgendaEventContext() {
        var agenda = document.getElementById('agenda');
        if (!agenda) return null;
        var monthPanels = Array.from(agenda.querySelectorAll('[data-smtt-agenda-month-panel]'));
        var currentPanel = monthPanels.find(function (panel) { return !panel.hidden; });
        var day = currentPanel ? currentPanel.querySelector('[data-smtt-agenda-day][data-event-count]:not([data-event-count="0"])') : null;
        if (!day) day = agenda.querySelector('[data-smtt-agenda-day][data-event-count]:not([data-event-count="0"])');
        if (!day) day = agenda.querySelector('[data-smtt-agenda-day]');
        if (!day) return { agenda: agenda, day: null, monthPanels: monthPanels, targetPanel: null };
        var targetPanel = day.closest('[data-smtt-agenda-month-panel]');
        return { agenda: agenda, day: day, monthPanels: monthPanels, targetPanel: targetPanel };
    }

    function highlightAgendaOverview() {
        var context = getAgendaEventContext();
        if (!context) return null;
        var agenda = context.agenda;
        var select = agenda.querySelector('[data-smtt-agenda-month-select]');
        var calendarScreen = agenda.querySelector('[data-smtt-agenda-calendar-screen]');
        var detailsScreen = agenda.querySelector('[data-smtt-agenda-details-screen]');
        var detailPanels = Array.from(agenda.querySelectorAll('[data-smtt-agenda-detail]'));
        var snapshot = {
            calendar: snapshotElement(calendarScreen),
            details: snapshotElement(detailsScreen),
            months: context.monthPanels.map(snapshotElement),
            detailPanels: detailPanels.map(snapshotElement),
            selectValue: select ? select.value : null
        };

        if (context.targetPanel) {
            context.monthPanels.forEach(function (panel) { panel.hidden = panel !== context.targetPanel; });
            if (select) select.value = context.targetPanel.getAttribute('data-smtt-agenda-month-panel') || select.value;
        }
        if (calendarScreen) {
            calendarScreen.hidden = false;
            calendarScreen.classList.add('is-active');
        }
        if (detailsScreen) {
            detailsScreen.hidden = true;
            detailsScreen.classList.remove('is-active');
        }
        detailPanels.forEach(function (panel) { panel.hidden = true; });
        if (context.day) context.day.classList.add('smtt-tour-demo-emphasis', 'smtt-tour-agenda-day-focus');

        activeCleanup = function () {
            restoreElement(snapshot.calendar);
            restoreElement(snapshot.details);
            snapshot.months.forEach(restoreElement);
            snapshot.detailPanels.forEach(restoreElement);
            if (select && snapshot.selectValue !== null) select.value = snapshot.selectValue;
        };
        return agenda;
    }

    function openAgendaDemo() {
        var context = getAgendaEventContext();
        if (!context || !context.agenda) return null;
        var agenda = context.agenda;
        var day = context.day;
        var calendarScreen = agenda.querySelector('[data-smtt-agenda-calendar-screen]');
        var detailsScreen = agenda.querySelector('[data-smtt-agenda-details-screen]');
        var select = agenda.querySelector('[data-smtt-agenda-month-select]');
        var detailTitle = agenda.querySelector('[data-smtt-agenda-detail-title]');
        var detailSummary = agenda.querySelector('[data-smtt-agenda-detail-summary]');
        var monthPanels = Array.from(agenda.querySelectorAll('[data-smtt-agenda-month-panel]'));
        var detailPanels = Array.from(agenda.querySelectorAll('[data-smtt-agenda-detail]'));

        if (!day || !calendarScreen || !detailsScreen) return highlightAgendaOverview() || agenda;

        var date = day.getAttribute('data-date') || '';
        var label = day.getAttribute('data-date-label') || date;
        var count = parseInt(day.getAttribute('data-event-count') || '0', 10);
        var detailPanel = agenda.querySelector('[data-smtt-agenda-detail="' + date + '"]');
        if (!detailPanel) return highlightAgendaOverview() || agenda;

        var snapshot = {
            calendar: snapshotElement(calendarScreen),
            details: snapshotElement(detailsScreen),
            months: monthPanels.map(snapshotElement),
            detailPanels: detailPanels.map(snapshotElement),
            selectValue: select ? select.value : null,
            title: detailTitle ? detailTitle.textContent : '',
            summary: detailSummary ? detailSummary.textContent : ''
        };

        var dateParts = date.split('-');
        var monthValue = dateParts.length === 3 ? String(parseInt(dateParts[1], 10)) : (select ? select.value : '');
        monthPanels.forEach(function (panel) {
            panel.hidden = panel.getAttribute('data-smtt-agenda-month-panel') !== monthValue;
        });
        if (select && monthValue) select.value = monthValue;
        detailPanels.forEach(function (panel) { panel.hidden = panel !== detailPanel; });
        calendarScreen.hidden = true;
        calendarScreen.classList.remove('is-active');
        detailsScreen.hidden = false;
        detailsScreen.classList.add('is-active');
        if (detailTitle) detailTitle.textContent = 'Agenda de ' + label;
        if (detailSummary) detailSummary.textContent = count + (count === 1 ? ' marcação encontrada.' : ' marcações encontradas.');

        activeCleanup = function () {
            restoreElement(snapshot.calendar);
            restoreElement(snapshot.details);
            snapshot.months.forEach(restoreElement);
            snapshot.detailPanels.forEach(restoreElement);
            if (select && snapshot.selectValue !== null) select.value = snapshot.selectValue;
            if (detailTitle) detailTitle.textContent = snapshot.title;
            if (detailSummary) detailSummary.textContent = snapshot.summary;
        };
        return detailsScreen;
    }

    function highlightMuralOverview() {
        var mural = document.getElementById('mural');
        var card = mural ? mural.querySelector('.smtt-mural-open-item') : null;
        if (card) card.classList.add('smtt-tour-demo-emphasis');
        activeCleanup = function () {};
        return mural;
    }

    function openMuralDemo() {
        var mural = document.getElementById('mural');
        var card = mural ? mural.querySelector('.smtt-mural-open-item') : null;
        var modal = document.getElementById('smttMuralModal');
        var backdrop = document.getElementById('smttMuralModalBackdrop');
        var body = document.getElementById('smttMuralModalBody');

        if (!mural || !card || !modal) return highlightMuralOverview() || mural;

        card.click();
        activeCleanup = function () {
            forceCloseOverlay(modal, backdrop, 'smtt-mural-modal-open');
            if (body) body.innerHTML = '';
        };
        return modal.querySelector('.smtt-mural-modal-panel') || modal;
    }

    function highlightBirthdayOverview() {
        var section = document.getElementById('aniversariantes');
        var openButton = document.getElementById('smttBdayTimelineOpen');
        if (openButton) openButton.classList.add('smtt-tour-demo-emphasis');
        activeCleanup = function () {};
        return section;
    }

    function openBirthdayDemo() {
        var section = document.getElementById('aniversariantes');
        var openButton = document.getElementById('smttBdayTimelineOpen');
        var modal = document.getElementById('smttBdayTimelineModal');
        var backdrop = document.getElementById('smttBdayTimelineBackdrop');
        if (!section || !openButton || !modal) return highlightBirthdayOverview() || section;

        openButton.click();
        activeCleanup = function () {
            forceCloseOverlay(modal, backdrop, 'smtt-bday-modal-open');
        };
        return modal.querySelector('.smtt-bday-timeline-panel') || modal;
    }

    function openRamaisDemo() {
        var button = document.querySelector('[data-smtt-ramais-open]');
        var ramaisRoot = document.querySelector('[data-smtt-ramais-root]');
        var drawer = ramaisRoot ? ramaisRoot.querySelector('.smtt-ramais-drawer') : null;
        if (!button || !ramaisRoot || !drawer) return button;

        var wasOpen = ramaisRoot.classList.contains('is-open');
        if (!wasOpen) button.click();
        activeCleanup = function () {
            if (!wasOpen && ramaisRoot.classList.contains('is-open')) button.click();
        };
        return drawer;
    }

    function emphasize(selector, fallback) {
        var elements = Array.from(document.querySelectorAll(selector));
        elements.slice(0, 4).forEach(function (element) {
            element.classList.add('smtt-tour-demo-emphasis');
        });
        activeCleanup = function () {};
        return fallback;
    }

    function prepareStep(step, fallbackTarget) {
        cleanupActiveDemo();
        var target = fallbackTarget;

        switch (step.key) {
            case 'systems_menu':
                target = openNavMenu('smttSystemsToggle', 'smttSystemsDropdown') || fallbackTarget;
                break;
            case 'documents_menu':
                target = openNavMenu('smttDocumentsToggle', 'smttDocumentsDropdown') || fallbackTarget;
                break;
            case 'departments_menu':
                target = openNavMenu('smttServicesToggle', 'smttServicesDropdown') || fallbackTarget;
                break;
            case 'theme':
                target = demoTheme() || fallbackTarget;
                break;
            case 'apps':
                target = openAppsDemo() || fallbackTarget;
                break;
            case 'search':
                target = emphasize('.smtt-banner-hero-center .smtt-banner-hero-search, .smtt-banner-hero-center .smtt-banner-hero-tip', fallbackTarget) || fallbackTarget;
                break;
            case 'access':
                target = highlightAccessOverview() || fallbackTarget;
                break;
            case 'access_detail':
                target = openAccessDemo() || fallbackTarget;
                break;
            case 'agenda':
                target = highlightAgendaOverview() || fallbackTarget;
                break;
            case 'agenda_detail':
                target = openAgendaDemo() || fallbackTarget;
                break;
            case 'mural':
                target = highlightMuralOverview() || fallbackTarget;
                break;
            case 'mural_detail':
                target = openMuralDemo() || fallbackTarget;
                break;
            case 'wiki':
                target = emphasize('#base-interna #smttWikiSearchBar, #base-interna .smtt-wiki-cat', fallbackTarget) || fallbackTarget;
                break;
            case 'useful_links':
                target = emphasize('#links-uteis-card .smtt-useful-link-item', fallbackTarget) || fallbackTarget;
                break;
            case 'news':
                target = emphasize('#comunicados .smtt-news-card-inline', fallbackTarget) || fallbackTarget;
                break;
            case 'birthdays':
                target = highlightBirthdayOverview() || fallbackTarget;
                break;
            case 'birthdays_detail':
                target = openBirthdayDemo() || fallbackTarget;
                break;
            case 'ramais':
                target = openRamaisDemo() || fallbackTarget;
                break;
            case 'chat':
                target = emphasize('.smtt-topbar-chat-btn', fallbackTarget) || fallbackTarget;
                break;
            case 'user':
                target = emphasize('.smtt-modern-user', fallbackTarget) || fallbackTarget;
                break;
            default:
                activeCleanup = function () {};
        }

        return target;
    }

    function isLiveDemo(key) {
        return ['systems_menu', 'documents_menu', 'departments_menu', 'theme', 'apps', 'access_detail', 'agenda_detail', 'mural_detail', 'birthdays_detail', 'ramais'].indexOf(key) !== -1;
    }

    function showWelcome() {
        ++transitionToken;
        cleanupActiveDemo();
        activeTarget = null;
        currentIndex = -1;
        activateBody();

        // Exibir a tela de boas-vindas já conta como visualização da rodada.
        // Assim, atualizar a página em qualquer momento não reinicia o Tour.
        markViewed();

        var container = getRoot();
        container.classList.remove('is-transitioning');
        var imageHtml = '';

        if (config.imageUrl) {
            imageHtml = '<div class="smtt-tour-welcome-media smtt-tour-welcome-media--' + escapeHtml(config.imageOrientation || 'horizontal') + '">' +
                '<img src="' + escapeHtml(config.imageUrl) + '" alt="' + escapeHtml(config.imageAlt || '') + '">' +
                '</div>';
        }

        container.innerHTML = '<div class="smtt-tour-backdrop"></div>' +
            '<section class="smtt-tour-welcome ' + (config.imageUrl ? 'has-media smtt-tour-welcome--' + escapeHtml(config.imageOrientation || 'horizontal') : 'no-media') + '" role="dialog" aria-modal="true" aria-labelledby="smttTourWelcomeTitle">' +
                '<button type="button" class="smtt-tour-close" data-tour-dismiss aria-label="Fechar apresentação">' + closeIconSvg() + '</button>' +
                imageHtml +
                '<div class="smtt-tour-welcome-copy">' +
                    '<div class="smtt-tour-brandline"><span class="smtt-tour-brandmark">V2</span><span>INTRANET SMTT</span></div>' +
                    '<h2 id="smttTourWelcomeTitle">' + escapeHtml(config.welcomeTitle) + '</h2>' +
                    '<p>' + escapeHtml(config.welcomeText) + '</p>' +
                    '<div class="smtt-tour-welcome-meta"><span>✦ Tour interativo</span><span>•</span><span>' + visibleSteps.length + ' pontos principais</span></div>' +
                    '<div class="smtt-tour-welcome-actions">' +
                        '<button type="button" class="smtt-tour-primary" data-tour-start>Começar o Tour <span aria-hidden="true">→</span></button>' +
                        '<button type="button" class="smtt-tour-secondary" data-tour-dismiss>Explorar por conta própria</button>' +
                    '</div>' +
                    '<small class="smtt-tour-welcome-note">Sem pressa: você pode sair quando quiser e continuar usando a Intranet normalmente.</small>' +
                '</div>' +
            '</section>';

        container.querySelector('[data-tour-start]').addEventListener('click', function () {
            /*
             * Começar já conta como visualização desta rodada. Isso garante que
             * atualizar a página no meio do Tour não faça a abertura automática
             * voltar. O colaborador pode rever depois pelo botão fixo.
             */
            markViewed();
            if (!visibleSteps.length) {
                showFinish();
                return;
            }
            showStep(0);
        });

        container.querySelectorAll('[data-tour-dismiss]').forEach(function (button) {
            button.addEventListener('click', dismiss);
        });

        focusFirstButton(container.querySelector('.smtt-tour-welcome'));
    }

    function getExistingStep(index, direction) {
        var i = index;
        while (i >= 0 && i < visibleSteps.length) {
            var target = document.querySelector(visibleSteps[i].target);
            if (target) return { index: i, step: visibleSteps[i], target: target };
            i += direction;
        }
        return null;
    }

    function setTransitioning(isTransitioning) {
        var container = getRoot();
        container.classList.toggle('is-transitioning', !!isTransitioning);
    }

    function waitForLayout(callback, delay) {
        window.setTimeout(function () {
            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(callback);
            });
        }, delay || 0);
    }

    function revealCurrentStep(token) {
        if (token !== transitionToken || !root) return;
        positionCurrent();
        waitForLayout(function () {
            if (token !== transitionToken || !root) return;
            positionCurrent();
            setTransitioning(false);
            var coach = root.querySelector('[data-tour-coachmark]');
            if (coach) focusFirstButton(coach);
        }, 40);
    }

    function scrollTargetIntoPlace(target, callback) {
        if (!target) {
            callback();
            return;
        }
        var style = window.getComputedStyle(target);
        var isFixed = style.position === 'fixed' || !!target.closest('#smttMuralModal, #smttSectorAccessModal, #smttBdayTimelineModal, [data-smtt-ramais-root].is-open');
        var rect = target.getBoundingClientRect();
        var mostlyVisible = rect.top >= 78 && rect.bottom <= window.innerHeight - 44 && rect.left >= 0 && rect.right <= window.innerWidth;
        if (!isFixed && !mostlyVisible && typeof target.scrollIntoView === 'function') {
            target.scrollIntoView({ behavior: 'auto', block: 'center', inline: 'nearest' });
        }
        waitForLayout(callback, 60);
    }

    function showStep(index) {
        var result = getExistingStep(index, index < currentIndex ? -1 : 1);
        if (!result) {
            if (index < currentIndex) showWelcome();
            else showFinish();
            return;
        }

        var token = ++transitionToken;
        currentIndex = result.index;
        activateBody();
        setTransitioning(true);
        cleanupActiveDemo();
        activeTarget = result.target;

        var container = getRoot();
        var displayNumber = currentIndex + 1;
        var total = visibleSteps.length;
        var previousDisabled = currentIndex === 0 ? ' disabled' : '';
        var nextLabel = currentIndex === visibleSteps.length - 1 ? 'Concluir Tour' : 'Próximo';
        var liveBadge = isLiveDemo(result.step.key)
            ? '<span class="smtt-tour-live-badge"><i></i>Demonstração ao vivo</span>'
            : '<span class="smtt-tour-live-badge smtt-tour-live-badge--info">Conheça o recurso</span>';

        container.innerHTML = '<div class="smtt-tour-stage" aria-hidden="true"></div>' +
            '<div class="smtt-tour-spotlight" data-tour-spotlight aria-hidden="true"></div>' +
            '<section class="smtt-tour-coachmark" data-tour-coachmark role="dialog" aria-modal="true" aria-labelledby="smttTourStepTitle">' +
                '<button type="button" class="smtt-tour-close" data-tour-dismiss aria-label="Fechar Tour">' + closeIconSvg() + '</button>' +
                '<div class="smtt-tour-progress-row"><span>Tour da Intranet V2</span><strong>' + displayNumber + ' / ' + total + '</strong></div>' +
                '<div class="smtt-tour-progress"><i style="width:' + Math.round((displayNumber / total) * 100) + '%"></i></div>' +
                '<div class="smtt-tour-step-heading">' +
                    '<span class="smtt-tour-step-icon">' + iconSvg(result.step.icon) + '</span>' +
                    '<div><div class="smtt-tour-step-meta"><span class="smtt-tour-eyebrow">' + escapeHtml(result.step.eyebrow || 'Tour da Intranet') + '</span>' + liveBadge + '</div>' +
                    '<h3 id="smttTourStepTitle">' + escapeHtml(result.step.title) + '</h3></div>' +
                '</div>' +
                '<p class="smtt-tour-step-text">' + escapeHtml(result.step.text) + '</p>' +
                '<div class="smtt-tour-coach-actions">' +
                    '<button type="button" class="smtt-tour-secondary" data-tour-prev' + previousDisabled + '><span aria-hidden="true">←</span> Anterior</button>' +
                    '<button type="button" class="smtt-tour-primary" data-tour-next>' + nextLabel + ' <span aria-hidden="true">→</span></button>' +
                '</div>' +
                '<div class="smtt-tour-step-footer"><button type="button" class="smtt-tour-skip" data-tour-dismiss>Pular Tour</button><span>Você também pode usar ← →</span></div>' +
            '</section>';

        container.querySelectorAll('[data-tour-dismiss]').forEach(function (button) {
            button.addEventListener('click', dismiss);
        });

        var previous = container.querySelector('[data-tour-prev]');
        if (previous) previous.addEventListener('click', function () {
            if (currentIndex > 0) showStep(currentIndex - 1);
        });

        container.querySelector('[data-tour-next]').addEventListener('click', function () {
            if (currentIndex >= visibleSteps.length - 1) showFinish();
            else showStep(currentIndex + 1);
        });

        scrollTargetIntoPlace(result.target, function () {
            if (token !== transitionToken) return;
            activeTarget = prepareStep(result.step, result.target) || result.target;
            var demoDelay = isLiveDemo(result.step.key) ? 190 : 20;
            waitForLayout(function () { revealCurrentStep(token); }, demoDelay);
        });
    }

    function scheduleReposition() {
        if (repositionTimer) window.cancelAnimationFrame(repositionTimer);
        repositionTimer = window.requestAnimationFrame(positionCurrent);
    }

    function positionCurrent() {
        if (!activeTarget || !root) return;

        var spotlight = root.querySelector('[data-tour-spotlight]');
        var coachmark = root.querySelector('[data-tour-coachmark]');
        if (!spotlight || !coachmark) return;

        var rect = activeTarget.getBoundingClientRect();
        if (rect.width <= 0 || rect.height <= 0) return;

        var pad = window.innerWidth < 640 ? 7 : 11;
        var left = Math.max(8, rect.left - pad);
        var top = Math.max(8, rect.top - pad);
        var width = Math.min(window.innerWidth - left - 8, rect.width + pad * 2);
        var height = Math.min(window.innerHeight - top - 8, rect.height + pad * 2);

        spotlight.style.left = Math.round(left) + 'px';
        spotlight.style.top = Math.round(top) + 'px';
        spotlight.style.width = Math.max(26, Math.round(width)) + 'px';
        spotlight.style.height = Math.max(26, Math.round(height)) + 'px';

        var coachWidth = Math.min(420, window.innerWidth - 28);
        coachmark.style.width = coachWidth + 'px';

        var coachHeight = coachmark.offsetHeight || 330;
        var gap = 18;
        var edge = 14;
        var coachLeft;
        var coachTop;
        var placement = 'center';
        var roomRight = window.innerWidth - rect.right;
        var roomLeft = rect.left;
        var roomBelow = window.innerHeight - rect.bottom;
        var roomAbove = rect.top;

        if (roomRight >= coachWidth + gap + edge && window.innerWidth > 920) {
            coachLeft = rect.right + gap;
            coachTop = Math.max(edge, Math.min(rect.top, window.innerHeight - coachHeight - edge));
            placement = 'right';
        } else if (roomLeft >= coachWidth + gap + edge && window.innerWidth > 920) {
            coachLeft = rect.left - coachWidth - gap;
            coachTop = Math.max(edge, Math.min(rect.top, window.innerHeight - coachHeight - edge));
            placement = 'left';
        } else if (roomBelow >= coachHeight + gap + edge) {
            coachLeft = Math.max(edge, Math.min(rect.left + (rect.width / 2) - (coachWidth / 2), window.innerWidth - coachWidth - edge));
            coachTop = rect.bottom + gap;
            placement = 'bottom';
        } else if (roomAbove >= coachHeight + gap + edge) {
            coachLeft = Math.max(edge, Math.min(rect.left + (rect.width / 2) - (coachWidth / 2), window.innerWidth - coachWidth - edge));
            coachTop = rect.top - coachHeight - gap;
            placement = 'top';
        } else {
            coachLeft = Math.max(edge, (window.innerWidth - coachWidth) / 2);
            coachTop = Math.max(edge, (window.innerHeight - coachHeight) / 2);
            placement = 'center';
        }

        coachmark.setAttribute('data-placement', placement);
        coachmark.style.left = Math.round(coachLeft) + 'px';
        coachmark.style.top = Math.round(coachTop) + 'px';
    }

    function showFinish() {
        ++transitionToken;
        cleanupActiveDemo();
        currentIndex = visibleSteps.length;
        activeTarget = null;
        activateBody();

        var container = getRoot();
        container.classList.remove('is-transitioning');
        container.innerHTML = '<div class="smtt-tour-backdrop"></div>' +
            '<section class="smtt-tour-finish" role="dialog" aria-modal="true" aria-labelledby="smttTourFinishTitle">' +
                '<div class="smtt-tour-finish-icon" aria-hidden="true">✓</div>' +
                '<span class="smtt-tour-eyebrow">TOUR CONCLUÍDO</span>' +
                '<h2 id="smttTourFinishTitle">' + escapeHtml(config.finishTitle) + '</h2>' +
                '<p>' + escapeHtml(config.finishText) + '</p>' +
                '<div class="smtt-tour-finish-summary"><span>✓ Acessos</span><span>✓ Comunicação</span><span>✓ Informação</span><span>✓ Pessoas</span></div>' +
                '<button type="button" class="smtt-tour-primary" data-tour-finish>Entrar na Intranet</button>' +
            '</section>';

        container.querySelector('[data-tour-finish]').addEventListener('click', dismiss);
        focusFirstButton(container.querySelector('.smtt-tour-finish'));
    }

    function onKeydown(event) {
        if (!root) return;

        if (event.key === 'Tab') {
            var focusable = Array.from(root.querySelectorAll('button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'));
            if (focusable.length) {
                var first = focusable[0];
                var last = focusable[focusable.length - 1];
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            dismiss();
            return;
        }

        if (currentIndex >= 0 && currentIndex < visibleSteps.length) {
            if (event.key === 'ArrowRight') {
                event.preventDefault();
                if (currentIndex >= visibleSteps.length - 1) showFinish();
                else showStep(currentIndex + 1);
            }

            if (event.key === 'ArrowLeft' && currentIndex > 0) {
                event.preventDefault();
                showStep(currentIndex - 1);
            }
        }
    }

    function neutralizePreviewUrl() {
        if (!config.preview || !window.history || typeof window.history.replaceState !== 'function') return;

        try {
            var url = new URL(window.location.href);
            if (!url.searchParams.has('smtt_tour_preview')) return;
            url.searchParams.delete('smtt_tour_preview');
            window.history.replaceState(window.history.state, document.title, url.pathname + (url.search ? url.search : '') + url.hash);
        } catch (error) {
            /* Não interfere no Tour se o navegador não aceitar a alteração da URL. */
        }
    }

    function init() {
        document.addEventListener('keydown', onKeydown);
        window.addEventListener('resize', scheduleReposition);
        window.addEventListener('scroll', scheduleReposition, true);

        neutralizePreviewUrl();
        createReplayButton();

        var localSeen = hasLocalSeen();

        if (config.autoStart && !localSeen) {
            window.setTimeout(showWelcome, Math.max(0, parseInt(config.delay || 0, 10)));
            return;
        }

        /*
         * Se o navegador lembra que a rodada já foi vista mas o servidor ainda
         * não recebeu a gravação (por exemplo, a aba foi fechada imediatamente),
         * sincronizamos em silêncio e evitamos reapresentar o Tour.
         */
        if (config.autoStart && localSeen) {
            markViewed();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
