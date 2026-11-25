# Alterações V68 — Access Gate

## Objetivo

Adicionar ao plugin SMTT Intranet uma camada desacoplada de controle de passagem pela Home, preparada para futura integração com Active Directory/SSO, GPO e firewall/proxy/NAC.

## Entregue nesta versão

- Novo módulo `Access Gate` em `includes/modules/access-gate/`.
- Tabela própria `wp_smtt_intranet_access_gate` (prefixo respeita a instalação WordPress).
- Criação/upgrade automático da tabela mesmo quando o plugin já está ativo.
- Registro transparente da passagem pela Home, sem botão ou confirmação do usuário.
- A passagem só é registrada após a aba permanecer visível pelo tempo configurado (padrão: 2 segundos).
- Uma autorização por usuário/dia, válida até `23:59:59` no fuso do WordPress.
- Reaberturas no mesmo dia atualizam `last_seen_at`, sem duplicar a autorização diária.
- REST API autenticada por sessão/nonce para `gate/pass` e `gate/status`.
- REST API servidor-a-servidor para futura integração de rede: `gate/network/status?identity=...`.
- Chave dedicada `X-SMTT-Gate-Key`, gerada automaticamente e rotacionável pelo painel.
- Campos administrativos de identidade corporativa no perfil do usuário: usuário Windows e UPN.
- Campos de identidade corporativa editáveis somente por administradores do Access Gate.
- Filtro de integração `smtt_intranet_access_gate_identity` para futura camada SSO.
- Filtros para IP/hostname fornecidos por infraestrutura confiável.
- Dashboard do Access Gate com métricas, histórico, busca, configurações e retenção.
- Limpeza automática diária do histórico antigo (90 dias por padrão).
- Novo capability `smtt_manage_access_gate` para administradores.
- Card do Access Gate no Dashboard da Intranet.
- Versão do plugin atualizada para `2.0.28`.

## Comportamento atual

O módulo está em modo de auditoria/integração. Ele registra e informa quem está autorizado, mas não bloqueia Internet. O bloqueio será feito posteriormente pela infraestrutura de rede, consumindo o estado do Access Gate.

## Próxima etapa de infraestrutura

1. Definir SSO (AD/Kerberos, Entra/OIDC/SAML ou solução equivalente).
2. Mapear a identidade Windows para o usuário WordPress.
3. Criar GPO de abertura da Intranet no logon/desbloqueio quando necessário.
4. Integrar firewall/proxy/NAC ao endpoint servidor-a-servidor do Access Gate.
5. Validar em modo auditoria antes de aplicar bloqueio de Internet.
