# SMTT Intranet v2.0.35.12 — Catálogos, Documentos e Wiki SMTT

Base: v2.0.35.11 (pré-servidor/homologação). Mudanças restritas à gestão dos três módulos, seu frontend e serviço de upload.

## Módulos personalizados
- Criar/editar catálogo pelo botão Editar, sem digitar ID.
- Botão Adicionar item abre Recursos Personalizados com o catálogo pré-selecionado.
- Escolha obrigatória do catálogo ao salvar um item.
- Módulo publicado aparece na Home mesmo sem links/itens, com estado vazio.
- Item sem URL é exibido como cartão informativo, não como link quebrado.
- Posição: início do painel (após banner) ou após a Central de Acessos.
- Pré-visualização do catálogo em Criar módulos.
- Registros legados sem vínculo não são alterados automaticamente: edite cada item e selecione o catálogo correto.

## Documentos e Wiki
- Upload direto no formulário do frontend: PDF, DOC/DOCX, XLS/XLSX, PPT/PPTX, ODT/ODS, TXT e CSV, até 15 MB por arquivo, sujeito também aos limites do PHP/servidor.
- Mídias públicas continuam compatíveis com a Biblioteca de Mídia do WordPress; URLs diretas NÃO são apropriadas para documentos sigilosos.
- Para upload privado, defina no wp-config.php **antes** de carregar WordPress:

```php
define('SMTT_INTRANET_PRIVATE_FILES_DIR', '/srv/smtt-intranet-private');
```

Crie o diretório fora de `ABSPATH` e do document root, com escrita exclusiva do usuário PHP-FPM/servidor web. A pasta **não** deve ser servida pelo Nginx/Apache nem estar sob aliases ou mounts públicos. Para um upload protegido, marque o checkbox exibido quando o diretório estiver configurado. O download será entregue pelo WordPress após verificar sessão e acesso ao registro. Recomenda-se varredura antivírus, backup e limites de PHP/FPM, além de testes das ACLs de setor/AD.

- A Wiki aceita artigo interno com editor WordPress (texto formatado), arquivo anexado ou link externo.
- O clique nos artigos agora abre um visualizador interno, o arquivo ou o link, conforme o formato, mantendo fallback para links antigos.
- Administração de categorias de Documentos e Wiki na Central (criação e edição; sem exclusão destrutiva automática).
- Formulários com campos condicionais conforme o tipo.
- Registros atuais mantidos. Arquivos anteriormente publicados por URL direta permanecem públicos, mesmo após mudar os novos fluxos; migrá-los/revisá-los será necessário caso sejam sigilosos.

## Como validar no WordPress local
1. Criar um módulo `VOTAÇÃO`, habilitar na Home e escolher a posição no início.
2. Sem cadastrar itens, verificar que aparece um estado vazio na Home.
3. Cadastrar um item `teste` sem URL, vincular a `VOTAÇÃO` e salvar. Verificar que aparece como informação.
4. Cadastrar outro item com URL HTTPS e confirmar a abertura em outra aba.
5. Criar categoria de Documentos e enviar um PDF não confidencial; verificar o link de download.
6. Criar artigo interno na Wiki, um registro com upload e outro com URL externa; verificar as três aberturas.
7. Testar com funcionário sem autorização para o setor e com sessão expirada.
8. Configurar diretório privado fora da área pública e repetir o teste de download, incluindo tentativa sem login.
9. Confirmar funcionamento de Notícias, Ramais, Chat e Access Gate.

**Importante:** estes fluxos passaram por sintaxe e testes isolados de renderização de catálogos. Upload, editor rico, banco de dados, permissões reais e visual requerem homologação no WordPress/Ubuntu. O FortiGate permanece em simulação; a autenticação corporativa ainda requer SSO/LDAPS real.
