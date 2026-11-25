# SMTT Intranet v2.0.35.18 — Menu de Aplicativos

## O que mudou

- A Central Administrativa ganhou **Menu de Aplicativos**; o menu de nove pontos da Home agora lê os aplicativos publicados, não mais uma lista fixa no PHP.
- Os nove atalhos originais (Google, Gmail, Drive, Documentos, Planilhas, Apresentações, Tradutor, Wikipédia e Calculadora) permanecem como configuração inicial, com as marcas e links anteriores.
- O CPD pode criar, editar, ordenar, ocultar, excluir atalhos, escolher ícones pela biblioteca existente e restringir visibilidade por usuário WordPress/SSO, setor ou grupo AD.
- O CPD pode salvar um rascunho, pré-visualizá-lo, publicar e restaurar a versão publicada anterior.
- Sistemas Internos ganharam a opção **Exibir no Menu de Aplicativos**. Ao ativá-la, a referência ao sistema entra no **rascunho** do menu. Depois de publicar, título, URL e ícone do sistema vêm do cadastro original. Não há duplicação do registro.
- Remover a referência de um Sistema Interno do rascunho e publicar desmarca a opção de presença nesse menu **sem excluir o sistema**.
- O menu mantém a grade de três colunas, com rolagem interna para grandes quantidades de aplicativos.

## Como testar no WordPress local

1. Atualize o plugin e abra **Intranet → Administração (teste) → Menu de Aplicativos**.
2. Confira os nove aplicativos originais, mude a ordem por ↑/↓ e clique em **Salvar rascunho**.
3. Clique em **Pré-visualizar rascunho**. O menu de nove pontos deverá mostrar a nova ordem apenas para o CPD na prévia.
4. Volte à Central e clique em **Publicar rascunho**. Confira a Home normal.
5. Adicione um atalho independente com URL HTTP(S), ícone e subtítulo; salve e publique.
6. Em **Módulos → Sistemas Internos**, edite um sistema de endereço web, marque **Exibir no Menu de Aplicativos**, salve e volte ao Menu de Aplicativos para publicar.
7. Teste ocultar um aplicativo, restaurar a publicação anterior e as permissões por usuário.

## Segurança e dependências

- Somente o CPD autorizado pelo Active Directory, ou o administrador de homologação local previamente habilitado, pode alterar a configuração.
- As verificações ocorrem no servidor: esconder links não substitui a autorização dos sistemas externos ou dos recursos de destino.
- Regras por setor e grupo exigem **SSO e AD validados**. Na ausência dessas informações, os aplicativos restritos por AD não são exibidos.
- No ambiente local, a seleção de usuários WordPress pode ser testada imediatamente. Em produção, os usuários precisam estar provisionados/sincronizados via SSO.
- Alterações nas outras áreas da Intranet não foram necessárias; não modificar o plugin institucional de notícias.

**Validação pendente:** conferir o fluxo completo no WordPress local, com persistência real no banco e mais de uma conta, antes de instalar em produção.
