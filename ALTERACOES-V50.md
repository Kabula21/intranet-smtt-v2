# SMTT Intranet — Alterações v50

Versão 2.0.10.

## Notícias
- Removida a dependência de `wp_http_validate_url()` para validar o portal externo.
- Nova validação local de URL: apenas HTTP/HTTPS, sem credenciais, somente portas 80/443 e bloqueio de localhost/IPs privados/reservados.
- A validação não depende de DNS nem de filtros globais do WordPress.
- A requisição continua usando a HTTP API normal do WordPress.
- Nova chave de cache v50 para descartar imediatamente o erro antigo “URL externa inválida”.
