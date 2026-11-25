# SMTT Intranet v2.0.23 — correções finais de Tour e Notícias

- O botão **Rever Tour** passa a ser renderizado também pelo PHP no rodapé da Intranet, evitando depender somente da criação dinâmica por JavaScript.
- A tela de boas-vindas já registra a rodada como vista; atualizar a página no meio do Tour não reinicia a apresentação.
- Persistência reforçada com perfil do usuário no WordPress, `localStorage` e cookie de contingência.
- O modo de pré-visualização remove o parâmetro da URL após abrir, evitando que um simples F5 mantenha a página presa em preview.
- Removido o proxy binário de imagens via `admin-ajax.php`, responsável pelos erros 502.
- Fotos das Notícias agora usam cache estático em `wp-content/uploads/smtt-intranet/news-static`, com download em streaming para não sobrecarregar PHP-FPM.
- Se o portal estiver temporariamente indisponível, a foto tenta carregar diretamente sem `Referer`; se ainda falhar, o card mantém o layout limpo sem ícone de imagem quebrada.
