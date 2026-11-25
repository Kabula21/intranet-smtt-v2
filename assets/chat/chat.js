(() => {
  'use strict';
  const cfg = window.smttChatConfig;
  const app = document.getElementById('smtt-chat-app');
  if (!cfg || !app) return;
  // Preferência local isolada por usuário. A mesma chave é lida pelo menu da Home.
  const themeKey = 'smtt_chat_theme_v1_user_' + String(cfg.userId || 0);
  function savedTheme() {
    try { const v = localStorage.getItem(themeKey); return v === 'dark' ? 'dark' : 'light'; }
    catch (_e) { return 'light'; }
  }
  function applyTheme(value) {
    const theme = value === 'dark' ? 'dark' : 'light';
    document.body.dataset.chatTheme = theme;
    const toggle = document.getElementById('smtt-chat-theme-toggle');
    if (toggle) {
      toggle.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
      toggle.setAttribute('aria-label', theme === 'dark' ? 'Ativar tema claro' : 'Ativar tema escuro');
      toggle.title = theme === 'dark' ? 'Ativar tema claro' : 'Ativar tema escuro';
    }
  }
  applyTheme(savedTheme());
  document.getElementById('smtt-chat-theme-toggle')?.addEventListener('click', () => {
    const next = document.body.dataset.chatTheme === 'dark' ? 'light' : 'dark';
    try { localStorage.setItem(themeKey, next); } catch (_e) { /* navegação privada */ }
    applyTheme(next);
  });
  window.addEventListener('storage', e => {
    if (e.key === themeKey) applyTheme(savedTheme());
  });
  if (!cfg.enabled) return;
  const $ = (id) => document.getElementById(id);
  const list = $('smtt-chat-conversations');
  const area = $('smtt-chat-message-area');
  const form = $('smtt-chat-compose');
  const input = $('smtt-chat-message-input');
  const dialog = $('smtt-chat-new-dialog');
  const search = $('smtt-chat-person-search');
  const people = $('smtt-chat-people-results');
  const selectedEl = $('smtt-chat-selected');
  const errorEl = $('smtt-chat-create-error');
  let activeId = 0;
  let conversations = [];
  let messages = new Map();
  let canLoadOlder = false;
  let busy = false;
  let creatingGroup = false;
  let selectedUsers = new Map();
  let searchTimer = 0;
  let pollInFlight = false;
  const icons = ['😀', '😊', '😂', '😉', '😍', '😃', '👍', '👏', '🙏', '🎉', '✅', '❤️', '👋', '💬', '🔔', '🙂'];

  function make(tag, className, text) {
    const el = document.createElement(tag);
    if (className) el.className = className;
    if (text !== undefined) el.textContent = String(text);
    return el;
  }
  async function api(path, options = {}) {
    const response = await fetch(cfg.rest + path, {
      method: options.method || 'GET',
      headers: {'X-WP-Nonce': cfg.nonce, ...(options.data ? {'Content-Type': 'application/json'} : {})},
      body: options.data ? JSON.stringify(options.data) : undefined,
      credentials: 'same-origin', cache: 'no-store'
    });
    let data;
    try { data = await response.json(); } catch (_e) { data = {}; }
    if (!response.ok) throw new Error(data.message || 'Não foi possível comunicar com o Chat SMTT.');
    return data;
  }
  function localDate(value) {
    if (!value) return '';
    const parsed = new Date(String(value).replace(' ', 'T') + 'Z');
    return isNaN(parsed.getTime()) ? '' : parsed.toLocaleString('pt-BR', {day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'});
  }
  function plainLinkify(node, text) {
    const pattern = /https?:\/\/[^\s<>'"`]+/gi;
    let start = 0;
    for (const match of text.matchAll(pattern)) {
      const position = match.index;
      if (position > start) node.append(document.createTextNode(text.slice(start, position)));
      const raw = match[0];
      const safeRaw = raw.replace(/[.,;!?)]*$/, '');
      const tail = raw.slice(safeRaw.length);
      try {
        const url = new URL(safeRaw);
        if (url.protocol === 'http:' || url.protocol === 'https:') {
          const link = make('a', '', safeRaw);
          link.href = url.href;
          link.target = '_blank';
          link.rel = 'noopener noreferrer nofollow';
          node.append(link);
        } else node.append(document.createTextNode(safeRaw));
      } catch (_e) { node.append(document.createTextNode(safeRaw)); }
      if (tail) node.append(document.createTextNode(tail));
      start = position + raw.length;
    }
    if (start < text.length) node.append(document.createTextNode(text.slice(start)));
  }
  function uiIcon(name) {
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('class', 'smtt-chat-ui-icon');
    svg.setAttribute('aria-hidden', 'true');
    const use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
    use.setAttribute('href', '#smtt-i-' + (name === 'group' ? 'group' : 'user'));
    svg.append(use);
    return svg;
  }
  function shortTime(value) {
    if (!value) return '';
    const date = new Date(String(value).replace(' ', 'T') + 'Z');
    if (Number.isNaN(date.getTime())) return '';
    const now = new Date();
    if (date.toDateString() === now.toDateString()) return date.toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'});
    return date.toLocaleDateString('pt-BR', {day:'2-digit',month:'2-digit'});
  }
  function renderConversations() {
    const query = $('smtt-chat-filter').value.trim().toLocaleLowerCase();
    list.replaceChildren();
    const matching = conversations.filter(x => x.title.toLocaleLowerCase().includes(query));
    if (!matching.length) {
      list.append(make('p', 'smtt-chat-list-empty', query ? 'Nenhuma conversa encontrada.' : 'Ainda não há conversas. Inicie uma nova.'));
      return;
    }
    for (const c of matching) {
      const button = make('button', 'smtt-chat-conversation' + (c.id === activeId ? ' is-active' : ''));
      button.type = 'button';
      button.dataset.conversationId = String(c.id);
      const avatar = make('span', 'smtt-chat-avatar' + (c.type === 'group' ? ' is-group' : '') + (c.group_source === 'ad' ? ' is-ad-group' : ''));
      if (c.type === 'group') avatar.append(uiIcon('group'));
      else avatar.textContent = (c.title || '?').trim().slice(0, 1).toLocaleUpperCase();
      const details = make('span', 'smtt-chat-conversation-info');
      details.append(make('strong', '', c.title));
      details.append(make('small', '', c.last_text ? c.last_text.slice(0, 75) : (c.group_source === 'ad' ? 'Grupo de trabalho do AD' : 'Conversa iniciada')));
      const side = make('span', 'smtt-chat-conversation-side');
      side.append(make('time', 'smtt-chat-conversation-time', shortTime(c.updated_at)));
      if (c.unread) side.append(make('span', 'smtt-chat-unread', c.unread > 99 ? '99+' : c.unread));
      button.append(avatar, details, side);
      button.addEventListener('click', () => openConversation(c.id));
      list.append(button);
    }
  }
  async function updateSummary() {
    const data = await api('summary?limit=50');
    conversations = data.conversations || [];
    renderConversations();
    if (activeId) {
      const current = conversations.find(c => c.id === activeId);
      if (current) $('smtt-chat-current-name').textContent = current.title;
    }
  }
  function drawMessages(scrollToBottom = false) {
    const currentScroll = area.scrollTop;
    const currentHeight = area.scrollHeight;
    const wasBottom = area.scrollHeight - area.scrollTop - area.clientHeight < 110;
    area.replaceChildren();
    if (canLoadOlder) {
      const older = make('button', 'smtt-chat-more', 'Carregar mensagens anteriores');
      older.type = 'button';
      older.addEventListener('click', loadOlder);
      area.append(older);
    }
    const arr = [...messages.values()].sort((a,b) => a.id - b.id);
    if (!arr.length) area.append(make('p', 'smtt-chat-empty', 'Nenhuma mensagem ainda. Envie a primeira!'));
    for (const m of arr) {
      const mine = m.sender_id === cfg.userId;
      const bubble = make('div', 'smtt-chat-bubble ' + (mine ? 'is-mine' : 'is-other'));
      bubble.append(make('strong', '', mine ? 'Você' : m.sender_name));
      const body = make('div', 'smtt-chat-text');
      plainLinkify(body, m.body || '');
      bubble.append(body, make('time', '', localDate(m.created_at)));
      area.append(bubble);
    }
    if (scrollToBottom || wasBottom) area.scrollTop = area.scrollHeight;
    else area.scrollTop = currentScroll + (area.scrollHeight - currentHeight);
  }
  async function fetchMessages(id, before = 0) {
    const data = await api('conversations/' + id + '/messages' + (before ? '?before=' + before : ''));
    if (activeId !== id) return;
    if (before) canLoadOlder = data.has_more;
    else if (!messages.size) canLoadOlder = data.has_more;
    for (const m of data.messages || []) messages.set(m.id, m);
    drawMessages(false);
    if (document.visibilityState === 'visible' && !before) {
      await api('conversations/' + id + '/read', {method:'POST',data:{}});
    }
  }
  async function openConversation(id) {
    activeId = Number(id);
    messages.clear(); canLoadOlder = false;
    const c = conversations.find(x => x.id === activeId);
    $('smtt-chat-current-name').textContent = c ? c.title : 'Conversa';
    $('smtt-chat-subtitle').textContent = c && c.group_source === 'ad' ? 'Grupo corporativo · membros pelo AD' : (c && c.type === 'group' ? 'Grupo administrado pelo CPD' : 'Conversa privada');
    const activeAvatar = $('smtt-chat-active-avatar');
    if (activeAvatar) {
      activeAvatar.replaceChildren();
      activeAvatar.classList.toggle('is-group', !!c && c.type === 'group');
      if (c && c.type === 'group') activeAvatar.append(uiIcon('group'));
      else activeAvatar.textContent = c ? c.title.trim().slice(0,1).toLocaleUpperCase() : '';
    }
    form.hidden = false;
    area.replaceChildren(make('p','smtt-chat-empty','Carregando mensagens…'));
    renderConversations();
    const url = new URL(window.location.href);
    url.searchParams.set('conversation', String(activeId));
    history.replaceState(null, '', url);
    try {
      const data = await api('conversations/' + activeId + '/messages');
      if (activeId !== Number(id)) return;
      canLoadOlder = data.has_more;
      messages.clear();
      for (const m of data.messages || []) messages.set(m.id,m);
      drawMessages(true);
      await api('conversations/' + activeId + '/read', {method:'POST',data:{}});
      await updateSummary();
    } catch (err) { area.replaceChildren(make('p','smtt-chat-error',err.message)); }
    input.focus();
  }
  async function loadOlder() {
    if (busy || !messages.size) return;
    busy = true;
    try {
      const first = Math.min(...messages.keys());
      await fetchMessages(activeId, first);
    } catch (err) { alert(err.message); }
    finally { busy = false; }
  }
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (busy || !activeId) return;
    const text = input.value.trim();
    if (!text || text.length > cfg.maxLength) { input.focus(); return; }
    busy = true;
    try {
      await api('conversations/' + activeId + '/messages', {method:'POST',data:{text}});
      input.value = '';
      await fetchMessages(activeId);
      area.scrollTop = area.scrollHeight;
      await updateSummary();
    } catch (err) { alert(err.message); }
    finally { busy = false; input.focus(); }
  });
  input.maxLength = cfg.maxLength;
  input.addEventListener('keydown', event => {
    if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {event.preventDefault();form.requestSubmit();}
  });
  const emojiMenu = $('smtt-chat-emoji-menu');
  for (const symbol of icons) {
    const button = make('button','',symbol);
    button.type = 'button';
    button.addEventListener('click', () => {input.value += symbol; setEmojiOpen(false); input.focus();});
    emojiMenu.append(button);
  }
  const emojiButton = $('smtt-chat-emoji-button');
  function setEmojiOpen(open) {
    emojiMenu.hidden = !open;
    emojiButton.setAttribute('aria-expanded', open ? 'true' : 'false');
  }
  emojiButton.addEventListener('click', () => setEmojiOpen(emojiMenu.hidden));
  document.addEventListener('click', e => {
    if (!emojiMenu.hidden && !emojiMenu.contains(e.target) && !emojiButton.contains(e.target)) setEmojiOpen(false);
  });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') setEmojiOpen(false); });
  function renderSelected() {
    selectedEl.replaceChildren();
    for (const [id,name] of selectedUsers.entries()) {
      const pill = make('button', 'smtt-chat-pill', name + ' ×');
      pill.type = 'button';
      pill.addEventListener('click',()=>{selectedUsers.delete(id);renderSelected();});
      selectedEl.append(pill);
    }
  }
  function startDialog(group) {
    creatingGroup = group;
    selectedUsers.clear();renderSelected();
    errorEl.textContent = '';
    search.value = '';people.replaceChildren();
    $('smtt-chat-dialog-title').textContent = group ? 'Criar conversa em grupo' : 'Nova conversa privada';
    $('smtt-chat-create-button').textContent = 'Abrir conversa';
    dialog.showModal();search.focus();
  }
  $('smtt-chat-new-direct').addEventListener('click',()=>startDialog(false));
  $('smtt-chat-dialog-close').addEventListener('click',()=>dialog.close());
  search.addEventListener('input', () => {
    clearTimeout(searchTimer);
    const query = search.value.trim();
    people.replaceChildren();
    if (query.length < 2) return;
    searchTimer = setTimeout(async () => {
      try {
        const data = await api('people?q='+encodeURIComponent(query));
        if (search.value.trim() !== query) return;
        people.replaceChildren();
        if (!(data.people || []).length) people.append(make('p', 'smtt-chat-list-empty','Nenhum funcionário encontrado.'));
        for (const user of data.people || []) {
          const button = make('button', 'smtt-chat-person', user.name);
          button.type = 'button';
          button.addEventListener('click',()=>{
            if (!creatingGroup) selectedUsers.clear();
            selectedUsers.set(user.id,user.name);
            renderSelected();
          });
          people.append(button);
        }
      } catch (err) { people.replaceChildren(make('p','smtt-chat-error',err.message)); }
    }, 300);
  });
  $('smtt-chat-create-form').addEventListener('submit', async event => {
    event.preventDefault();
    if (busy) return;
    const ids = [...selectedUsers.keys()];
    if ((!creatingGroup && ids.length !== 1) || (creatingGroup && ids.length < 2)) {
      errorEl.textContent = creatingGroup ? 'Selecione ao menos 2 participantes.' : 'Selecione um funcionário.';
      return;
    }
    busy = true;
    try {
      const data = {type:'direct',recipient_id:ids[0]};
      const created = await api('conversations',{method:'POST',data});
      dialog.close();await updateSummary();await openConversation(created.id);
    } catch (err) { errorEl.textContent = err.message; }
    finally { busy = false; }
  });
  $('smtt-chat-filter').addEventListener('input',renderConversations);
  async function refresh() {
    if (pollInFlight || document.visibilityState !== 'visible') return;
    pollInFlight = true;
    try {
      await updateSummary();
      if (activeId) await fetchMessages(activeId);
    } catch (err) { console.warn('Chat SMTT:',err.message); }
    finally {pollInFlight = false;}
  }
  async function start() {
    try {
      await updateSummary();
      const param = Number(new URL(window.location.href).searchParams.get('conversation'));
      if (Number.isSafeInteger(param) && param > 0) await openConversation(param);
    } catch (err) {list.replaceChildren(make('p','smtt-chat-error',err.message));}
    window.setInterval(refresh, 10000);
  }
  start();
})();
