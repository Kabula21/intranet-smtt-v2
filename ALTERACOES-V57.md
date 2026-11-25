# SMTT Intranet — Alterações v57

Versão 2.0.17.

## Importação de aniversariantes
- Aceita diretamente CSV simplificado com `Nome,Data de Nascimento`.
- Mantém compatibilidade com `nome,setor,cargo,data_nascimento,status`.
- Aceita datas `dd/mm/aaaa`, `dd/mm/aa` e `aaaa-mm-dd`.
- Converte automaticamente anos de dois dígitos para o século adequado.
- Detecta separador vírgula, ponto e vírgula ou TAB.
- Reconhece cabeçalhos com espaços, acentos e variações comuns.
- Novos registros recebem status `ativo` quando o CSV não trouxer status.
- Em registros existentes, CSV simplificado atualiza nome/data/status sem apagar setor ou cargo já cadastrados.
- Evita duplicação pelo nome normalizado e detecta nomes repetidos no mesmo arquivo.
