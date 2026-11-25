# SMTT Intranet v2.0.35.1 — Atalho no WordPress

## Mudança solicitada

- O menu lateral do WordPress mantém apenas **SMTT Intranet**.
- Ao clicar nesse item, o painel antigo de cards e gerenciadores foi substituído por uma tela simples com o botão **Abrir Intranet**, que abre a Home em uma nova aba.
- A administração de conteúdos e módulos da Intranet permanece na Central Administrativa frontend em `/administracao/`. A opção de administração na Home só aparece para usuários validados pelos grupos AD definidos.
- Removido o redirecionamento automático do menu WordPress para a Central Administrativa, para que o botão apareça mesmo quando o AD não estiver configurado.
- Removido o aviso de navegação para gerenciadores antigos nas telas técnicas do WordPress.

## Preservação e limites

- Não foram alteradas as classes de Notícias, Ramais, Home, Access Gate, Active Directory ou FortiGate.
- Os endpoints/páginas administrativos legados continuam presentes para contingência técnica. A retirada definitiva exige homologar a Central e validar SSO, autorização por grupos e funcionalidades equivalentes.
- A Central Administrativa permanece protegida pelo SSO e pelos grupos corporativos. Sem configuração real de SSO/AD, `/administracao/` continua retornando acesso restrito, por segurança.
- A mudança é apenas no painel WordPress do plugin, sem interferir em outros plugins e menus técnicos nativos.

## Homologação

1. Faça backup e instale em homologação.
2. Acesse `wp-admin/admin.php?page=smtt-intranet`: deve aparecer apenas o botão **Abrir Intranet**.
3. Clique e verifique se a Home abre em uma nova aba.
4. Verifique que os conteúdos de Notícias, Ramais e Access Gate continuam como antes.
5. Com SSO/AD validado, verifique a entrada Administração na Home e a Central em `/administracao/`.
