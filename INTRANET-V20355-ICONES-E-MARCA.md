# SMTT Intranet v2.0.35.5 — Biblioteca e identidade visual

Base utilizada: plugin v2.0.35.4 enviado anteriormente. Atualização incremental, sem recriar módulos.

## Biblioteca de ícones

- Mantidos os 2.060 ícones Font Awesome Free e os códigos `fa:solid:...`, `fa:regular:...` e `fa:brands:...` existentes.
- Incluídos 228 SVGs de uso interno, sob códigos `smtt:...`, com nomes em português.
- Total: 2.288 entradas locais, sem dependência de CDN ou conexão externa.
- Busca global sem a limitação antiga de 90 resultados; navegação progressiva de 72 resultados por etapa, por rolagem ou botão "Mostrar mais".
- Filtros por categorias: Pastas, Sistemas e TI, Documentos, Pessoas e setores, Redes, Segurança, Trânsito e transporte, Comunicação, Marcas e Outros.
- Filtros e pesquisa incluídos tanto na página da Biblioteca quanto na janela Escolher ícone dos cadastros.
- Mantido o upload de logomarcas PNG, WEBP e JPEG. SVG externo não é aceito no upload.

## Marca institucional

- Aplicada, na barra lateral da Central Administrativa, a imagem já presente no plugin: `assets/public/img/logo-smtt.png`.
- Substituído o monograma genérico por marca vetorial própria da Intranet, inspirada nas cores da SMTT, para favicon e menu do plugin no WordPress.
- Favicon da Intranet incluído nas rotas da Home, da Central Administrativa e da Minha Gestão, sem alterar a configuração global de ícone do WordPress.

## Sem alterações

- Códigos de Notícias, Ramais, Access Gate e seus serviços atuais.
- Conteúdos cadastrados e IDs dos ícones anteriores.
- Política de acesso AD e modo de simulação FortiGate.

## Implantação

Recomendado atualizar em ambiente de homologação, limpar cache de navegador / plugin e validar seleção de ícones em Sistemas, Pastas e Links. Ativação real do AD/FortiGate continua dependente da infraestrutura.
