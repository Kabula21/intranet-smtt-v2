# SMTT Intranet v2.0.35.2 — Correção do botão "Abrir Intranet"

## Problema observado

- O atalho no wp-admin abria `home_url('/')`, que em instalações recém-criadas exibia o Blog padrão do WordPress.
- A lista de plugins oferecia o link "Visitar o site do plugin" para o portal institucional SMTT, que não corresponde à Home da Intranet.

## Correções pontuais

- A Home da Intranet passa a ser localizada pelo shortcode `[smtt_intranet_home]`, inclusive páginas existentes feitas no Elementor.
- Se a página não existir, o plugin cria automaticamente uma página publicada `Intranet SMTT` com o shortcode; não cria uma cópia de Sistemas, Ramais ou Notícias.
- Se a instalação ainda estiver configurada para exibir posts na página inicial (Blog), a Home da Intranet passa a ser a página inicial do WordPress. Posts existentes não são apagados.
- Se já houver uma página inicial estática diferente, ela permanece inalterada; o atalho abre somente a página da Intranet identificada/criada.
- A página criada pelo plugin usa um template frontend próprio, com `wp_head`, `wp_body_open` e `wp_footer`, sem os elementos visuais do tema WordPress (como a página Blog).
- O botão "Abrir Intranet" utiliza o permalink real da página de Intranet. Se a criação falhar, mostra erro, em vez de abrir o Blog por engano.
- Removidos os campos `Plugin URI` e `Author URI` do cabeçalho, eliminando o link de visita ao site institucional na listagem do plugin.

## Escopo de preservação

- Base utilizada: SMTT Intranet v2.0.35.1 entregue pelo usuário neste projeto.
- Alterados somente `smtt-intranet.php` e `includes/admin/class-smtt-intranet-admin-menu.php`.
- Adicionados: `includes/public/class-smtt-intranet-home-routing.php`, `includes/public/intranet-home-template.php` e este documento.
- Notícias, Ramais, Access Gate, sincronização AD e telas administrativas permanecem com código inalterado.
- A Central Administrativa `/administracao/` continua exigindo autenticação/grupos AD; o novo botão leva à Home, não burla essas exigências.

## Testes

- PHP: `php -l` para todos os arquivos PHP do plugin.
- JavaScript: `node --check` para todos os arquivos JavaScript.
- Simulação: site com blog padrão, site com Home estática de terceiro e site com Home da Intranet existente; sem duplicar páginas na atualização.
- ZIP: checagem CRC de todas as entradas e caminhos seguros.
- Validação em WordPress real ainda pendente (homologação do ambiente de destino).
