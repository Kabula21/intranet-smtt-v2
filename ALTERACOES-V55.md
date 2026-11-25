# SMTT Intranet — Alterações v55

Versão 2.0.15.

## Ramais
- Importação direta do Excel clássico `.xls` (BIFF8), sem necessidade de converter para CSV.
- Suporte nativo ao modelo recebido pela SMTT: `SETOR | RAMAL`, mesmo com coluna de sequência antes do cabeçalho.
- Cabeçalho localizado automaticamente nas primeiras linhas da planilha.
- Conversão automática de descrições como `CPD - SUPORTE` em Setor `CPD` e função `SUPORTE`.
- Suporte a separador `/`, por exemplo `DPS / SECRETÁRIA`.
- Continuidade do suporte a CSV.
- Ramal existente é atualizado pelo número; ramal novo é inserido.
- Cache público dos Ramais é limpo imediatamente após importação, cadastro manual, edição ou exclusão.
- Tela administrativa atualizada para aceitar `.xls,.csv` e explicar o formato esperado.
