# SMTT Intranet v2.0.35.10 — Grupos do Chat gerenciados pelo CPD

Base: v2.0.35.9 (sem reconstruir módulos antigos).

## Controle de grupos
- Nova criação de grupos somente em Central Administrativa > Chat SMTT, com autorização validada no servidor.
- Botão de criar grupo removido da interface do Chat SMTT. A API pública também rejeita tentativas de criar grupos (HTTP 403), independentemente de botões.
- Grupos manuais criados pelo CPD: nome, membros por login WordPress, inclusão/remoção de participantes, renomeação e arquivamento/reativação.
- Grupos AD: escolher grupo de segurança do catálogo sincronizado e fornecer um nome; o acesso efetivo é conferido por SID de grupo da identidade AD validada no servidor; não há inclusão manual nesse tipo.
- O grupo AD passa a aparecer automaticamente para o funcionário com conta WordPress provisionada, quando ele abre o chat ou consulta as conversas na Home, **após autenticação SSO funcional**.
- Se o funcionário deixar o grupo AD ou o SSO não puder ser validado, a sessão não pode acessar o canal; o registro local de associação é retirado no próximo acesso.
- Arquivamento é reversível e preserva mensagens.
- Acesso a histórico de grupo restringido às mensagens posteriores à entrada do participante, evitando disponibilizar conversas antigas para pessoas transferidas ao setor.
- Operações de administração de grupos registradas na auditoria corporativa.

## Dependências para produção
- SSO deve autenticar usuários e provisionar/vincular contas no WordPress.
- Conexão LDAPS e catálogo de grupos AD sincronizados.
- Grupo CPD e grupo administrativo exclusivo definidos.
- Comportamento de grupos aninhados depende da confirmação de associação recebida do AD.

## Limitações
- Lista de participantes de grupos AD só refletirá contas WordPress que já tenham aberto o Chat após autenticação; não provisiona todas as contas do domínio.
- Gestão dos integrantes dos grupos de segurança é feita no Windows Server, nunca pela Intranet.
- Cadastro manual aceita logins WordPress separados por vírgula ou espaço; usuários ainda não provisionados não podem ser incluídos até possuírem conta.
- Requer testes reais com duas contas e, posteriormente, com SSO/AD.
