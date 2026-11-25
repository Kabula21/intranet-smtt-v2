# SMTT Intranet v2.0.35.13 — Editor Visual da Home

Esta versão evolui a v2.0.35.12 sem migrar ou eliminar cadastros. É destinada inicialmente à homologação no WordPress local.

## Como utilizar

1. Abra **Central Administrativa → Layout da Home**.
2. Mova os cards segurando a alça ⠿; também há botões **Subir** e **Descer** e atalhos Alt+↑ / Alt+↓ ao focar a alça.
3. Para cada módulo, altere **Exibir na Home**, **Largura** e **Altura**. Em **Nome, subtítulo, ícone e cor**, configure a identificação visual opcionalmente.
4. Clique em **Salvar rascunho**. A Home publicada não é modificada.
5. Abra **Prévia na Home (rascunho salvo)**, apenas com uma conta CPD autorizada, para ver o resultado real.
6. Após conferir, clique em **Publicar layout**.
7. Se necessário, use **Restaurar layout publicado anteriormente**. Uma cópia anterior é mantida para reversão.

**Importante:** o seletor Celular na Central é apenas uma simulação da distribuição; faça também testes reais no navegador em tamanhos diferentes.

## Novos catálogos

O CPD continua criando catálogos em **Criar módulos**, e os itens em **Módulos → Recursos personalizados**. Após cadastrar um catálogo novo, ele aparecerá em **Layout da Home**, inicialmente oculto. Ative sua exibição, escolha sua posição e publique o layout. Catálogos antigos permanecem cadastrados.

## Documentos e Wiki

Os formulários e menus não exigem categorias. As categorias antigas e seus relacionamentos permanecem preservados, evitando remoção de informações históricas. O editor de artigos e o upload de Documentos/Wiki são mantidos.

## Comportamento de segurança

- Apenas contas autorizadas do CPD podem salvar, publicar ou restaurar o layout.
- O código utiliza proteção por nonce em operações de gravação e prévias.
- Ocultar um card **não revoga** a permissão de acesso ao conteúdo; continue aplicando as permissões nos módulos e no AD.
- Não é realizada alteração automática no plugin de Notícias institucional nem no FortiGate.
- O Access Gate continua em modo de auditoria até integração com a infraestrutura.

## Testes recomendados

- Testar nos navegadores desktop e celular (inclusive rolagem do conteúdo dos cards de tamanho reduzido).
- Criar catálogos ATENDE/VOTAÇÃO e confirmar que ficam na mesma grade dos cards padrão.
- Ocultar e reexibir Notícias, Mural, Agenda, Wiki, Central de Acessos, Links úteis e Aniversariantes.
- Testar mudanças na identidade visual sem alterar o conteúdo dos módulos.
- Abrir a prévia com uma conta comum: a visualização especial não deve ser autorizada.
- Verificar rascunho, publicação, restauração e persistência após recarregar a página.
- Conferir Notícias, Ramais, Chat e Wiki em homologação.
