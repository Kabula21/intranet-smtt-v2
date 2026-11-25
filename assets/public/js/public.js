/* SMTT v35: acesso defensivo ao storage. Políticas de privacidade não podem interromper a inicialização do front. */
function smttStorageGet(key) {
    try { return window.localStorage ? window.localStorage.getItem(key) : null; } catch (error) { return null; }
}

function smttStorageSet(key, value) {
    try { if (window.localStorage) window.localStorage.setItem(key, value); } catch (error) { /* storage indisponível: mantém estado apenas na sessão atual */ }
}

document.addEventListener('DOMContentLoaded', function () {

    if (document.querySelector('.smtt-app')) {
        document.body.classList.add('smtt-intranet-page');
    }

    /* =========================
       THEMAS
    ========================== */
const themeToggle = document.getElementById('smttThemeToggle');
const savedTheme = smttStorageGet('smtt-theme');

if (savedTheme === 'dark') {
    document.body.classList.add('smtt-dark-mode');
}
document.documentElement.classList.remove('smtt-theme-dark-preload');

if (themeToggle) {
    themeToggle.addEventListener('click', function () {
        document.body.classList.toggle('smtt-dark-mode');

        if (document.body.classList.contains('smtt-dark-mode')) {
            smttStorageSet('smtt-theme', 'dark');
        } else {
            smttStorageSet('smtt-theme', 'light');
        }
    });
}


/* =========================
   SIDEBAR RECOLHÍVEL
========================== */
const sidebarToggle = document.getElementById('smttSidebarToggle');
const savedSidebarState = smttStorageGet('smtt-sidebar');

if (savedSidebarState === 'collapsed') {
    document.body.classList.add('smtt-sidebar-collapsed');

    if (sidebarToggle) {
        sidebarToggle.setAttribute('aria-expanded', 'false');
        sidebarToggle.setAttribute('aria-label', 'Expandir menu lateral');
    }
}

if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function () {
        document.body.classList.toggle('smtt-sidebar-collapsed');

        const isCollapsed = document.body.classList.contains('smtt-sidebar-collapsed');

        smttStorageSet('smtt-sidebar', isCollapsed ? 'collapsed' : 'expanded');

        sidebarToggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
        sidebarToggle.setAttribute('aria-label', isCollapsed ? 'Expandir menu lateral' : 'Recolher menu lateral');
    });
}

/* =========================
   TOOLTIP BONITA DA SIDEBAR RECOLHIDA
========================== */
const sidebarTooltipItems = document.querySelectorAll(
    [
        '.smtt-sidebar-nav a',
        '.smtt-sidebar-links a',
        '.smtt-side-action',
        '.smtt-side-accordion-toggle',
        '.smtt-side-subitem',
        '.smtt-links-toggle'
    ].join(',')
);

let sidebarTooltip = document.querySelector('.smtt-sidebar-floating-tooltip');

if (!sidebarTooltip) {
    sidebarTooltip = document.createElement('div');
    sidebarTooltip.className = 'smtt-sidebar-floating-tooltip';
    document.body.appendChild(sidebarTooltip);
}

function getSidebarTooltipText(item) {
    if (!item) return '';

    const currentTitle = item.getAttribute('title');
    const currentAria = item.getAttribute('aria-label');
    const dataTooltip = item.getAttribute('data-tooltip');
    const text = item.textContent;

    return String(dataTooltip || currentAria || currentTitle || text || '')
        .replace(/\s+/g, ' ')
        .trim();
}

function hideSidebarTooltip() {
    if (!sidebarTooltip) return;

    sidebarTooltip.classList.remove('is-visible');
}

function showSidebarTooltip(item) {
    if (!document.body.classList.contains('smtt-sidebar-collapsed')) {
        hideSidebarTooltip();
        return;
    }

    const label = getSidebarTooltipText(item);

    if (!label) {
        hideSidebarTooltip();
        return;
    }

    const rect = item.getBoundingClientRect();

    sidebarTooltip.textContent = label;
    sidebarTooltip.style.left = (rect.right + 12) + 'px';
    sidebarTooltip.style.top = (rect.top + rect.height / 2) + 'px';
    sidebarTooltip.classList.add('is-visible');
}

sidebarTooltipItems.forEach(function (item) {
    const label = getSidebarTooltipText(item);

    if (!label) return;

    item.setAttribute('data-tooltip', label);
    item.setAttribute('aria-label', label);

    /*
       Remove o title para não aparecer a tooltip padrão do navegador.
       A tooltip visual agora é a nossa.
    */
    if (item.hasAttribute('title')) {
        item.removeAttribute('title');
    }

    item.addEventListener('mouseenter', function () {
        showSidebarTooltip(item);
    });

    item.addEventListener('focus', function () {
        showSidebarTooltip(item);
    });

    item.addEventListener('mouseleave', hideSidebarTooltip);
    item.addEventListener('blur', hideSidebarTooltip);
});

