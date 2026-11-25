# SMTT Intranet v2.0.35.8 — Chat SMTT nativo (homologação)

## Escopo
- Mensagens privadas e grupos entre usuários WordPress autenticados, com identidade AD futura via SSO do WordPress.
- Sem envio de fotos, vídeos, áudios, anexos ou documentos. Mensagens aceitam somente texto, emojis e links HTTP(S) clicáveis.
- Home: ícone do Chat abre um submenu com cinco conversas recentes, contador de não lidas e opção de abrir a aplicação em outra aba.
- Chat completo em `/chat-smtt/`, em layout próprio, com pesquisa de usuários, composição de grupos, histórico paginado e atualização periódica.
- Central Administrativa do CPD: aba Chat SMTT com configuração de disponibilidade, grupos e limite de caracteres, além de métricas agregadas. Administração não concede leitura de mensagens privadas.
- Tabelas SQL próprias (`smtt_chat_conversations`, `smtt_chat_participants`, `smtt_chat_messages`, com prefixo WP da instalação). Criação automática e versionada com dbDelta.
- REST autenticado com nonce WordPress, verificação de participante, limitação simples de frequência, sanitização das mensagens e respostas sem cache.

## Observações e limites
- O conteúdo do histórico do Better Messages NÃO é migrado. Instalar esta versão não apaga dados do Better Messages caso ele esteja instalado separadamente.
- Só usuários existentes/provisionados no WordPress aparecem na busca. O SSO/AD deve provisionar ou vincular essas contas no ambiente corporativo.
- Grupos criados têm participantes definidos no momento da criação; edição de integrantes, expulsão, retenção automática e exportação não foram implementadas nesta entrega.
- O chat usa consultas periódicas (10 s na janela aberta, 30 s na Home quando visível); não utiliza WebSockets.
- A versão é destinada a testes locais/homologação. Não houve teste com WordPress e banco de dados reais neste ambiente de geração.
- Políticas de privacidade, retenção institucional, backup e carga devem ser aprovadas antes do uso definitivo.

## Não alterados funcionalmente
Notícias, Ramais, Access Gate, Active Directory, integrações FortiGate e biblioteca de ícones.
