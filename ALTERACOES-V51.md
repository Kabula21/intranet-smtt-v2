# SMTT Intranet — Alterações v51

Versão 2.0.11.

## Mural Interno / Avisos
- Consulta do front não depende mais da existência de `_smtt_sort_order` ou de outras metas para encontrar o post publicado.
- Registros legados sem `_smtt_status` são tratados como ativos.
- Corrigida a compatibilidade do antigo checkbox “Visível no front”: registros antigos sem marcador explícito passam a ser visíveis por padrão.
- Novos avisos já abrem com “Exibir para os colaboradores na intranet” marcado.
- A partir da v51, a escolha explícita de ocultar/mostrar é gravada separadamente e respeitada pelo front.
- Datas vazias ou inválidas não escondem o aviso; datas válidas continuam controlando a vigência.
- Cache do Mural é invalidado novamente após todas as metas e taxonomias serem efetivamente salvas.
- Nova chave de cache `mural_home_v51_*` evita reaproveitar um resultado vazio da versão anterior.
