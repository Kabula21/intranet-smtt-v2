# SMTT Intranet v2.0.22 — Tour persistente e notícias com imagens

- O Tour agora registra a rodada no perfil do usuário assim que ele inicia, fecha ou pula a apresentação.
- Atualizar a página, fechar o navegador, sair e entrar novamente ou acessar por outro computador não reapresenta a mesma rodada automaticamente.
- Adicionado botão discreto **Rever Tour** no front-end para o próprio colaborador repetir a apresentação quando quiser.
- Adicionado no backend o botão **Disparar Tour novamente para todos**, que cria uma nova rodada sem apagar os históricos anteriores.
- A gravação do Tour ganhou redundância com `fetch keepalive`, `sendBeacon` e fallback local por usuário/rodada.
- As imagens das Notícias passam por um proxy/cache interno autenticado, evitando falhas de carregamento direto do portal externo e sem criar anexos na Biblioteca de Mídia.
- Cache de notícias renovado para forçar uma leitura limpa após a atualização.
