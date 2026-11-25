(() => {
  'use strict';
  const cfg = window.smttChatHomeConfig;
  const root = document.querySelector('[data-smtt-chat-home]');
  if (!cfg || !root) return;
  const button = root.querySelector('[data-chat-toggle]');
  const dropdown = root.querySelector('[data-chat-dropdown]');
  const items = root.querySelector('[data-chat-recent]');
  const badge = root.querySelector('[data-chat-badge]');
  // Sincroniza o tema com a preferência do Chat SMTT sem alterar o tema da Home.
  const themeKey = 'smtt_chat_theme_v1_user_' + String(cfg.userId || 0);
  function syncTheme() {
    let theme = '';
    try { theme = localStorage.getItem(themeKey) || ''; } catch (_e) { /* privacidade */ }
    if (theme === 'dark' || theme === 'light') root.dataset.chatTheme = theme;
    else delete root.dataset.chatTheme;
  }
  syncTheme();
  window.addEventListener('storage', event => { if (event.key === themeKey) syncTheme(); });
  let inFlight = false;
  const el = (tag, cls, text) => {
    const node = document.createElement(tag);
    if (cls) node.className = cls;
    if (text !== undefined) node.textContent = String(text);
    return node;
  };
  const url = (conversation) => {
    const u = new URL(cfg.url, window.location.href);
    if (conversation) u.searchParams.set('conversation', String(conversation));
    return u.href;
  };
  function setOpen(value) {
    dropdown.hidden = !value;
    button.setAttribute('aria-expanded', value ? 'true' : 'false');
    if (value) refresh();
  }
  button.addEventListener('click',()=>setOpen(dropdown.hidden));
  document.addEventListener('click', e => {if (!root.contains(e.target)) setOpen(false);});
  document.addEventListener('keydown', e => {if (e.key === 'Escape') setOpen(false);});
  function previewTime(value) {
    if (!value) return '';
    const date = new Date(String(value).replace(' ', 'T') + 'Z');
    if (Number.isNaN(date.getTime())) return '';
    if (date.toDateString() === new Date().toDateString()) return date.toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'});
    return date.toLocaleDateString('pt-BR', {day:'2-digit',month:'2-digit'});
  }
  function groupAvatar() {
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox','0 0 24 24');
    svg.setAttribute('aria-hidden','true');
    svg.setAttribute('class','smtt-chat-home-group-icon');
    svg.innerHTML = '<path d="M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 20v-2a4 4 0 0 0-3-3.87M16 2.13a4 4 0 0 1 0 7.75"/>';
    return svg;
  }
  async function refresh() {
    if (inFlight || document.visibilityState !== 'visible') return;
    if (!cfg.loggedIn || !cfg.enabled) {
      items.replaceChildren(el('p','smtt-chat-menu-note',cfg.loggedIn ? 'Chat desativado pelo CPD.' : 'Faça login para ver suas conversas.'));
      badge.hidden = true;
      return;
    }
    inFlight = true;
    try {
      const response = await fetch(cfg.rest + 'summary?limit=5', {headers:{'X-WP-Nonce':cfg.nonce},credentials:'same-origin',cache:'no-store'});
      const data = await response.json();
      if (!response.ok) throw new Error(data.message || 'Erro ao consultar conversas.');
      const count = Number(data.unread_total || 0);
      badge.hidden = count <= 0;
      badge.textContent = count > 99 ? '99+' : String(count);
      items.replaceChildren();
      if (!(data.conversations || []).length) items.append(el('p','smtt-chat-menu-note','Nenhuma conversa recente. Abra o chat para começar.'));
      for (const c of data.conversations || []) {
        const link = el('a','smtt-chat-menu-item');
        link.href = url(c.id);
        link.target = '_blank';link.rel = 'noopener noreferrer';
        const avatar = el('span','smtt-chat-menu-avatar' + (c.type === 'group' ? ' is-group' : ''));
        if (c.type === 'group') avatar.append(groupAvatar());
        else avatar.textContent = (c.title || '?').slice(0,1).toLocaleUpperCase();
        const info = el('span','smtt-chat-menu-info');
        info.append(el('strong','',c.title),el('small','',c.last_text ? c.last_text.slice(0,90) : 'Nova conversa'));
        const meta = el('span','smtt-chat-menu-meta');
        meta.append(el('time','smtt-chat-menu-time',previewTime(c.updated_at)));
        if (c.unread) meta.append(el('span','smtt-chat-menu-unread',c.unread>99?'99+':c.unread));
        link.append(avatar,info,meta);
        items.append(link);
      }
    } catch (err) {if (!dropdown.hidden) items.replaceChildren(el('p','smtt-chat-menu-note',err.message));}
    finally {inFlight = false;}
  }
  const openFull = root.querySelector('[data-chat-full]');
  if (openFull) openFull.href = cfg.loggedIn ? url() : cfg.loginUrl;
  refresh();
  window.setInterval(refresh, 30000);
})();
