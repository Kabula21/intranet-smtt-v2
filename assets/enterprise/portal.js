/* SMTT Intranet: catálogo local com categorias, pesquisa integral e carregamento progressivo. */
(() => {
  'use strict';
  const root = document.getElementById('smtt-enterprise-admin');
  if (!root) return;

  // Campos condicionais de Documentos e Wiki: o formulário mostra apenas os campos pertinentes.
  const contentForm = root.querySelector('form[data-smtt-module]');
  if (contentForm) {
    const showField = (id, visible) => {
      const field = document.getElementById('smtt-field-' + id);
      if (!field) return;
      const label = root.querySelector('label[for="smtt-field-' + id + '"]');
      field.hidden = !visible;
      if (label) label.hidden = !visible;
      const wrapper = field.closest('.smtt-ep__form-field');
      if (wrapper) wrapper.hidden = !visible;
    };
    const renderContentType = () => {
      const kind = contentForm.dataset.smttModule;
      const upload = contentForm.querySelector('[data-smtt-upload]');
      const rich = contentForm.querySelector('#smtt-wiki-editor');
      if (kind === 'documents') {
        const mode = document.getElementById('smtt-field-document_type')?.value || 'arquivo';
        showField('file_url', mode !== 'link');
        showField('external_url', mode !== 'arquivo');
        if (upload) upload.hidden = mode === 'link';
      } else if (kind === 'knowledge') {
        const mode = document.getElementById('smtt-field-content_type')?.value || 'artigo';
        showField('article_url', mode === 'link');
        if (rich) rich.hidden = mode !== 'artigo';
        if (upload) upload.hidden = mode !== 'arquivo';
      }
    };
    contentForm.querySelectorAll('#smtt-field-document_type, #smtt-field-content_type').forEach(el=>el.addEventListener('change',renderContentType));
    renderContentType();
  }

  root.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
  }));

  const dataNode = document.getElementById('smtt-portal-icon-data');
  let icons = [];
  try {
    const data = JSON.parse(dataNode?.textContent || '[]');
    icons = Array.isArray(data) ? data.filter(item => item && typeof item.value === 'string' && typeof item.label === 'string') : [];
  } catch (_) { icons = []; }

  const categories = [
    ['todos', 'Todos'],
    ['pastas', 'Pastas'],
    ['sistemas', 'Sistemas e TI'],
    ['documentos', 'Documentos'],
    ['usuarios', 'Pessoas e setores'],
    ['redes', 'Redes'],
    ['seguranca', 'Segurança'],
    ['transito', 'Trânsito e transporte'],
    ['comunicacao', 'Comunicação'],
    ['marcas', 'Marcas'],
    ['outros', 'Outros'],
  ];
  const PAGE_SIZE = 72;
  const iconBase = root.dataset.iconBase || '';
  const dialog = document.getElementById('smtt-icon-dialog');
  let activeInput = null;
  const normalize = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
  const iconMatches = (item, group) => group === 'todos' || (Array.isArray(item.categories) && item.categories.includes(group));
  const counts = new Map(categories.map(([key]) => [key, key === 'todos' ? icons.length : icons.filter(item => iconMatches(item, key)).length]));
  const searchable = icons.map(item => ({ ...item, searchText: normalize([item.label, item.value, item.keywords || ''].join(' ')) }));

  const iconUrl = value => {
    let match = /^fa:(solid|regular|brands):([a-z0-9-]+)$/.exec(value);
    if (match) return iconBase + match[1] + '/' + match[2] + '.svg';
    match = /^smtt:([a-z0-9-]+)$/.exec(value);
    return match ? iconBase + 'smtt/' + match[1] + '.svg' : '';
  };

  const views = [
    { id:'main', grid:document.getElementById('smtt-icon-grid'), chips:document.getElementById('smtt-icon-categories'),
      search:document.getElementById('smtt-icon-search'), info:document.getElementById('smtt-icon-results'),
      more:document.getElementById('smtt-icon-more'), category:'todos', limit:PAGE_SIZE, filtered:[] },
    { id:'modal', grid:document.getElementById('smtt-modal-grid'), chips:document.getElementById('smtt-modal-categories'),
      search:document.getElementById('smtt-modal-search'), info:document.getElementById('smtt-modal-results'),
      more:document.getElementById('smtt-modal-more'), category:'todos', limit:PAGE_SIZE, filtered:[] },
  ];

  const copyCode = async value => {
    const feedback = document.getElementById('smtt-icon-copied');
    try {
      if (navigator.clipboard?.writeText) await navigator.clipboard.writeText(value);
      else {
        const textarea = document.createElement('textarea');
        textarea.value = value;
        textarea.style.position = 'fixed'; textarea.style.left = '-9999px';
        document.body.append(textarea); textarea.select();
        if (!document.execCommand('copy')) throw new Error('clipboard unavailable');
        textarea.remove();
      }
      if (feedback) feedback.textContent = 'Código copiado: ' + value;
    } catch (_) {
      if (feedback) feedback.textContent = 'Copie o código do ícone: ' + value;
    }
  };

  const select = value => {
    if (activeInput && document.contains(activeInput)) {
      activeInput.value = value;
      activeInput.dispatchEvent(new Event('input', { bubbles:true }));
      activeInput.dispatchEvent(new Event('change', { bubbles:true }));
    } else copyCode(value);
    if (dialog?.open) dialog.close();
    activeInput = null;
  };

  const createTile = item => {
    const button = document.createElement('button');
    button.type = 'button'; button.className = 'smtt-ep__iconchoice';
    button.title = item.label + ' · ' + item.value;
    button.setAttribute('aria-label', item.label + '. Selecionar ícone');
    const src = iconUrl(item.value);
    if (src) {
      const img = document.createElement('img');
      img.src = src; img.alt = ''; img.loading = 'lazy'; img.decoding = 'async';
      button.append(img);
    }
    const label = document.createElement('small'); label.textContent = item.label;
    button.append(label);
    button.addEventListener('click', () => select(item.value));
    return button;
  };

  const updateInfo = view => {
    if (!view.info) return;
    const shown = Math.min(view.limit, view.filtered.length);
    if (!view.filtered.length) {
      view.info.textContent = 'Nenhum ícone encontrado. Experimente outra palavra ou a categoria Todos.';
    } else {
      view.info.textContent = 'Exibindo ' + shown.toLocaleString('pt-BR') + ' de ' + view.filtered.length.toLocaleString('pt-BR') + ' ícones desta seleção. Role a lista ou clique em Mostrar mais.';
    }
    if (view.more) {
      view.more.hidden = shown >= view.filtered.length;
      if (!view.more.hidden) view.more.textContent = 'Mostrar mais ' + Math.min(PAGE_SIZE, view.filtered.length - shown).toLocaleString('pt-BR') + ' ícones';
    }
  };

  const renderList = (view, append = false) => {
    if (!view.grid) return;
    const filtered = view.filtered;
    const start = append ? Math.max(0, view.limit - PAGE_SIZE) : 0;
    if (!append) view.grid.replaceChildren();
    const fragment = document.createDocumentFragment();
    for (const item of filtered.slice(start, view.limit)) fragment.append(createTile(item));
    view.grid.append(fragment);
    updateInfo(view);
  };

  const filterView = (view, resetScroll = true) => {
    const term = normalize(view.search?.value);
    const terms = term.split(/\s+/).filter(Boolean);
    view.filtered = searchable.filter(item => iconMatches(item, view.category) && terms.every(word => item.searchText.includes(word)));
    view.limit = PAGE_SIZE;
    if (resetScroll && view.grid) view.grid.scrollTop = 0;
    renderList(view);
  };

  const renderCategories = view => {
    if (!view.chips) return;
    const fragment = document.createDocumentFragment();
    for (const [key, label] of categories) {
      const count = counts.get(key) || 0;
      if (count === 0) continue;
      const button = document.createElement('button');
      button.type = 'button'; button.className = 'smtt-ep__cat';
      button.dataset.category = key; button.setAttribute('aria-pressed', String(view.category === key));
      const text = document.createElement('span'); text.textContent = label;
      const number = document.createElement('span'); number.className = 'smtt-ep__cat-count'; number.textContent = count.toLocaleString('pt-BR');
      button.append(text, number);
      button.addEventListener('click', () => {
        view.category = key;
        // Keep the text search visible; selecting a new category refines it.
        view.chips.querySelectorAll('.smtt-ep__cat').forEach(chip => chip.setAttribute('aria-pressed', String(chip.dataset.category === key)));
        filterView(view);
      });
      fragment.append(button);
    }
    view.chips.replaceChildren(fragment);
  };

  for (const view of views) {
    if (!view.grid) continue;
    renderCategories(view);
    filterView(view, false);
    view.search?.addEventListener('input', () => {
      // Search across the full collection by default, including on a selected category.
      if (view.category !== 'todos') {
        view.category = 'todos';
        view.chips?.querySelectorAll('.smtt-ep__cat').forEach(chip => chip.setAttribute('aria-pressed', String(chip.dataset.category === 'todos')));
      }
      filterView(view);
    });
    view.more?.addEventListener('click', () => {
      view.limit += PAGE_SIZE;
      renderList(view, true);
    });
    // Scrolling is an alternative to clicking the button, never an artificial 90-item cap.
    view.grid.addEventListener('scroll', () => {
      if (view.grid.scrollTop + view.grid.clientHeight < view.grid.scrollHeight - 120) return;
      if (view.limit >= view.filtered.length) return;
      view.limit += PAGE_SIZE;
      renderList(view, true);
    }, { passive: true });
  }

  root.querySelectorAll('[data-open-icons]').forEach(button => button.addEventListener('click', () => {
    activeInput = button.closest('.smtt-ep__iconinput')?.querySelector('[data-icon-value]') || null;
    const modal = views[1];
    modal.category = 'todos';
    if (modal.search) modal.search.value = '';
    renderCategories(modal);
    filterView(modal);
    if (dialog && typeof dialog.showModal === 'function') {
      dialog.showModal();
      modal.search?.focus();
    }
  }));
  document.getElementById('smtt-icon-close')?.addEventListener('click', () => dialog?.close());
  dialog?.addEventListener('close', () => { activeInput = null; });
  root.querySelectorAll('[data-pick-icon]').forEach(el => el.addEventListener('click', () => select(el.dataset.pickIcon || '')));
})();
