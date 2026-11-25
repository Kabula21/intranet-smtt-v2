# SMTT Intranet — Access Gate

## O que esta versão faz

O Access Gate registra, de forma transparente, que um usuário autenticado passou pela Home da Intranet. Não há botão de confirmação.

A autorização diária é criada quando:

1. o usuário está autenticado no WordPress;
2. a Home da Intranet foi carregada;
3. a aba permanece visível pelo tempo configurado (2 segundos por padrão).

A autorização é válida até 23:59:59 do mesmo dia, conforme o fuso horário configurado no WordPress.

## Teste funcional antes da GPO/firewall

1. Atualize/instale o plugin `SMTT Intranet v.2.0` versão 2.0.28.
2. Acesse **SMTT Intranet > Access Gate** pelo card do Dashboard.
3. Confirme que o status está **ATIVO — Modo auditoria/integração**.
4. Em **Usuários**, edite um usuário de teste e informe, se desejado, `DOMINIO\\usuario` no campo **Usuário Windows**.
5. Entre na Intranet com esse usuário.
6. Deixe a Home visível por pelo menos 2 segundos.
7. Volte ao painel **Access Gate** e confirme o registro no histórico.
8. Reabra a Home no mesmo dia: não será criada uma segunda autorização diária; `last_seen_at` é atualizado internamente.

## REST API do navegador

### Registrar passagem

`POST /wp-json/smtt-intranet/v1/gate/pass`

Requer sessão WordPress autenticada e nonce REST (`X-WP-Nonce`). O JavaScript da Home já executa esta operação automaticamente.

### Consultar o próprio estado

`GET /wp-json/smtt-intranet/v1/gate/status`

Também exige sessão WordPress autenticada.

## REST API para infraestrutura

### Consultar uma identidade

`GET /wp-json/smtt-intranet/v1/gate/network/status?identity=DOMINIO%5Cusuario`

Cabeçalho obrigatório:

`X-SMTT-Gate-Key: <chave exibida no painel Access Gate>`

Exemplo de resposta:

```json
{
  "enabled": true,
  "identity": "DOMINIO\\usuario",
  "authorized": true,
  "authorized_at": "2026-09-15T08:01:13-03:00",
  "valid_until": "2026-09-15T23:59:59-03:00",
  "status": "authorized"
}
```

A integração de rede deve decidir o comportamento quando `enabled=false` ou quando a API estiver indisponível. Essa política deve ser definida antes de ativar bloqueio em produção.

## Identidade Windows / SSO

Nesta etapa o Access Gate usa, nesta ordem:

1. **Usuário Windows** cadastrado administrativamente no perfil;
2. **UPN** cadastrado no perfil;
3. login WordPress como fallback.

O filtro `smtt_intranet_access_gate_identity` permite que uma futura camada de SSO injete a identidade já autenticada via AD/Kerberos, Entra/OIDC, SAML ou solução equivalente.

O navegador não expõe de forma confiável o hostname do Windows. O campo `computer_name` fica preparado para ser preenchido por infraestrutura confiável através do filtro `smtt_intranet_access_gate_computer_name`.

## Segurança

- O endpoint de passagem ignora qualquer nome de usuário enviado pelo navegador: usa sempre o usuário da sessão autenticada.
- A identidade Windows/UPN só pode ser alterada por administradores com `smtt_manage_access_gate`.
- A API de rede usa chave própria e não reutiliza senha de usuário/WordPress.
- `REMOTE_ADDR` é a fonte padrão de IP; cabeçalhos de proxy não são confiados automaticamente.
- A chave da API pode ser rotacionada no painel.

## Próximas etapas

1. Implementar SSO/AD.
2. Criar GPO para abrir a Home no logon e no desbloqueio quando a autorização estiver vencida.
3. Escolher a integração do firewall/proxy/NAC.
4. Executar piloto em modo auditoria.
5. Só então ativar a política de bloqueio externo.
