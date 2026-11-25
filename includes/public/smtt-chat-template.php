<?php
/** Aplicação de mensagens própria, sem header/footer do tema WordPress. */
if (!defined('ABSPATH')) { exit; }
if (!defined('DONOTCACHEPAGE')) { define('DONOTCACHEPAGE', true); }
nocache_headers();
$chat_enabled = SMTT_Intranet_Native_Chat::enabled();
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Chat SMTT | Intranet V2</title>
<link rel="icon" href="<?php echo esc_url(SMTT_INTRANET_URL . 'assets/chat/chat-mark.svg'); ?>" type="image/svg+xml">
<?php wp_head(); ?>
</head>
<body class="smtt-native-chat-page">
<?php wp_body_open(); ?>
<!-- Ícones locais, sem dependência de fontes externas. -->
<svg xmlns="http://www.w3.org/2000/svg" class="smtt-chat-svg-sprite" aria-hidden="true" focusable="false">
    <symbol id="smtt-i-back" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6M9 12h12"/></symbol>
    <symbol id="smtt-i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
    <symbol id="smtt-i-group" viewBox="0 0 24 24"><path d="M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 20v-2a4 4 0 0 0-3-3.87M16 2.13a4 4 0 0 1 0 7.75"/></symbol>
    <symbol id="smtt-i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M5 21v-2a7 7 0 0 1 14 0v2"/></symbol>
    <symbol id="smtt-i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></symbol>
    <symbol id="smtt-i-moon" viewBox="0 0 24 24"><path d="M20.9 13a9 9 0 0 1-9.9-9.9A9 9 0 1 0 20.9 13Z"/></symbol>
    <symbol id="smtt-i-sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></symbol>
    <symbol id="smtt-i-send" viewBox="0 0 24 24"><path d="m22 2-7 20-4-9-9-4 20-7ZM22 2 11 13"/></symbol>
    <symbol id="smtt-i-smile" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01"/></symbol>
    <symbol id="smtt-i-chat" viewBox="0 0 24 24"><path d="M20 11.5a8.5 8.5 0 0 1-8.5 8.5 9 9 0 0 1-3.2-.6L3 21l1.6-5.3A8.5 8.5 0 1 1 20 11.5Z"/><path d="M8 10h8m-8 4h5"/></symbol>
    <symbol id="smtt-i-close" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></symbol>