window.addEventListener('scroll', hideSidebarTooltip, true);
window.addEventListener('resize', hideSidebarTooltip);

    /* =========================
       MENU DE APLICATIVOS
    ========================== */
    const appsWrap = document.querySelector('.smtt-topbar-apps');
    const appsToggle = document.getElementById('smttAppsToggle');

    if (appsWrap && appsToggle) {
        appsToggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            appsWrap.classList.toggle('is-open');
            const expanded = appsWrap.classList.contains('is-open');
            appsToggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });

        document.addEventListener('click', function (e) {
            if (!appsWrap.contains(e.target)) {
                appsWrap.classList.remove('is-open');
                appsToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    /* =========================
   SCROLL INTERNO DOS CARDS + DESTAQUE VISUAL
========================== */
const scrollContainer = document.querySelector('.smtt-main-scroll');
const sidebarLinks = document.querySelectorAll('.smtt-sidebar-nav a[href^="#"]');

const highlightedCardIds = [
    'agenda',
    'aniversariantes',
    'base-interna',
    'documentos',
    'mural',
    'comunicados'
];

function clearCardHighlights() {
    highlightedCardIds.forEach(function (id) {
        const card = document.getElementById(id);

        if (!card) return;

        card.classList.remove('smtt-card--nav-highlight');
        card.classList.remove('smtt-card--nav-pulse');
    });
}

function highlightCard(target) {
    if (!target || !target.classList.contains('smtt-card')) return;

    clearCardHighlights();

    target.classList.add('smtt-card--nav-highlight');

    // Reinicia a animação mesmo clicando no mesmo item novamente
    target.classList.remove('smtt-card--nav-pulse');

    void target.offsetWidth;

    target.classList.add('smtt-card--nav-pulse');

    setTimeout(function () {
        target.classList.remove('smtt-card--nav-pulse');
    }, 900);
}

if (scrollContainer && sidebarLinks.length) {
    sidebarLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
            const hash = this.getAttribute('href');

            if (!hash || hash === '#') return;

            const target = document.querySelector(hash);
            if (!target) return;

            e.preventDefault();

            const containerTop = scrollContainer.getBoundingClientRect().top;
            const targetTop = target.getBoundingClientRect().top;
            const currentScroll = scrollContainer.scrollTop;
            const offset = 8;

            scrollContainer.scrollTo({
                top: currentScroll + (targetTop - containerTop) - offset,
                behavior: 'smooth'
            });

            document.querySelectorAll('.smtt-sidebar-nav a').forEach(function (item) {
                item.classList.remove('is-active');
            });

            link.classList.add('is-active');

            highlightCard(target);
        });
    });
}

    /* =========================
   ACCORDION DA SIDEBAR
========================== */
const sideAccordions = document.querySelectorAll('.smtt-side-accordion-toggle');

sideAccordions.forEach(function (toggle) {
    toggle.addEventListener('click', function () {
        const currentWrapper = this.closest('.smtt-side-accordion');

        if (!currentWrapper) {
            return;
        }

        const isAlreadyOpen = currentWrapper.classList.contains('is-open');

        document.querySelectorAll('.smtt-side-accordion.is-open').forEach(function (openedWrapper) {
            if (openedWrapper !== currentWrapper) {
                openedWrapper.classList.remove('is-open');
            }
        });

        currentWrapper.classList.toggle('is-open', !isAlreadyOpen);
    });
});

    /* =========================
   CENTRAL DE ACESSOS
========================== */
const accessCenter = document.getElementById('smttAccessCenter');
const accessBackdrop = document.getElementById('smttAccessCenterBackdrop');
const accessOpenBtns = document.querySelectorAll('.smtt-open-access-center');
const accessCloseBtn = document.getElementById('smttAccessCenterClose');

const sectorStage = document.getElementById('smttAccessSectorStage');
const systemsStage = document.getElementById('smttAccessSystemsStage');
const sectorButtons = document.querySelectorAll('.smtt-access-sector');
const sectorPanels = document.querySelectorAll('.smtt-access-systems-panel');
const sectorTitle = document.getElementById('smttAccessSystemsTitle');
const sectorSubtitle = document.getElementById('smttAccessSystemsSubtitle');
const accessBackBtn = document.getElementById('smttAccessBackToSectors');
const accessHeaderText = document.getElementById('smttAccessHeaderText');

function getSectorLabel(button) {
    if (!button) {
        return 'setor';
    }

    const dataName = button.getAttribute('data-sector-name');

    if (dataName) {
        return dataName.trim();
    }

    return String(button.textContent || '')
        .replace(/\s+/g, ' ')
        .trim();
}

function resetAccessCenterState() {
    if (sectorStage) {
        sectorStage.classList.remove('is-leaving');
        sectorStage.classList.add('is-active');
    }

    if (systemsStage) {
        systemsStage.classList.remove('is-active', 'is-leaving');
    }

    sectorButtons.forEach(function (btn) {
        btn.classList.remove('is-active');
    });

    sectorPanels.forEach(function (panel) {
        panel.classList.remove('is-active');
    });

    if (accessHeaderText) {
        accessHeaderText.textContent = 'Escolha um setor ou acesse diretamente um sistema';
    }

    if (sectorTitle) {
        sectorTitle.textContent = 'Acessos do setor';
    }

    if (sectorSubtitle) {
        sectorSubtitle.textContent = 'Selecione um sistema ou pasta para acessar';
    }
}

function openAccessCenter() {
    if (!accessCenter || !accessBackdrop) {
        return;
    }

    resetAccessCenterState();

    accessCenter.classList.add('is-open');
    accessBackdrop.classList.add('is-open');
    document.body.classList.add('smtt-overlay-open');
}

function closeAccessCenter() {
    if (!accessCenter || !accessBackdrop) {
        return;
    }

    accessCenter.classList.remove('is-open');
    accessBackdrop.classList.remove('is-open');
    document.body.classList.remove('smtt-overlay-open');

    setTimeout(function () {
        resetAccessCenterState();
    }, 180);
}

