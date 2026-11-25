# SMTT Intranet — v2.0.32

## Correção de Notícias

- Nenhuma alteração foi feita no plugin institucional `smt-noticias`.
- A Intranet passa a consultar primeiro o REST nativo do WordPress no post type real `smt_noticia`.
- A imagem principal vem da imagem destacada retornada por `_embed`; se necessário, há fallback pela página individual.
- O subtítulo é lido da classe pública `.smt-single-subtitle` da página individual, pois o post_meta não é exposto pelo REST nativo.
- Os cards exibem o subtítulo real; quando uma notícia não possui subtítulo, usam o resumo como fallback.
- A coleta continua apenas em background (WP-Cron/atualização manual), sem requisições externas durante a abertura da Home.
- Uma sincronização é agendada automaticamente uma única vez após a atualização para esta versão.

Nenhum outro módulo, layout ou regra da Intranet foi alterado.
