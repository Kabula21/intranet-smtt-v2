# SMTT Intranet v2.0.35.11 — Preparação do servidor e homologação

**Situação:** pacote de homologação endurecido, não aprovação de produção.
**Base preservada:** v2.0.35.10. Não migrar a partir de pacotes antigos.

## Alterações preventivas desta versão

1. A Home exige autenticação WordPress/SSO, inclusive em hosts locais. A exceção anterior dependia de `HTTP_HOST`, controlável por uma requisição HTTP.
2. O endpoint AJAX do diretório de Ramais exige usuário autenticado; sua otimização e cache HTML foram mantidos.
3. A prévia administrativa de desenvolvimento exige `WP_ENVIRONMENT_TYPE` explícito (`local` ou `development`), além de usuário administrador e hostname de testes. Nunca fica automaticamente habilitada por um domínio terminado em `.local`.
4. Formulários de módulos validam setor, taxonomias e alcance antes de persistir post/meta, evitando alterações parciais após uma negativa.
5. Tabelas AD, permissões/auditoria e eventos simulados só registram a versão do esquema após constatar que foram criadas.
6. A Central Administrativa só envia o catálogo completo de ícones nas telas que realmente precisam dele.
7. Novo diagnóstico somente leitura: `wp smtt-intranet preflight`.

## Configuração local de desenvolvimento

Para continuar usando a prévia do CPD com um WordPress local (sem AD), configure no `wp-config.php`, antes de `/* That's all, stop editing! */`:

```php
define('WP_ENVIRONMENT_TYPE', 'local');
```

Use somente com um domínio de desenvolvimento como `localhost`, `127.0.0.1`, `*.local` ou `*.test`.
O bypass local NÃO cria identidade corporativa real, nem concede autorização de rede. Acesso à Home e ao Chat continua exigindo login WordPress.

## Configuração do Ubuntu em homologação

1. Provisionar domínio de homologação e certificado HTTPS válido (rede interna ou CA corporativa), resolver DNS, e habilitar reescrita de URLs amigáveis (`/administracao/`, `/chat-smtt/`, `/minha-gestao/`).
2. Instalar/ativar PHP adequado ao WordPress, extensões necessárias (em especial `ldap`, `mysqli`, `mbstring`, `json`, `curl`, `openssl`) e driver de banco. Confirmar memória PHP, `upload_max_filesize` e permissões do servidor web.
3. Instalar WordPress e banco separados de produção. Fazer backup consistente antes de trocar o plugin (banco + `wp-content/uploads` + configuração do WordPress); manter ZIP anterior para rollback.
4. Instalar o ZIP desta versão pelo WordPress, verificar versão instalada e salvar links permanentes. Confirmar se a Home foi detectada/gerada corretamente.
5. Executar no diretório do WordPress: `wp smtt-intranet preflight` (WP-CLI). Nada disso testa o login AD ou FortiGate por si só.
6. Configurar ambiente de produção como `define('WP_ENVIRONMENT_TYPE', 'production');` e `define('SMTT_INTRANET_DISABLE_LOCAL_PREVIEW', true);` no `wp-config.php` do servidor definitivo.
7. Isolar `/wp-admin/` e `/wp-login.php` por redes e papéis técnicos. Algumas telas antigas ainda usam permissões WordPress genéricas; sua desativação por grupo AD depende de homologação adicional.
8. Evitar cache público compartilhado em páginas autenticadas (`/`, `/administracao/`, `/minha-gestao/`, `/chat-smtt/`) e nas APIs/AJAX de conteúdo pessoal. Excluir essas rotas do cache de página em proxy/CDN/Nginx.

### WP-Cron

Para sincronização confiável, configurar cron real do Ubuntu como usuário do serviço web e, *somente após* testar o agendamento, definir `DISABLE_WP_CRON` como `true` no `wp-config.php`.

Com WP-CLI instalado, exemplo esquemático (ajustar caminhos e usuário):

```cron
*/5 * * * * cd /CAMINHO/DO/WORDPRESS && /CAMINHO/DO/WP-CLI/wp cron event run --due-now --quiet
```

Não executar o cron como root. Verificar `wp cron event list` e a execução dos jobs de Notícias, AD e Access Gate. A sincronização AD do plugin é agendada a cada 15 min, mas exige credenciais válidas.