if (accessOpenBtns.length) {
    accessOpenBtns.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            openAccessCenter();
        });
    });
}

if (accessCloseBtn) {
    accessCloseBtn.addEventListener('click', function (e) {
        e.preventDefault();
        closeAccessCenter();
    });
}

if (accessBackdrop) {
    accessBackdrop.addEventListener('click', function () {
        closeAccessCenter();
    });
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeAccessCenter();
    }
});

/* =========================
   TROCA DE TELA: SETORES -> ACESSOS
========================== */
sectorButtons.forEach(function (button) {
    button.addEventListener('click', function () {
        const sector = this.getAttribute('data-sector');

        if (!sector || !sectorStage || !systemsStage) {
            return;
        }

        const sectorLabel = getSectorLabel(this);

        sectorButtons.forEach(function (btn) {
            btn.classList.remove('is-active');
        });

        this.classList.add('is-active');

        sectorPanels.forEach(function (panel) {
            panel.classList.remove('is-active');
        });

        const activePanel = document.querySelector('[data-sector-panel="' + sector + '"]');

        if (activePanel) {
            activePanel.classList.add('is-active');
        }

        if (sectorTitle) {
            sectorTitle.textContent = 'Acessos - ' + sectorLabel;
        }

        if (sectorSubtitle) {
            sectorSubtitle.textContent = 'Sistemas e pastas vinculados a este setor';
        }

        if (accessHeaderText) {
            accessHeaderText.textContent = 'Selecione um acesso do setor';
        }

        sectorStage.classList.add('is-leaving');

        setTimeout(function () {
            sectorStage.classList.remove('is-active', 'is-leaving');
            systemsStage.classList.add('is-active');
        }, 180);
    });
});

/* =========================
   VOLTAR PARA SETORES
========================== */
if (accessBackBtn) {
    accessBackBtn.addEventListener('click', function (e) {
        e.preventDefault();

        if (!sectorStage || !systemsStage) {
            return;
        }

        systemsStage.classList.add('is-leaving');

        setTimeout(function () {
            systemsStage.classList.remove('is-active', 'is-leaving');
            sectorStage.classList.add('is-active');

            if (accessHeaderText) {
                accessHeaderText.textContent = 'Escolha um setor ou acesse diretamente um sistema';
            }

            if (sectorTitle) {
                sectorTitle.textContent = 'Acessos do setor';
            }

            if (sectorSubtitle) {
                sectorSubtitle.textContent = 'Selecione um sistema ou pasta para acessar';
            }

            sectorButtons.forEach(function (btn) {
                btn.classList.remove('is-active');
            });

            sectorPanels.forEach(function (panel) {
                panel.classList.remove('is-active');
            });
        }, 180);
    });
}

/* =========================
   CLIQUE NO ACESSO FECHA O BALÃO
========================== */
document.querySelectorAll('.smtt-access-mini-card').forEach(function (card) {
    card.addEventListener('click', function (e) {
        const href = card.getAttribute('href');

        if (!href || href === '#') {
            e.preventDefault();
            return;
        }

        closeAccessCenter();
    });
});
});

/* ========================================================================== 
   SMTT Intranet — navegação moderna v6
   Somente comportamento de apresentação; módulos e endpoints permanecem iguais.
   ========================================================================== */
