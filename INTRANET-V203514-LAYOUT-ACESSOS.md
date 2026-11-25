# SMTT Intranet v2.0.35.14 — Layout da Home e Central de Acessos

Alterações sobre v2.0.35.13:

- A Home agora inclui catálogos personalizados como filhos da mesma grade dos cards originais. O layout publicado (ordem, tamanho, visibilidade) aplica-se a ambos.
- Editor de Layout com rolagem própria, barras de rolagem visíveis, auto-scroll ao arrastar, botões de mover e barra de ações fixa no rodapé da área de edição.
- Formulários de Sistemas e Pastas: dois controles independentes — exibir na navbar e exibir na Central de Acessos — mais seleção de um ou vários setores.
- Central de Acessos consulta sistemas e pastas independentemente da escolha de exibir na navbar. O setor deve existir e estar vinculado.
- A exclusão de catálogos personalizados agora fica em Criar módulos, restrita ao CPD e protegida por nonce. Seus itens são enviados à lixeira; os sete módulos padrão não podem ser excluídos.

## Como testar

1. Em Administração > Setores e AD, cadastre o setor teste. Na Home, a Central de Acessos exibirá o setor, respeitando as autorizações.
2. Em Módulos > Sistemas internos, edite o Portal Perícia, marque Central de Acessos e associe o setor teste; salve. Valide a navbar independentemente da opção Central.
3. Abra Layout da Home; role a lista de cards e mova VOTAÇÃO e ATENDE entre os cards fixos. Publique; recarregue a Home e confirme as posições e larguras.
4. Oculte Notícias e publique; verifique que o card desaparece sem apagar notícias; reative e publique.
5. Em Criar módulos, crie um catálogo teste e depois exclua-o. Confirme que ele desaparece do editor/Home e que os itens vinculados foram para a lixeira.

Não houve conexão com AD/FortiGate e não há bloqueio de rede nesta versão. Realizar a homologação funcional no WordPress local antes de implantar no Ubuntu.
