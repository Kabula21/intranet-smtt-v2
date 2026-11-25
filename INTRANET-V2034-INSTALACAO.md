# SMTT Intranet v2.0.34 — Fundação AD e diagnóstico

## Estado desta entrega

* Base: v2.0.33 fornecida pelo usuário. Foram mantidos os arquivos de Notícias, Ramais, Home e demais módulos (exceto o JavaScript/API do Access Gate necessário à melhoria de segurança).
* A Central Administrativa inicial está disponível em `/administracao/`, **somente após** o bootstrap de dois grupos AD e a identificação SSO confiável. Ela contém diagnóstico, grupos e OUs sincronizados, escolha dos dois grupos e botão Sincronizar.
* O novo mecanismo de **consulta LDAPS somente leitura** detecta grupos e OUs automaticamente, preservando objetos ausentes como inativos no catálogo local. Novos usuários são identificados por SSO + consulta em tempo real ao AD, sem importação maciça de contas.
* A sincronização automática é agendada para 15 minutos. Em produção, recomenda-se cron do Ubuntu disparando `wp cron event run --due-now` a cada minuto; WP-Cron depende de tráfego.
* Access Gate: `/gate/start` cria um ticket temporário vinculado à sessão; `/gate/pass` exige o ticket e o intervalo mínimo no servidor. A condição da aba visível continua dependente do navegador e **não comprova leitura**.
* **A integração FortiGate real NÃO foi ativada nem implementada**. A API `/gate/network/status` agora responde `authorized=false` e `enforcement_ready=false` por padrão, independentemente da passagem do WordPress. O estado de passagem auditável permanece acessível no `/gate/status`.
* Os gerenciadores antigos no `wp-admin` **permanecem ativos**, até serem migrados e validados; a área `/administracao/` ainda não substitui esses formulários.

## Pré-requisitos para ativar AD

1. WordPress e plugin em PHP com extensão `ldap` habilitada.
2. Comunicação LDAPS de Ubuntu para o controlador de domínio e cadeia de certificados do AD confiável no Ubuntu/OpenLDAP. Não desativar verificação TLS.
3. Conta técnica no AD com acesso **somente leitura**. Não inserir sua senha no painel ou nos metadados do WordPress.
4. SSO do WordPress configurado para identificar de forma autenticada o usuário Windows. Esta entrega **não instala/implementa o protocolo Kerberos ou OIDC**.

Defina no `wp-config.php` (fora do ZIP e dos repositórios):

```php
define('SMTT_AD_LDAPS_URL', 'ldaps://controlador.exemplo.interno:636');
define('SMTT_AD_BASE_DN', 'DC=exemplo,DC=interno');
define('SMTT_AD_BIND_DN', 'CN=conta-de-leitura,OU=Servicos,DC=exemplo,DC=interno');
define('SMTT_AD_BIND_PASSWORD', getenv('SMTT_AD_BIND_PASSWORD'));
```

Estes são apenas **exemplos**, não os dados reais da SMTT. O host, base DN e credencial precisam ser obtidos da infraestrutura.

## Adaptador para o SSO confiável

A integração SSO instalada no WordPress deverá registrar um filtro **executado no servidor** que devolva dados validados, nunca cabeçalhos/client-side não autenticados:

```php
add_filter('smtt_intranet_verified_ad_identity', function ($identity, $wp_user_id) {
    // Exemplo de contrato. Substituir por integração REAL de SSO;
    // NÃO usar diretamente o nome informado pelo navegador.
    // return array('verified' => true, 'upn' => 'usuario@exemplo.interno');
    return null;
}, 10, 2);
```

A ausência de um adaptador válido bloqueia o acesso ao novo painel por segurança. No momento a identidade AD não é automaticamente deduzida do `user_login` nem dos campos manuais do usuário WordPress. O plugin resolve a conta no AD, consulta grupos aninhados e exige associação simultânea a dois grupos de segurança.

## Primeira configuração: via terminal Ubuntu com WP-CLI

Execute no diretório raiz da instalação WordPress e com o usuário técnico autorizado:

```bash
wp smtt-intranet ad-sync
wp smtt-intranet ad-status
wp smtt-intranet ad-bootstrap --cpd='S-1-5-21-111111-222222-333333-4444' --admin='S-1-5-21-111111-222222-333333-5555'
```

Os SIDs devem ser copiados dos grupos **reais e distintos** obtidos pela sincronização. O comando de bootstrap só aceita SIDs ativos registrados no catálogo. Depois, o integrante validado dos dois grupos acessa `https://SEU-ENDERECO/administracao/` para diagnóstico e alterações autorizadas.

**Restrinja rigorosamente o acesso ao servidor e ao WP-CLI**; qualquer administrador de infraestrutura com privilégios de alterar código, banco ou `wp-config.php` pode modificar a instalação e não está tecnicamente sujeito às permissões de uma aplicação web.

## Segurança do Access Gate

- A API de rede só deverá ser utilizada depois de projeto e testes da integração FortiGate. Ela exige HTTPS; em instalações atrás de proxy reverso, configure o WordPress para reconhecer corretamente HTTPS. Nesta versão o endpoint está permanentemente em modo de auditoria (`enforcement_ready=false`); o adaptador de imposição deverá ser desenvolvido e testado numa etapa futura.
- A mudança na API de passagem requer a atualização conjunta do JS e do PHP, incluída no ZIP. Limpe caches de página, CDN e scripts ao atualizar.
- Valide horário/fuso do WordPress. A validade diária continua no fuso WordPress e expira às 23:59:59.
- O tempo de exibição é um sinal de apresentação, não uma garantia de leitura. Um usuário com controle do navegador pode falsificar eventos; só o FortiGate poderá impor o acesso externo em pontos da rede gerenciada.
- Não crie políticas FortiGate de bloqueio antes de validar identidade por dispositivo/sessão, whitelist de serviços essenciais, revogação, IPv6 e exceções.

## Testes sugeridos em homologação

1. Com AD não configurado: portal retorna HTTP 403, cron não tenta conexão, Home funciona.
2. Com AD configurado: sincronização mostra grupos e OUs, sem expor conta e senha; renomear uma OU atualiza o catálogo sem apagar vínculos.
3. SSO não validado: mesmo administrador WordPress não consegue acesso ao novo painel.
4. SSO validado + dois grupos ativos: acesso permitido; apenas CPD, negado; apenas grupo admin, negado.
5. `/gate/pass` sem ticket, ticket inválido ou antes do tempo: negado. Home com aba visível: passagem registrada.
6. `/gate/network/status`: `authorized=false`, `enforcement_ready=false` por padrão; nenhum acesso externo liberado.
7. Regressão: notícias (imagens 1/2 e subtítulos), ramais (pré-carregamento/cache), Access Gate, módulos existentes.

## Próximas etapas (ainda não implementadas)

- Implementar SSO na configuração Ubuntu/WordPress real; gerenciar as ligações entre OUs, setores e permissões delegadas.
- Concluir interfaces administrativas para cada módulo e substituir telas antigas do `wp-admin`.
- Criar biblioteca de ícones e logomarcas próprias; criar Minha Gestão.
- Desenvolver adaptador FortiGate compatível com FortiOS e autenticação de rede, incluindo reconciliação, revogação e piloto.