document.addEventListener('DOMContentLoaded', function () {
    const scrollArea = document.querySelector('.smtt-main-scroll');
    const navScrollButtons = document.querySelectorAll('.smtt-topbar-nav-item[data-smtt-scroll]');
    const systemsToggle = document.getElementById('smttSystemsToggle');
    const systemsDropdown = document.getElementById('smttSystemsDropdown');
    const documentsToggle = document.getElementById('smttDocumentsToggle');
    const documentsDropdown = document.getElementById('smttDocumentsDropdown');
    const servicesToggle = document.getElementById('smttServicesToggle');
    const servicesDropdown = document.getElementById('smttServicesDropdown');
    const navGroups = document.querySelectorAll('[data-smtt-nav-group]');

    function setDropdown(group, button, menu, open) {
        if (!group || !button || !menu) return;
        group.classList.toggle('is-open', open);
        menu.hidden = !open;
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function closeNavDropdowns(exceptGroup) {
        navGroups.forEach(function (group) {
            if (exceptGroup && group === exceptGroup) return;
            const button = group.querySelector('.smtt-topbar-nav-item[aria-controls]');
            const menu = button ? document.getElementById(button.getAttribute('aria-controls')) : null;
            setDropdown(group, button, menu, false);
        });
    }

    function bindDropdownToggle(button, menu) {
        if (!button || !menu) return;
        const group = button.closest('[data-smtt-nav-group]');
        if (!group) return;

        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            const open = !group.classList.contains('is-open');
            document.querySelectorAll('.smtt-topbar-nav-item').forEach(function (item) {
                item.classList.remove('is-active');
            });
            button.classList.add('is-active');
            closeNavDropdowns(group);
            setDropdown(group, button, menu, open);
        });
    }

    bindDropdownToggle(systemsToggle, systemsDropdown);
    bindDropdownToggle(documentsToggle, documentsDropdown);
    bindDropdownToggle(servicesToggle, servicesDropdown);

    // Desktop: abre os menus principais ao passar o mouse. O clique continua
    // funcionando como fallback para teclado, touch e acessibilidade.
    const canHoverNav = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    const navHoverTimers = new WeakMap();

    if (canHoverNav) {
        navGroups.forEach(function (group) {
            const button = group.querySelector('.smtt-topbar-nav-item[aria-controls]');
            const menu = button ? document.getElementById(button.getAttribute('aria-controls')) : null;
            if (!button || !menu) return;

            group.addEventListener('mouseenter', function () {
                const pendingTimer = navHoverTimers.get(group);
                if (pendingTimer) {
                    window.clearTimeout(pendingTimer);
                    navHoverTimers.delete(group);
                }

                closeNavDropdowns(group);
                setDropdown(group, button, menu, true);
            });

            group.addEventListener('mouseleave', function () {
                const timer = window.setTimeout(function () {
                    setDropdown(group, button, menu, false);
                    navHoverTimers.delete(group);
                }, 140);
                navHoverTimers.set(group, timer);
            });
        });
    }

    document.addEventListener('click', function (event) {
        let inside = false;
        navGroups.forEach(function (group) {
            if (group.contains(event.target)) inside = true;
        });
        if (!inside) closeNavDropdowns();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeNavDropdowns();
    });

    document.querySelectorAll('[data-smtt-service-root]').forEach(function (item) {
        const button = item.querySelector('.smtt-service-root-button');
        if (!button) return;

        button.addEventListener('click', function (event) {
            event.preventDefault();
            document.querySelectorAll('[data-smtt-service-root]').forEach(function (other) {
                if (other !== item) other.classList.remove('is-open');
            });
            item.classList.toggle('is-open');
        });
    });

    document.querySelectorAll('.smtt-nav-dropdown-link[href="#"]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            event.preventDefault();
        });
    });

    document.querySelectorAll('.smtt-nav-dropdown-footer').forEach(function (button) {
        button.addEventListener('click', function () {
            closeNavDropdowns();
        });
    });

    function scrollToSection(selector) {
        if (!selector) return;

        if (selector === '#inicio') {
            if (scrollArea) {
                scrollArea.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
            return;
        }

        const target = document.querySelector(selector);
        if (!target) return;

        if (scrollArea && scrollArea.scrollHeight > scrollArea.clientHeight + 4) {
            const areaRect = scrollArea.getBoundingClientRect();
            const targetRect = target.getBoundingClientRect();
            const targetTop = scrollArea.scrollTop + targetRect.top - areaRect.top - 8;
            scrollArea.scrollTo({ top: Math.max(0, targetTop), behavior: 'smooth' });
        } else {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        target.classList.remove('smtt-card--nav-pulse');
        void target.offsetWidth;
        target.classList.add('smtt-card--nav-pulse');
        window.setTimeout(function () {
            target.classList.remove('smtt-card--nav-pulse');
        }, 900);
    }

    navScrollButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            closeNavDropdowns();
            navScrollButtons.forEach(function (other) {
                other.classList.remove('is-active');
            });
            button.classList.add('is-active');
            scrollToSection(button.getAttribute('data-smtt-scroll'));
        });
    });
});

/* ========================================================================== 
   SMTT Intranet — Busca global do banner
   Exibe resultados na própria home; cada resultado abre o conteúdo correto.
   ========================================================================== */
