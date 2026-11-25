# SMTT Intranet — Alterações v49

Versão 2.0.9.

## Notícias
- Página pública `/noticias/` passa a ser a origem preferencial, evitando depender da REST API lenta.
- Parser HTML robusto para título, resumo, link, data e imagem.
- RSS passa a ser lido diretamente por HTTP/XML, sem depender do `fetch_feed()` que estava retornando URL inválida.
- REST fica como último fallback, sem descoberta automática e com timeout curto.
- Última resposta válida é preservada por 7 dias e usada se o portal oscilar.
- Falhas ficam em cache por apenas 1 minuto.
- Removido cache duplo de Notícias na home.
- URL vazia ou inválida usa automaticamente `https://smtt.aracaju.se.gov.br`.
