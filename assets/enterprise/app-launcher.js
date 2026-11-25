/** SMTT — Editor de aplicativos: reordenação, campos condicionais e confirmações. */
(() => {
  'use strict';
  const list = document.querySelector('[data-apps-sortable]');
  if (!list) return;
  let dragging = null;
  let dirty = false;
  const markDirty = () => { dirty = true; };
  list.addEventListener('input',markDirty);
  list.addEventListener('change',markDirty);
  const rows = () => [...list.querySelectorAll('[data-app-row]')];
  const focusRow = row => row?.querySelector('[data-app-drag]')?.focus();

  list.addEventListener('click', e => {
    const move = e.target.closest('[data-move]');
    if (move) {
      const row = move.closest('[data-app-row]');
      if (!row) return;
      if (move.dataset.move === 'up' && row.previousElementSibling) list.insertBefore(row, row.previousElementSibling);
      if (move.dataset.move === 'down' && row.nextElementSibling) list.insertBefore(row.nextElementSibling, row);
      focusRow(row);
      markDirty();
      return;
    }
    const remove = e.target.closest('[data-app-remove]');
    if (remove && !window.confirm('Remover "' + remove.dataset.appRemove + '" do rascunho do menu? O registro do sistema, quando houver, será preservado.')) {
      e.preventDefault();
    }
  });

  list.addEventListener('dragstart', e => {
    const row=e.target.closest('[data-app-row]');
    // Only the explicit drag handle may initiate a move.
    if (!row || !row.dataset.dragArmed) { e.preventDefault(); return; }
    dragging=row;
    e.dataTransfer.effectAllowed='move';
    e.dataTransfer.setData('text/plain',row.dataset.appRow);
    requestAnimationFrame(()=>row.classList.add('is-dragging'));
  });
  list.addEventListener('pointerdown', e => {
    const row=e.target.closest('[data-app-row]');
    if (row && e.target.closest('[data-app-drag]')) { row.dataset.dragArmed='1'; row.draggable=true; }
  });
  list.addEventListener('pointerup',()=>rows().forEach(row=>{ delete row.dataset.dragArmed; row.draggable=false; }));
  list.addEventListener('dragover', e => {
    const target=e.target.closest('[data-app-row]');
    if (!dragging || !target || target===dragging) return;
    e.preventDefault();
    const rect=target.getBoundingClientRect();
    const sourceRect=dragging.getBoundingClientRect();
    const sameGridRow=Math.abs(rect.top-sourceRect.top) < Math.min(rect.height,sourceRect.height)*0.4;
    const before=sameGridRow ? e.clientX < rect.left + rect.width/2 : e.clientY < rect.top + rect.height/2;
    list.insertBefore(dragging,before ? target : target.nextSibling);
    markDirty();
  });
  list.addEventListener('drop',e=>{ if(dragging) e.preventDefault(); });
  list.addEventListener('dragend',()=>{
    if (dragging) dragging.classList.remove('is-dragging');
    dragging=null;
    rows().forEach(row=>{ row.draggable=false; delete row.dataset.dragArmed; });
  });

  const applyAudience = select => {
    const row=select.closest('[data-app-row]');
    if (!row) return;
    const users=row.querySelector('[data-app-users]');
    const sectors=row.querySelector('[data-app-sectors]');
    const groups=row.querySelector('[data-app-groups]');
    if (users) users.hidden = select.value!=='users';
    if (sectors) sectors.hidden = select.value!=='sectors';
    if (groups) groups.hidden = select.value!=='groups';
  };
  list.querySelectorAll('[data-app-audience]').forEach(select=>{
    select.addEventListener('change',()=>applyAudience(select));
    applyAudience(select);
  });
  // If a collapsed application has an invalid input, open its details before
  // native browser validation tries to move focus to that input.
  const editorForm=document.getElementById('smtt-apps-editor');
  editorForm?.addEventListener('invalid',event=>{
    const details=event.target.closest('details.smtt-app-admin__details');
    if (details) details.open=true;
  },true);
  document.querySelectorAll('[data-app-publish]').forEach(button=>button.addEventListener('click',e=>{
    if (dirty) {
      e.preventDefault();
      window.alert('Há alterações ainda não salvas. Clique em Salvar rascunho antes de publicar.');
    }
  }));
  document.querySelectorAll('[data-app-restore]').forEach(button=>button.addEventListener('click',e=>{
    if (!window.confirm('Restaurar e publicar a versão anterior do menu?')) e.preventDefault();
  }));
})();
