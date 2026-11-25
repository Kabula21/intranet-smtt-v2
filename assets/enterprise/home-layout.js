(function () {
  'use strict';
  const editor = document.querySelector('[data-smtt-home-editor]');
  if (!editor) return;
  const form = editor.querySelector('form[data-smtt-home-form]');
  const list = editor.querySelector('[data-home-cards]');
  const field = editor.querySelector('[name="layout_json"]');
  const phone = editor.querySelector('[data-home-device]');
  const note = editor.querySelector('[data-home-dirty]');
  let dragged = null;
  let changed = false;

  const entries = () => Array.from(list.querySelectorAll('[data-card-id]'));
  function markChanged() {
    changed = true;
    note.hidden = false;
    refresh();
  }
  function refresh() {
    const data = entries().map((card) => ({
      id: card.dataset.cardId,
      visible: !!card.querySelector('[data-card-visible]').checked,
      span: Number(card.querySelector('[data-card-span]').value),
      height: Number(card.querySelector('[data-card-height]').value),
      title: card.querySelector('[data-card-title]').value.trim(),
      subtitle: card.querySelector('[data-card-subtitle]').value.trim(),
      icon: card.querySelector('[data-card-icon]').value.trim(),
      tone: card.querySelector('[data-card-tone]').value
    }));
    field.value = JSON.stringify(data);
    entries().forEach((card, index) => {
      const name = card.querySelector('[data-card-order]');
      if (name) name.textContent = String(index + 1).padStart(2,'0');
      card.style.setProperty('--editor-span', card.querySelector('[data-card-span]').value);
      card.style.setProperty('--editor-height', card.querySelector('[data-card-height]').value + 'px');
      card.classList.toggle('is-hidden-card', !card.querySelector('[data-card-visible]').checked);
    });
    const hidden = data.filter((e) => !e.visible).length;
    const summary = editor.querySelector('[data-home-summary]');
    if (summary) summary.textContent = `${data.length - hidden} exibidos · ${hidden} ocultos`;
  }
  function move(card, offset) {
    const rows = entries(); const pos = rows.indexOf(card);
    const target = rows[pos + offset];
    if (!target) return;
    if (offset < 0) list.insertBefore(card, target);
    else list.insertBefore(target, card);
    card.querySelector('[data-card-grip]').focus();
    markChanged();
  }
  list.addEventListener('click', (event) => {
    const btn = event.target.closest('button[data-move]');
    if (!btn) return;
    const card = btn.closest('[data-card-id]');
    if (card) move(card, btn.dataset.move === 'up' ? -1 : 1);
  });
  list.addEventListener('input', (event) => {
    if (event.target.matches('[data-card-title],[data-card-subtitle]')) markChanged();
  });
  list.addEventListener('click', (event) => {
    const button = event.target.closest('[data-reset-icon]');
    if (!button) return;
    const field = button.closest('.smtt-ep__iconinput').querySelector('[data-card-icon]');
    field.value = '';
    markChanged();
  });
  list.addEventListener('change', (event) => {
    if (event.target.matches('[data-card-visible],[data-card-span],[data-card-height],[data-card-title],[data-card-subtitle],[data-card-icon],[data-card-tone]')) markChanged();
  });
  list.addEventListener('dragstart', (event) => {
    const grip = event.target.closest('[data-card-grip]');
    const card = event.target.closest('[data-card-id]');
    if (!grip || !card) { event.preventDefault(); return; }
    dragged = card;
    if (event.dataTransfer) {
      event.dataTransfer.effectAllowed = 'move';
      event.dataTransfer.setData('text/plain',card.dataset.cardId);
    }
    card.classList.add('is-dragging');
  });
  list.addEventListener('dragover', (event) => {
    if (!dragged) return;
    // O editor usa a rolagem da página/coluna direita, nunca uma caixa interna.
    event.preventDefault();
    const pane = editor.closest('.smtt-ep__body');
    const paneScrollable = pane && pane.scrollHeight > pane.clientHeight + 2 &&
      window.getComputedStyle(pane).overflowY !== 'visible';
    const viewport = paneScrollable ? pane.getBoundingClientRect() :
      { top: 0, bottom: window.innerHeight };
    const edge = 88;
    if (event.clientY >= viewport.top && event.clientY < viewport.top + edge) {
      if (paneScrollable) pane.scrollTop -= 18;
      else window.scrollBy(0, -18);
    } else if (event.clientY <= viewport.bottom && event.clientY > viewport.bottom - edge) {
      if (paneScrollable) pane.scrollTop += 18;
      else window.scrollBy(0, 18);
    }
    const over = event.target.closest('[data-card-id]');
    if (!over || over === dragged) return;
    const midpoint = over.getBoundingClientRect().top + over.offsetHeight / 2;
    if (event.clientY < midpoint) list.insertBefore(dragged, over);
    else list.insertBefore(dragged, over.nextSibling);
  });
  list.addEventListener('drop', (event) => { if (dragged) { event.preventDefault(); markChanged(); } });
  list.addEventListener('dragend', () => {
    entries().forEach((c) => c.classList.remove('is-dragging'));
    dragged = null;
    refresh();
  });
  list.addEventListener('keydown', (event) => {
    const grip = event.target.closest('[data-card-grip]');
    if (!grip || !event.altKey || !['ArrowUp','ArrowDown'].includes(event.key)) return;
    event.preventDefault();
    move(grip.closest('[data-card-id]'),event.key === 'ArrowUp' ? -1 : 1);
  });
  if (phone) phone.addEventListener('change', () => editor.classList.toggle('is-mobile-preview',phone.value === 'mobile'));
  form.addEventListener('submit', () => refresh());
  refresh();
  window.addEventListener('beforeunload', (event) => {
    if (!changed) return;
    event.preventDefault();
    event.returnValue = '';
  });
  form.addEventListener('submit', () => { changed = false; });
  const restore = editor.querySelector('form[data-home-restore]');
  if (restore) restore.addEventListener('submit', (e) => {
    if (!window.confirm('Restaurar o layout publicado anteriormente? Esta ação alterará a Home de todos os funcionários.')) e.preventDefault();
  });
})();