document.addEventListener('DOMContentLoaded', function () {
    const heroInput = document.getElementById('smttHeroSearchInput');
    const heroButton = document.getElementById('smttHeroSearchButton');
    const heroResults = document.getElementById('smttHeroSearchResults');
    const mainScroll = document.querySelector('.smtt-main-scroll');

    function normalizeSearchText(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function scrollToSection(selector) {
        if (!selector) return;
        const target = document.querySelector(selector);
        if (!target) return;

        if (mainScroll && mainScroll.scrollHeight > mainScroll.clientHeight) {
            const containerTop = mainScroll.getBoundingClientRect().top;
            const targetTop = target.getBoundingClientRect().top;
            const currentScroll = mainScroll.scrollTop;
            mainScroll.scrollTo({
                top: Math.max(0, currentScroll + (targetTop - containerTop) - 12),
                behavior: 'smooth'
            });
        } else {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function openRamais(query) {
        const ramaisButton = document.querySelector('[data-smtt-ramais-open]');
        if (!ramaisButton) return;

        const root = document.querySelector('[data-smtt-ramais-root]');
        if (!root || !root.classList.contains('is-open')) {
            ramaisButton.click();
        }

        const ramaisSearch = document.querySelector('[data-smtt-ramais-search]');
        if (ramaisSearch && query) {
            ramaisSearch.value = query;
            ramaisSearch.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    function buildSearchIndex() {
        const index = [];

        document.querySelectorAll('#smttDocumentsDropdown .smtt-nav-document-link').forEach(function (item) {
            const title = item.querySelector('.smtt-nav-document-copy strong');
            const desc = item.querySelector('.smtt-nav-document-copy small');
            index.push({
                type: 'Documento',
                title: title ? title.textContent.trim() : item.textContent.trim(),
                description: desc ? desc.textContent.trim() : 'Arquivo interno',
                keywords: item.getAttribute('data-document-search') || item.textContent,
                href: item.getAttribute('href') || '',
                element: item
            });
        });

        document.querySelectorAll('#smttWikiList .smtt-wiki-article').forEach(function (item) {
            const title = item.querySelector('.smtt-wiki-article-title');
            const desc = item.querySelector('.smtt-wiki-article-desc');
            index.push({
                type: 'Wiki',
                title: title ? title.textContent.trim() : item.textContent.trim(),
                description: desc ? desc.textContent.trim() : 'Procedimento ou conhecimento interno',
                keywords: item.textContent,
                href: item.getAttribute('href') || '',
                element: item
            });
        });

        const seenSystems = new Set();
        document.querySelectorAll('#smttSystemsDropdown .smtt-nav-dropdown-link').forEach(function (item) {
            const title = item.textContent.replace(/\s+/g, ' ').trim();
            const href = item.getAttribute('href') || '';
            const key = normalizeSearchText(title + '|' + href);
            if (!title || seenSystems.has(key)) return;
            seenSystems.add(key);
            index.push({
                type: 'Sistema',
                title: title,
                description: 'Sistema institucional',
                keywords: title,
                href: href,
                element: item
            });
        });

        const seenDepartments = new Set();
        document.querySelectorAll('#smttServicesDropdown .smtt-nav-dropdown-link').forEach(function (item) {
            const title = item.textContent.replace(/\s+/g, ' ').trim();
            const href = item.getAttribute('href') || '';
            const key = normalizeSearchText(title + '|' + href);
            if (!title || seenDepartments.has(key)) return;
            seenDepartments.add(key);
            index.push({
                type: 'Departamento',
                title: title,
                description: 'Pasta ou acesso departamental',
                keywords: title + ' departamento setor pasta',
                href: href,
                element: item
            });
        });

        document.querySelectorAll('#central-acessos-card [data-smtt-sector-access]').forEach(function (item) {
            const name = item.getAttribute('data-sector-name') || item.textContent.replace(/\s+/g, ' ').trim();
            if (!name) return;
            index.push({
                type: 'Setor',
                title: name,
                description: 'Central de Acessos',
                keywords: name + ' setor central acessos',
                element: item,
                section: '#central-acessos-card'
            });
        });

        document.querySelectorAll('#links-uteis-card .smtt-useful-link-item').forEach(function (item) {
            const titleEl = item.querySelector('span');
            const title = titleEl ? titleEl.textContent.trim() : item.textContent.replace(/\s+/g, ' ').trim();
            if (!title) return;
            index.push({
                type: 'Link útil',
                title: title,
                description: 'Portal ou serviço de uso frequente',
                keywords: title + ' link portal servico serviço',
                href: item.getAttribute('href') || '',
                element: item
            });
        });

        document.querySelectorAll('#comunicados .smtt-news-card-inline').forEach(function (item) {
            const title = item.querySelector('h4');
            const desc = item.querySelector('p');
            const link = item.querySelector('a[href]');
            index.push({
                type: 'Notícia',
                title: title ? title.textContent.trim() : item.textContent.trim(),
                description: desc ? desc.textContent.trim() : 'Notícia institucional',
                keywords: item.textContent,
                href: link ? link.getAttribute('href') : '',
                element: item
            });
        });

        document.querySelectorAll('#aniversariantes .smtt-bday-person').forEach(function (item) {
            const name = item.getAttribute('data-name') || '';
            const sector = item.getAttribute('data-sector') || '';
            const role = item.getAttribute('data-role') || '';
            index.push({
                type: 'Pessoa',
                title: name,
                description: [sector, role].filter(Boolean).join(' — ') || 'Colaborador',
                keywords: [name, sector, role, item.getAttribute('data-date') || ''].join(' '),
                element: item,
                section: '#aniversariantes'
            });
        });

        index.push(
            { type: 'Agenda', title: 'Agenda', description: 'Reservas de salas, auditório e reuniões', keywords: 'agenda reuniao reunião reunioes reuniões sala salas auditorio auditório reserva reservas', section: '#agenda' },
            { type: 'Mural', title: 'Mural Interno', description: 'Avisos e destaques internos', keywords: 'mural aviso avisos comunicado comunicados destaque destaques', section: '#mural' }
        );

        return index;
    }

    const searchIndex = buildSearchIndex();

    function scoreResult(item, query) {
        const title = normalizeSearchText(item.title);
        const description = normalizeSearchText(item.description);
        const keywords = normalizeSearchText(item.keywords);
        const typeBoosts = {
            'Documento': 18,
            'Wiki': 18,
            'Sistema': 14,
            'Departamento': 12,
            'Setor': 12,
            'Link útil': 10,
            'Agenda': 9,
            'Mural': 9,
            'Notícia': 7,
            'Pessoa': 6
        };
        let score = 0;
        if (title === query) score = 120;
        else if (title.startsWith(query)) score = 100;
        else if (title.includes(query)) score = 78;
        else if (description.includes(query)) score = 46;
        else if (keywords.includes(query)) score = 30;
        if (!score) return 0;
        return score + (typeBoosts[item.type] || 0);
    }

    function escapeSearchHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function closeHeroResults() {
        if (!heroResults) return;
        heroResults.hidden = true;
        heroResults.innerHTML = '';
        document.body.classList.remove('smtt-hero-search-open');
    }

    function renderHeroResults(rawQuery) {
        if (!heroResults || !heroInput) return;
        const query = normalizeSearchText(rawQuery);

        if (!query) {
            closeHeroResults();
            return;
        }

        const ranked = searchIndex
            .map(function (item) { return { item: item, score: scoreResult(item, query) }; })
            .filter(function (entry) { return entry.score > 0; })
            .sort(function (a, b) { return b.score - a.score; });

        const groupOrder = ['Documento', 'Wiki', 'Sistema', 'Departamento', 'Setor', 'Link útil', 'Agenda', 'Mural', 'Notícia', 'Pessoa'];
        const grouped = {};
        ranked.forEach(function (entry) {
            const type = entry.item.type || 'Outros';
            if (!grouped[type]) grouped[type] = [];
            if (grouped[type].length < 4) grouped[type].push(entry.item);
        });

        const ramalIntent = /\b(ramal|ramais|telefone|telefones|fone|fones|extensao|extensoes)\b/.test(query) || /^\d{3,5}$/.test(query);
        if (ramalIntent) {
            grouped.Ramais = [{
                type: 'Ramais',
                title: 'Pesquisar nos Ramais Internos',
                description: 'Filtrar por setor, função ou número: “' + rawQuery.trim() + '”',
                ramais: true
            }];
        }

        const orderedTypes = groupOrder.filter(function (type) { return grouped[type] && grouped[type].length; });
        if (grouped.Ramais) orderedTypes.push('Ramais');

        if (!orderedTypes.length) {
            heroResults.innerHTML = '<div class="smtt-hero-search-results-head"><div><strong>Busca global</strong><small>Documentos, Wiki, sistemas, setores, links, notícias e pessoas</small></div></div>' +
                '<div class="smtt-hero-search-empty">Nenhum resultado encontrado para <strong>“' + escapeSearchHtml(rawQuery.trim()) + '”</strong>.</div>';
            heroResults.hidden = false;
            document.body.classList.add('smtt-hero-search-open');
            return;
        }

        const displayResults = [];
        const groupHtml = orderedTypes.map(function (type) {
            const items = grouped[type] || [];
            const buttons = items.map(function (item) {
                const index = displayResults.length;
                displayResults.push(item);
                return '<button type="button" class="smtt-hero-search-result" data-search-result-index="' + index + '">' +
                    '<span class="smtt-hero-search-result-copy"><strong>' + escapeSearchHtml(item.title) + '</strong><small>' + escapeSearchHtml(item.description) + '</small></span>' +
                    '<span class="smtt-hero-search-result-arrow" aria-hidden="true">›</span>' +
                '</button>';
            }).join('');

            return '<section class="smtt-hero-search-group">' +
                '<div class="smtt-hero-search-group-title"><span>' + escapeSearchHtml(type) + '</span><small>' + items.length + '</small></div>' +
                '<div class="smtt-hero-search-group-items">' + buttons + '</div>' +
            '</section>';
        }).join('');

        heroResults.innerHTML = '<div class="smtt-hero-search-results-head">' +
            '<div><strong>Busca global</strong><small>Resultados em toda a intranet</small></div>' +
            '<span class="smtt-hero-search-results-count">' + displayResults.length + ' resultado' + (displayResults.length === 1 ? '' : 's') + '</span>' +
            '</div>' +
            '<div class="smtt-hero-search-results-body">' + groupHtml + '</div>';

        heroResults.hidden = false;
        document.body.classList.add('smtt-hero-search-open');

        heroResults.querySelectorAll('[data-search-result-index]').forEach(function (button) {
            button.addEventListener('click', function () {
                const item = displayResults[parseInt(button.getAttribute('data-search-result-index'), 10)];
                if (!item) return;
                closeHeroResults();

                if (item.ramais) {
                    openRamais(rawQuery.trim());
                    return;
                }

                if (item.element && item.element.classList && item.element.classList.contains('smtt-restricted-access')) {
                    item.element.click();
                    return;
                }

                if (item.href && item.href !== '#') {
                    window.open(item.href, '_blank', 'noopener');
                    return;
                }

                if (item.section) {
                    scrollToSection(item.section);
                    if (item.element && typeof item.element.click === 'function') {
                        window.setTimeout(function () { item.element.click(); }, 320);
                    }
                    return;
                }

                if (item.element && typeof item.element.click === 'function') {
                    item.element.click();
                }
            });
        });
    }

    if (heroButton && heroInput) {
        heroButton.addEventListener('click', function () {
            renderHeroResults(heroInput.value);
        });
    }

    if (heroInput) {
        heroInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                renderHeroResults(heroInput.value);
            }
            if (event.key === 'Escape') {
                closeHeroResults();
            }
        });

        heroInput.addEventListener('input', function () {
            if (normalizeSearchText(heroInput.value).length >= 2) {
                renderHeroResults(heroInput.value);
            } else {
                closeHeroResults();
            }
        });
    }

    document.addEventListener('click', function (event) {
        const searchWrap = heroInput ? heroInput.closest('.smtt-banner-hero-center') : null;
        if (searchWrap && !searchWrap.contains(event.target)) {
            closeHeroResults();
        }
    });

    const navDocumentSearch = document.getElementById('smttNavDocumentSearch');
    const navDocumentItems = document.querySelectorAll('#smttNavDocumentList .smtt-nav-document-link');
    if (navDocumentSearch && navDocumentItems.length) {
        navDocumentSearch.addEventListener('input', function () {
            const query = normalizeSearchText(navDocumentSearch.value);
            navDocumentItems.forEach(function (item) {
                const haystack = normalizeSearchText(item.getAttribute('data-document-search') || item.textContent);
                item.hidden = Boolean(query && !haystack.includes(query));
            });
        });
    }
});


/* ========================================================================== 
   SMTT Intranet — Permissões por setor / Central de Acessos
   Mantém o layout v21 e adiciona apenas a lógica de autorização.
   ========================================================================== */
document.addEventListener('DOMContentLoaded', function () {
    const toast = document.getElementById('smttAccessToast');
    let toastTimer = null;

    function showAccessMessage(message) {
        if (!toast) return;
        toast.textContent = message || 'Você não possui permissão para este acesso.';
        toast.hidden = false;
        toast.classList.add('is-visible');
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(function () {
            toast.classList.remove('is-visible');
            window.setTimeout(function () { toast.hidden = true; }, 180);
        }, 4200);
    }

    document.querySelectorAll('.smtt-restricted-access').forEach(function (link) {
        link.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            showAccessMessage(link.getAttribute('data-access-message'));
        });
    });

    const modal = document.getElementById('smttSectorAccessModal');
    const backdrop = document.getElementById('smttSectorAccessBackdrop');
    const closeButton = document.getElementById('smttSectorAccessClose');
    const title = document.getElementById('smttSectorAccessTitle');
    const subtitle = document.getElementById('smttSectorAccessSubtitle');
    const sectorButtons = document.querySelectorAll('[data-smtt-sector-access]');
    const sectorPanels = document.querySelectorAll('.smtt-access-sector-panel');

    function closeSectorModal() {
        if (!modal || !backdrop) return;
        modal.classList.remove('is-open');
        backdrop.classList.remove('is-open');
        document.body.classList.remove('smtt-access-modal-open');
        window.setTimeout(function () {
            modal.hidden = true;
            backdrop.hidden = true;
            sectorPanels.forEach(function (panel) { panel.hidden = true; });
        }, 160);
    }

    function openSectorModal(button) {
        if (!modal || !backdrop || !button) return;

        if (button.getAttribute('data-access-allowed') !== '1') {
            showAccessMessage(button.getAttribute('data-access-message'));
            return;
        }

        const sectorId = button.getAttribute('data-sector-id');
        const sectorName = button.getAttribute('data-sector-name') || 'Setor';
        let activePanel = null;
        sectorPanels.forEach(function (panel) {
            const active = panel.getAttribute('data-sector-panel') === sectorId;
            panel.hidden = !active;
            if (active) activePanel = panel;
        });

        if (!activePanel) {
            showAccessMessage('Nenhum acesso foi configurado para este setor.');
            return;
        }

        if (title) title.textContent = 'Acessos — ' + sectorName;
        if (subtitle) subtitle.textContent = 'Sistemas e pastas liberados para este setor.';
        modal.hidden = false;
        backdrop.hidden = false;
        document.body.classList.add('smtt-access-modal-open');
        window.requestAnimationFrame(function () {
            modal.classList.add('is-open');
            backdrop.classList.add('is-open');
        });
    }

    sectorButtons.forEach(function (button) {
        button.addEventListener('click', function () { openSectorModal(button); });
    });

    if (closeButton) closeButton.addEventListener('click', closeSectorModal);
    if (backdrop) backdrop.addEventListener('click', closeSectorModal);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal && !modal.hidden) closeSectorModal();
    });
});

