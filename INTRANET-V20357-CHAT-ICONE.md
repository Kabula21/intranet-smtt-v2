# v2.0.35.7 — Ícone WordPress e Chat SMTT

- Menu WordPress: símbolo branco derivado da logomarca original SMTT; imagem compacta, sem círculo colorido, sem sobreposição.
- Chat: identificado que as conversas dependem do plugin externo **Better Messages** (presente no backup original da instalação).
- Página `/chat-smtt/`: criada apenas se não existir; conteúdos existentes não são sobrescritos. A página gerenciada combina `[smtt_chat_header]` com `[smtt_chat_area]`.
- Com Better Messages ativo, a área carrega seu shortcode oficial `[better_messages]`.
- Sem Better Messages, a página informa a indisponibilidade e orienta somente administradores sobre a instalação. Não finge ter mensagens nem cria armazenamento de conversas.
- A Intranet não instala nem altera o plugin externo automaticamente.
- Mantidos sem alterações Notícias, Ramais, Access Gate, AD, biblioteca de ícones e Central Administrativa.