</svg>
<div class="smtt-chat-layout" id="smtt-chat-app">
    <aside class="smtt-chat-side" aria-label="Navegação do Chat SMTT">
        <div class="smtt-chat-brand">
            <a href="<?php echo esc_url(SMTT_Intranet_Home_Routing::url() ?: home_url('/')); ?>" class="smtt-chat-back" aria-label="Voltar à Intranet" title="Voltar à Intranet"><svg class="smtt-chat-ui-icon"><use href="#smtt-i-back"/></svg></a>
            <img class="smtt-chat-brand-mark" src="<?php echo esc_url(SMTT_INTRANET_URL . 'assets/chat/chat-mark.svg'); ?>" alt="" width="43" height="43">
            <div class="smtt-chat-brand-label"><strong>Chat SMTT</strong><small>Comunicação interna</small></div>
        </div>
        <div class="smtt-chat-side-toolbar">
            <div><h2>Conversas</h2><p>Fale com sua equipe</p></div>
            <div class="smtt-chat-side-actions">
                <button type="button" id="smtt-chat-new-direct" class="smtt-chat-icon-button smtt-chat-new-btn" aria-label="Nova conversa" title="Nova conversa"><svg class="smtt-chat-ui-icon"><use href="#smtt-i-plus"/></svg></button>

            </div>
        </div>
        <label class="smtt-visually-hidden" for="smtt-chat-filter">Buscar conversas</label>
        <div class="smtt-chat-search-wrap"><svg class="smtt-chat-ui-icon" aria-hidden="true"><use href="#smtt-i-search"/></svg><input id="smtt-chat-filter" type="search" placeholder="Pesquisar conversas..." autocomplete="off"></div>
        <div id="smtt-chat-conversations" class="smtt-chat-conversations" aria-label="Minhas conversas" aria-live="polite"></div>
        <div class="smtt-chat-profile"><span class="smtt-chat-my-avatar" aria-hidden="true"><?php echo esc_html(strtoupper(substr(wp_get_current_user()->display_name ?: wp_get_current_user()->user_login, 0, 1))); ?></span><div><strong><?php echo esc_html(wp_get_current_user()->display_name ?: wp_get_current_user()->user_login); ?></strong><span>Conta corporativa</span></div></div>
    </aside>
    <main class="smtt-chat-main" aria-label="Mensagens">
        <header class="smtt-chat-conversation-head">
            <div class="smtt-chat-heading-content">
                <div class="smtt-chat-active-avatar" id="smtt-chat-active-avatar" aria-hidden="true"><svg class="smtt-chat-ui-icon"><use href="#smtt-i-chat"/></svg></div>
                <div><h1 id="smtt-chat-current-name">Chat interno</h1><p id="smtt-chat-subtitle">Selecione uma conversa ou inicie uma nova</p></div>
            </div>
            <button type="button" id="smtt-chat-theme-toggle" class="smtt-chat-icon-button smtt-chat-theme-toggle" aria-label="Ativar tema escuro" title="Alternar tema claro/escuro" aria-pressed="false"><svg class="smtt-chat-ui-icon smtt-chat-icon-moon"><use href="#smtt-i-moon"/></svg><svg class="smtt-chat-ui-icon smtt-chat-icon-sun"><use href="#smtt-i-sun"/></svg><span class="smtt-chat-theme-label">Tema</span></button>
        </header>
        <?php if (!$chat_enabled): ?>
            <div class="smtt-chat-empty-state"><img src="<?php echo esc_url(SMTT_INTRANET_URL . 'assets/chat/chat-mark.svg'); ?>" alt="" width="72" height="72"><h2>Chat desativado</h2><p>O serviço está desativado pela administração da Intranet.</p></div>
        <?php else: ?>
            <div id="smtt-chat-message-area" class="smtt-chat-message-area" aria-label="Mensagens"><div class="smtt-chat-empty-state"><img src="<?php echo esc_url(SMTT_INTRANET_URL . 'assets/chat/chat-mark.svg'); ?>" alt="" width="76" height="76"><h2>Converse com a equipe</h2><p>Escolha uma conversa à esquerda ou clique no botão <strong>+</strong> para começar.</p><span>Mensagens, emojis e links — sem anexos</span></div></div>
            <form id="smtt-chat-compose" class="smtt-chat-compose" hidden>
                <div id="smtt-chat-emoji-menu" class="smtt-chat-emoji-menu" hidden></div>
                <button type="button" class="smtt-chat-icon-button smtt-chat-emoji-button" id="smtt-chat-emoji-button" title="Inserir emoji" aria-label="Inserir emoji" aria-expanded="false"><svg class="smtt-chat-ui-icon"><use href="#smtt-i-smile"/></svg></button>
                <label class="smtt-visually-hidden" for="smtt-chat-message-input">Mensagem</label>
                <textarea id="smtt-chat-message-input" rows="1" placeholder="Digite sua mensagem..." aria-label="Mensagem"></textarea>
                <button type="submit" class="smtt-chat-send" aria-label="Enviar mensagem" title="Enviar mensagem"><svg class="smtt-chat-ui-icon"><use href="#smtt-i-send"/></svg><span>Enviar</span></button>
            </form>
            <div class="smtt-chat-help">Enter para enviar · Shift + Enter para nova linha · Texto, emojis e links</div>
        <?php endif; ?>
    </main>
</div>
<dialog id="smtt-chat-new-dialog" class="smtt-chat-dialog" aria-labelledby="smtt-chat-dialog-title">
    <form method="dialog" id="smtt-chat-create-form">
        <div class="smtt-chat-dialog-heading"><h2 id="smtt-chat-dialog-title">Nova conversa</h2><button type="button" id="smtt-chat-dialog-close" aria-label="Fechar"><svg class="smtt-chat-ui-icon"><use href="#smtt-i-close"/></svg></button></div>
        <p class="smtt-chat-dialog-intro">Encontre funcionários na Intranet para começar a conversar.</p>

        <label>Buscar funcionário<input id="smtt-chat-person-search" type="search" placeholder="Digite pelo menos 2 letras" autocomplete="off"></label>
        <div id="smtt-chat-selected" class="smtt-chat-selected"></div>
        <div id="smtt-chat-people-results" class="smtt-chat-people-results" role="listbox"></div>
        <p id="smtt-chat-create-error" class="smtt-chat-error" role="alert"></p>
        <button type="submit" id="smtt-chat-create-button" class="smtt-chat-primary">Iniciar conversa</button>
    </form>
</dialog>
<?php wp_footer(); ?>
</body>
</html>