/* =========================
   AGENDA v38 — interação instantânea, sem reload/flash
   PHP continua pré-renderizando todo o ano e os links preservam fallback real.
========================== */
document.addEventListener('DOMContentLoaded', function () {
    const root = document.querySelector('[data-smtt-agenda-root]');
    if (!root) return;

    const select = root.querySelector('[data-smtt-agenda-month-select]');
    const calendarScreen = root.querySelector('[data-smtt-agenda-calendar-screen]');
    const detailsScreen = root.querySelector('[data-smtt-agenda-details-screen]');
    const backButton = root.querySelector('[data-smtt-agenda-back]');
    const detailTitle = root.querySelector('[data-smtt-agenda-detail-title]');
    const detailSummary = root.querySelector('[data-smtt-agenda-detail-summary]');
    const monthPanels = Array.from(root.querySelectorAll('[data-smtt-agenda-month-panel]'));
    const detailPanels = Array.from(root.querySelectorAll('[data-smtt-agenda-detail]'));
    const year = parseInt((root.querySelector('[data-smtt-agenda-stage]') || {}).dataset?.year || '', 10);

    if (!select || !calendarScreen || !detailsScreen) return;

    function setPanelVisibility(element, visible) {
        if (!element) return;
        element.hidden = !visible;
        element.classList.toggle('is-active', visible);
    }

    function updateUrl(month, date, mode) {
        if (!window.history || typeof window.history.replaceState !== 'function') return;
        try {
            const url = new URL(window.location.href);
            if (year) url.searchParams.set('smtt_agenda_year', String(year));
            url.searchParams.set('smtt_agenda_month', String(month));
            if (date) url.searchParams.set('smtt_agenda_date', date);
            else url.searchParams.delete('smtt_agenda_date');
            url.hash = 'agenda';
            const state = { smttAgenda: true, month: String(month), date: date || '' };
            if (mode === 'push') window.history.pushState(state, '', url.toString());
            else window.history.replaceState(state, '', url.toString());
        } catch (error) {
            // A interface continua funcionando mesmo que a URL não possa ser atualizada.
        }
    }

    function showMonth(month, updateHistory) {
        const normalized = String(parseInt(month, 10) || select.value);
        monthPanels.forEach(function (panel) {
            panel.hidden = panel.getAttribute('data-smtt-agenda-month-panel') !== normalized;
        });
        if (select.value !== normalized) select.value = normalized;
        setPanelVisibility(detailsScreen, false);
        setPanelVisibility(calendarScreen, true);
        detailPanels.forEach(function (panel) { panel.hidden = true; });
        if (updateHistory !== false) updateUrl(normalized, '', 'replace');
    }

    function showDate(date, label, count, historyMode) {
        const target = detailPanels.find(function (panel) {
            return panel.getAttribute('data-smtt-agenda-detail') === date;
        });
        if (!target) return false;

        detailPanels.forEach(function (panel) { panel.hidden = panel !== target; });
        const parts = String(date).split('-');
        const month = parts.length === 3 ? String(parseInt(parts[1], 10)) : select.value;
        if (month && select.value !== month) select.value = month;
        monthPanels.forEach(function (panel) {
            panel.hidden = panel.getAttribute('data-smtt-agenda-month-panel') !== month;
        });

        if (detailTitle) detailTitle.textContent = 'Agenda de ' + (label || date);
        if (detailSummary) {
            const total = parseInt(count, 10) || target.querySelectorAll('.smtt-cal-event-item').length;
            detailSummary.textContent = total + (total === 1 ? ' marcação encontrada.' : ' marcações encontradas.');
        }

        setPanelVisibility(calendarScreen, false);
        setPanelVisibility(detailsScreen, true);
        if (historyMode) updateUrl(month, date, historyMode);
        return true;
    }

    select.addEventListener('change', function () {
        showMonth(select.value, true);
    });

    root.addEventListener('click', function (event) {
        const dayLink = event.target.closest('[data-smtt-agenda-day]');
        if (!dayLink || !root.contains(dayLink)) return;
        const date = dayLink.getAttribute('data-date') || '';
        if (!date) return;
        if (showDate(date, dayLink.getAttribute('data-date-label'), dayLink.getAttribute('data-event-count'), 'push')) {
            event.preventDefault();
        }
    });

    if (backButton) {
        backButton.addEventListener('click', function (event) {
            event.preventDefault();
            showMonth(select.value, true);
        });
    }

    window.addEventListener('popstate', function () {
        try {
            const url = new URL(window.location.href);
            const month = url.searchParams.get('smtt_agenda_month') || select.value;
            const date = url.searchParams.get('smtt_agenda_date') || '';
            if (date) {
                const dayLink = Array.from(root.querySelectorAll('[data-smtt-agenda-day]')).find(function (item) { return item.getAttribute('data-date') === date; });
                if (dayLink && showDate(date, dayLink.getAttribute('data-date-label'), dayLink.getAttribute('data-event-count'), null)) return;
            }
            showMonth(month, false);
        } catch (error) {
            showMonth(select.value, false);
        }
    });
});

