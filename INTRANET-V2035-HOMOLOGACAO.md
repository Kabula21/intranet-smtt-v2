# SMTT Intranet v2.0.35 — Central Administrativa (homologação)

Base: v2.0.34, entregue anteriormente. Esta versão adiciona recursos locais sem integrar-se ao AD/SSO/FortiGate reais.

## Incluído

- Construtor de catálogos de links padronizados, com itens gerenciados na Central e publicação opcional na Home (desativada por padrão).
- Busca de usuários em tempo real no AD, restrita ao CPD, para selecionar SIDs individuais.
- Interface responsiva em `/administracao/`, exclusiva do grupo AD CPD mais grupo exclusivo de administradores, verificados via identidade corporativa.
- Interface `/minha-gestao/` com operações delegadas.
- Gestão frontend de Sistemas, Pastas, Links úteis, Documentos, Agenda, Mural, Base de Conhecimento, Aniversariantes e Ramais, utilizando os mesmos CPTs, metadados, taxonomias e tabelas da instalação atual.
- Inclusão, edição, exclusão (lixeira do WordPress para CPTs), importação de Ramais CSV/XLS e de Aniversariantes CSV.
- Setores, catálogo de grupos e OUs sincronizados, associação de grupos AD a setores existentes.
- Política de permissões por SID de usuário, SID de grupo ou setor, módulo, operação, escopo (todos, próprios, setor, itens específicos), com validade e log de alterações.
- Configurações frontend do Tour, preferências de aniversariantes e preferências de visibilidade no gerenciador.
- Catálogo embarcado Font Awesome Free com 2060 SVGs (solid, regular e brands), com licença e atribuição incluídas; seletor visual e upload de imagens próprias PNG/WEBP/JPEG. Os antigos identificadores de ícones continuam aceitos.
- Ícones selecionados exibidos nos miniacessos de Sistemas/Pastas e nos Links úteis da Home.
- Simulador de eventos do Access Gate: ledger local de `allow_expected` e `revoke_expected` somente para passagens com identidade AD validada. **Nunca há envio ao FortiGate**.
- Cache da Home invalidado após salvar os metadados, preservando a otimização do módulo Ramais.

## Infraestrutura pendente

1. Configurar SSO servidor-a-servidor confiável no WordPress e o filtro `smtt_intranet_verified_ad_identity`, seguindo `INTRANET-V2034-INSTALACAO.md`.
2. Habilitar extensão PHP LDAP no Ubuntu e configurar LDAPS com certificado validado e conta de leitura em `wp-config.php`.
3. Criar grupo AD exclusivo de administradores e associá-lo ao CPD, sincronizar e executar bootstrap `wp smtt-intranet ad-bootstrap --cpd=S-1-... --admin=S-1-...`.
4. Confirmar FSSO/FortiOS e mecanismo real de imposição, revogação e expiração no FortiGate. O simulador **não** aplica regras, bloqueio ou liberação de Internet.
5. Homologar em WordPress real: cadastro, edição, exclusão, permissões e restrições por grupo; testes de carga, HTTPS, acessos diretos aos arquivos e backups.

## Limites da versão

- O painel técnico do WordPress e suas rotas administrativas antigas continuam operantes para contingência até validação do SSO e migração integral. Ocultar o menu não basta para impor o requisito CPD.
- Preferências de módulos na Central controlam a organização do novo gerenciador; não desativam todos os renderizadores públicos antigos.
- O construtor implementado cria catálogos padronizados de links. Módulos com fluxos, campos e comportamentos inéditos ainda exigem desenvolvimento de um construtor mais avançado ou código especializado.
- Uploads de documentos por URL direta não se tornam protegidos pela Intranet; controle de downloads exige proteção no servidor de arquivos.
- A aplicação real das permissões em endpoints legados exige fase de endurecimento antes da substituição definitiva das telas antigas.
- Exibição da Home não é prova de leitura e a API de passagem não impede todas as simulações realizadas por um cliente controlado pelo usuário.
- O site institucional `smt-noticias` não foi editado. Testes reais com notícias externas e AD não são possíveis fora da infraestrutura SMTT.

## Implantação segura

Faça backup dos dados e do plugin. Instale **somente em homologação**, preferencialmente num clone do WordPress. Não ative políticas de firewall. Configure LDAPS e SSO, sincronize grupos, selecione CPD e grupo administrador por WP-CLI, abra `/administracao/` e teste. Mantenha a v2.0.34 de produção até validar a regressão completa.

### Licenças de terceiros

Catálogo Font Awesome Free: ícones sob CC BY 4.0. Veja `assets/enterprise/icons/FONT-AWESOME-LICENSE.txt`. Logotipos de marcas são propriedade de seus titulares.
