# SMTT Intranet v2.0.35.15 — Correção de layout e ícones

Base: v2.0.35.14. Mudanças limitadas ao editor de Layout da Home e ao estilo dos ícones dos cards personalizados.

- O editor deixou de usar rolagem interna de altura limitada. A lista de cards cresce naturalmente e pode ser percorrida com a rolagem da coluna direita da Central Administrativa.
- Botões Salvar rascunho e Publicar layout saíram da barra sticky que cobria os cards. Ficam após a lista em posição normal.
- Link de atalho para os botões de salvar e instruções de rolagem atualizadas.
- Arrastar próximo ao topo/rodapé agora desloca a coluna principal (ou a janela em telas menores), não um contêiner interno.
- Ícone principal de cada módulo personalizado limitado a 19x19 px, escala dos cards originais. Não modifica a biblioteca nem os ícones dos itens internos.
- Não modifica registros, conteúdo, consultas a Notícias, Ramais, Chat, Access Gate, AD ou FortiGate.

## Homologação local

1. Atualizar e abrir Central Administrativa > Layout da Home.
2. Rolar a coluna direita: ver todos os cards e os botões ao final, sem sobreposição.
3. Testar arrastar, Subir/Descer, Salvar rascunho, prévia, Publicar layout e restaurar.
4. Abrir a Home e verificar cabeçalhos de VOTAÇÃO/ATENDE com ícones proporcionais aos cards padrão.
5. Testar resoluções desktop e celular, inclusive zoom do navegador.

A execução WordPress/AD/FortiGate reais não está coberta por testes estáticos.