/* =========================
   ANIVERSARIANTES - linha do tempo completa
========================== */
document.addEventListener('DOMContentLoaded', function () {
    const openButton = document.getElementById('smttBdayTimelineOpen');
    const modal = document.getElementById('smttBdayTimelineModal');
    const backdrop = document.getElementById('smttBdayTimelineBackdrop');
    const closeButton = document.getElementById('smttBdayTimelineClose');
    const panel = modal ? modal.querySelector('.smtt-bday-timeline-panel') : null;

    if (!openButton || !modal || !backdrop || !closeButton) return;

    function openTimeline() {
        modal.hidden = false;
        backdrop.hidden = false;
        if (panel) panel.scrollTop = 0;
        requestAnimationFrame(function () {
            modal.classList.add('is-open');
            backdrop.classList.add('is-open');
            document.body.classList.add('smtt-bday-modal-open');
            closeButton.focus({ preventScroll: true });
        });
    }

    function closeTimeline() {
        modal.classList.remove('is-open');
        backdrop.classList.remove('is-open');
        document.body.classList.remove('smtt-bday-modal-open');
        setTimeout(function () {
            modal.hidden = true;
            backdrop.hidden = true;
        }, 180);
    }

    openButton.addEventListener('click', openTimeline);
    closeButton.addEventListener('click', closeTimeline);
    backdrop.addEventListener('click', closeTimeline);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) closeTimeline();
    });
});

