# SMTT Intranet 2.0.26 — sincronização resiliente de Notícias

## Correções

- A sincronização das Notícias deixou de depender de timeouts agressivos de 2–4 segundos.
- O portal público e o RSS agora recebem uma janela maior apenas no processo em background; a Home continua sem fazer requisições externas.
- Quando a origem já apresentou timeout, os endpoints REST não são chamados na mesma rodada, evitando prender um worker PHP desnecessariamente.
- O botão **Atualizar agora** do painel não executa mais a consulta externa dentro da tela do administrador. Ele agenda a atualização em segundo plano e retorna imediatamente.
- As imagens foram separadas da sincronização principal: cada notícia recebe um job próprio para baixar/cachear sua foto, evitando uma fila longa dentro de uma única execução.
- Imagens já armazenadas são reaproveitadas enquanto uma nova sincronização acontece.
- O downloader de imagens passou a tolerar melhor o portal lento, com timeout exclusivo de background e retry automático.
- As requisições externas usam HTTP/1.1, descompressão e cabeçalhos mais compatíveis com o portal, sem forçar `Cache-Control: no-cache`.
- Ao desativar o plugin, os novos jobs de imagem também são removidos do WP-Cron.

## Comportamento

A abertura da Intranet continua utilizando apenas o snapshot local. Se o portal institucional estiver lento ou indisponível, os colaboradores continuam vendo a última versão válida sem esperar a rede externa.
