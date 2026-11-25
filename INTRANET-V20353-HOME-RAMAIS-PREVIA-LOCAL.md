# SMTT Intranet v2.0.35.3 — Ramais e prévia administrativa local

Base: v2.0.35.2, sem arquivos antigos reintroduzidos.

- Ramais: correção de exibição do botão e drawer nas páginas que usam `[smtt_intranet_home]`, inclusive `/intranet/`. Mantidos prefetch, AJAX e cache existentes.
- Administração: link visível **na barra superior da Home** para administradores autorizados, sem cobrir o botão flutuante dos Ramais. Funcionários delegados veem Minha Gestão.
- Prévia local: administradores WordPress (`manage_options`) podem visualizar a Central somente quando o **endereço configurado da instalação** termina em `.local`, `.localhost`, `.test`, ou é `localhost`, `127.0.0.1` ou `::1`. Não usa `HTTP_HOST` recebido do navegador. Ambientes declarados `WP_ENVIRONMENT_TYPE=production` ou `staging` não têm exceção. `SMTT_INTRANET_DISABLE_LOCAL_PREVIEW=true` permite desativá-la explicitamente.
- Em produção, permanece obrigatória a dupla participação nos grupos reais do AD via SSO. A prévia não cria identidade AD, não concede passagem via Access Gate nem libera o FortiGate.
- O painel `/wp-admin/` continua só com o botão de abrir a Intranet.
- Notícias, lógica dos Ramais, Access Gate, sincronização de AD e outros módulos não foram alterados nesta entrega.

Atenção: homologação real no WordPress local ainda é necessária. Se o endereço de teste for um domínio que não termina em sufixo local, configurar o ambiente local adequadamente (não liberar preview em domínio de produção).