/* Saudação do banner acompanha a mudança de horário sem exigir recarregar a página. */
document.addEventListener('DOMContentLoaded', function () {
    const greeting = document.getElementById('smttTimeGreeting');
    if (!greeting) return;

    function updateGreeting() {
        const hour = new Date().getHours();
        greeting.textContent = hour < 12 ? 'Bom dia' : (hour < 18 ? 'Boa tarde' : 'Boa noite');
    }

    updateGreeting();
    window.setInterval(updateGreeting, 60000);
});


/* =========================
   NOTÍCIAS - fallback visual de imagem
========================== */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('img[data-smtt-news-image]').forEach(function (image) {
        image.addEventListener('error', function handleNewsImageError() {
            var fallback = image.getAttribute('data-smtt-news-fallback');
            var alreadyTriedFallback = image.getAttribute('data-smtt-news-fallback-tried') === '1';

            if (fallback && !alreadyTriedFallback && image.src !== fallback) {
                image.setAttribute('data-smtt-news-fallback-tried', '1');
                image.src = fallback;
                return;
            }

            var thumb = image.closest('.smtt-news-thumb-inline');
            var card = image.closest('.smtt-news-card-inline');
            if (thumb) thumb.remove();
            if (card) {
                card.classList.remove('has-image');
                card.classList.add('no-image');
            }
        });
    });
});
