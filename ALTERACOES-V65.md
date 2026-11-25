# SMTT Intranet v2.0.25 — Notícias em background

- A Home não faz mais requisições HTTP ao portal institucional durante o carregamento.
- As 3 notícias são mantidas em um snapshot local persistente no WordPress.
- Atualização automática via WP-Cron a cada 15 minutos, fora da requisição do colaborador.
- Trava de atualização impede múltiplos processos simultâneos quando vários usuários acessam ao mesmo tempo.
- Se o portal estiver indisponível, a Intranet mantém a última versão válida sem atrasar a Home.
- Fotos são baixadas para cache estático em `wp-content/uploads/smtt-intranet/news-static/`.
- Quando a listagem não fornece uma imagem válida, o sincronizador procura `og:image`, `twitter:image` ou a primeira imagem real na página da notícia.
- Imagens pequenas de placeholder/lazy-load são rejeitadas.
- O cache de imagens remove arquivos antigos que não pertencem mais às 3 notícias atuais, evitando crescimento indefinido em disco.
- O botão administrativo **Atualizar agora** faz sincronização manual protegida por nonce e mostra a última sincronização.
- Nenhum `admin-ajax.php` é usado para servir fotos de notícias.
