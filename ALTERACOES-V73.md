# SMTT Intranet 2.0.33 — Ramais mais rápidos no frontend

Alterações restritas ao módulo de Ramais:

- Pré-carregamento silencioso do diretório após a Home terminar de carregar, usando `requestIdleCallback` quando disponível.
- Ao clicar no botão de Ramais, a mesma requisição em andamento é reaproveitada; não há requisições duplicadas.
- Cache do HTML final dos ramais por 1 hora no servidor, invalidado automaticamente ao cadastrar, editar, excluir ou importar ramais.
- Consulta pública otimizada para buscar somente `setor`, `cargo` e `ramal`, em vez de `SELECT *`.
- Mantida a busca instantânea no navegador após o carregamento.
- Nenhuma alteração funcional em Notícias, Access Gate ou demais módulos.
