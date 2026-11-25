# SMTT Intranet V2 — Tour guiado (v2.0.19)

## Novo módulo: Tour da Intranet V2

- Novo card administrativo **Tour da Intranet V2**, separado do Mural Interno.
- Nova capability `smtt_manage_tour`, atribuída ao perfil Administrador.
- Tela de boas-vindas configurável com:
  - ativação/desativação;
  - versão do Tour;
  - atraso de abertura;
  - título e mensagem;
  - placeholder `{nome}` para personalização com o primeiro nome do colaborador;
  - imagem opcional selecionada pela biblioteca de mídia do WordPress.
- Detecção automática da orientação da imagem:
  - horizontal: apresentação ampla;
  - vertical/quadrada: composição lateral no desktop;
  - responsividade automática no celular.

## Tour no front-end

O Tour destaca os próprios componentes da home, sem alterar a lógica deles:

1. Busca Global;
2. Central de Acessos;
3. Mural Interno;
4. Agenda;
5. Aniversariantes.

Cada etapa possui texto curto, acolhedor e profissional, com navegação **Anterior**, **Próximo**, **Pular Tour** e fechamento por `X`/`Esc`.

Ao final, há uma tela de conclusão com mensagem personalizada.

## Controle de visualização

- Ao concluir ou dispensar o Tour, a versão atual é registrada em `user_meta` (`smtt_intranet_tour_seen_version`).
- O mesmo Tour não volta a aparecer para o mesmo usuário, mesmo em outro computador.
- Para reapresentar a novidade a todos, basta incrementar **Versão do Tour** no painel.
- A pré-visualização administrativa não grava o Tour como visualizado.

## Isolamento e segurança

- O módulo não reutiliza posts, metadados ou regras do Mural.
- CSS e JavaScript públicos do Tour estão em arquivos próprios (`tour.css` e `tour.js`).
- O registro de visualização usa AJAX autenticado com nonce do WordPress.
- O Tour só carrega seus assets quando necessário.