## Configuração AD e SSO (ainda NÃO validada)

1. Integração SSO deve autenticar a sessão Windows e associar a pessoa a uma conta WordPress única, com identificador imutável. O plugin depende do filtro servidor-a-servidor `smtt_intranet_verified_ad_identity`; **não há adaptador de SSO pré-selecionado ou testado no pacote**.
2. Configurar acesso a controlador de domínio via LDAPS, certificado CA confiável, conta de leitura e firewall entre Ubuntu e AD. As constantes `SMTT_AD_LDAPS_URL`, `SMTT_AD_BASE_DN`, `SMTT_AD_BIND_DN`, `SMTT_AD_BIND_PASSWORD` deverão estar em configuração protegida fora de repositórios/ZIPs; nunca enviar senha via chat.
3. Confirmar `wp smtt-intranet ad-status`, executar `wp smtt-intranet ad-sync`, verificar descoberta de grupos e OUs e configurar SIDs de `CPD` e do grupo EXCLUSIVO de administração por bootstrap técnico.
4. Testar conta CPD autorizada, CPD sem autorização, funcionário de outro setor, troca de grupos, conta desativada, falha LDAPS e grupo novo.
5. Revisar vínculos do setor: a Home e algumas telas legadas ainda consultam `smtt_user_sector` e `manage_options`. Isso não equivale à política corporativa por grupos; validar e migrar antes do rollout amplo.

## Chat SMTT

- Testar com duas contas WordPress e dois navegadores separados: pesquisa, conversa privada, envio, leitura, contagem de não lidas, tema, submenu da Home.
- Testar grupo manual criado pelo CPD e que usuários sem CPD não consigam criar grupos nem chamar a API diretamente.
- Com AD, testar canal por grupo corporativo, inclusão/remoção, grupos aninhados, autorização após revogação e acesso ao histórico anterior à entrada.
- Fazer testes de carga proporcional aos usuários simultâneos; o chat consulta a API periodicamente (10 s no chat completo e 30 s na Home).
- O chat não envia anexos e não migra automaticamente históricos de Better Messages.

## Notícias, Ramais e conteúdos

- Validar Notícias **com o plugin `smt-noticias` do site institucional sem alterá-lo**; confirmar imagens 1/2 e subtítulo, além do cron/snapshot.
- Validar Ramais: pesquisa, pré-carregamento, importação CSV, invalidação de cache e acesso somente autenticado.
- Testar setores, Sistemas, Pastas, Links, Documentos, Agenda, Mural, aniversariantes, biblioteca de ícones, Home em desktop/mobile, Tour e criação de catálogos.
- Links diretos de arquivos/documentos restritos precisam de proteção no repositório/servidor de arquivos. Esconder links na Intranet **não** protege o recurso externo.

## Access Gate e FortiGate

- O Access Gate registra a passagem diária e mantém uma API de consulta de estado em **modo auditoria**. Não há liberação/bloqueio real da Internet neste pacote.
- A espera na Home e o tíquete de servidor dificultam chamadas simples, mas **não comprovam leitura** e não são prova resistente a automação maliciosa.
- Antes da ativação de qualquer bloqueio: identificar FortiOS, validar FSSO ou outro mecanismo de identidade, projetar adaptador de autorização/revogação, configurar políticas por usuário, testar troca de máquinas/usuários, expiração, encerramento de sessões já abertas, IPv6, exceções DNS/AD/SSO/Intranet e fail-closed controlado.
- Fazer rollout em auditoria, piloto, depois setores. Nunca ativar bloqueio direto em produção sem reversão imediata.

## Restrições antes de produção

**Não dar como prontas estas atividades:** adaptador real do SSO, aplicação e revogação no FortiGate, retirada segura das rotas antigas do wp-admin, unificação completa das ACLs legadas com AD, armazenamento protegido para arquivos restritos, homologação de carga/concor­rência/recuperação do chat e testes reais com banco.

**Critério de aceite:** nenhuma operação ou conteúdo interno indevidamente visível a usuário não autenticado ou sem permissão, inclusive acesso por URLs diretas e APIs; SSO, AD e grupos validados; cron comprovadamente executado; rollback testado; e todos os módulos existentes sem regressão.
