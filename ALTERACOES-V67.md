# SMTT Intranet 2.0.27 — Notícias rápidas, sem fila de imagens

## Ajustes

- A sincronização de Notícias agora termina assim que título, resumo, data, link e URL da imagem são atualizados.
- Foram removidos os jobs individuais que baixavam cada foto no servidor e podiam ficar pendentes quando o portal público estava lento.
- A Home continua sem consultar páginas de notícias via PHP durante a abertura.
- Quando existe uma imagem local válida de versões anteriores, ela continua sendo aproveitada. Caso contrário, o navegador usa diretamente a URL pública da imagem, sem passar por `admin-ajax.php` nem por proxy PHP.
- O parser da página de Notícias foi ampliado para reconhecer `src`, lazy-load, `srcset`, `<source>`, atributos de thumbnail e imagens aplicadas por `background-image`.
- Cards antigos preservam a URL remota de imagem mesmo quando nunca conseguiram criar um cache local.
- Se uma imagem local antiga falhar, o front tenta automaticamente a URL pública antes de remover a miniatura.
- Eventos antigos de download de imagens da v2.0.26 são limpos do WP-Cron uma única vez na atualização.

## Resultado

O servidor da Intranet não fica mais preso baixando imagens do portal institucional. A sincronização de conteúdo é independente das fotos e a interface continua usando `loading=lazy` para as miniaturas.
