# Verificacao de Layout - Etapa 1

Referencias usadas:
- `clinica/!Suporte/layout_2/doctor_dashboard_summary/code.html`
- `clinica/!Suporte/layout_2/patient_medical_records/code.html`
- `clinica/!Suporte/layout_2/configurações_do_aplicativo/code.html`

## Dashboard (`/dashboard`)
- Aderente: estrutura mobile com status row + header + cards de resumo + card de receita + lista de atendimentos.
- Aderente: tipografia e icones Material (Material Icons Round + Symbols Outlined).
- Aderente: barra inferior com 5 itens no mesmo arranjo do modelo.
- Parcial: imagem/avatar e textos de exemplo do layout foram convertidos para dados reais da API.

## Paciente (`/paciente`)
- Aderente: cabecalho com acao esquerda/direita, card principal de paciente, abas horizontais, timeline de consultas e FAB.
- Aderente: navegacao inferior flutuante no padrao mobile.
- Aderente: card/timeline com hierarquia visual equivalente (dot, trilha, bloco de conteudo).
- Parcial: seções clinicas detalhadas do mock (PA/Temp/Prescricao fixa) foram substituidas por conteudo de prontuario da API.

## Configuracoes (`/configuracoes`)
- Aderente: header sticky, card de perfil com selo, grupos de itens com chevron, bloco de logout e rodape de versao.
- Aderente: barra inferior no padrao do mock.
- Aderente: paleta `primary` e atmosfera visual baseadas no modelo.
- Parcial: itens ainda sem navegacao funcional (atualmente placeholders visuais).

## Validacao tecnica da etapa
- Build Angular: OK (`npm.cmd run build`).
- Observacao: front legado continua intacto, novo front roda em paralelo em `clinica/frontend-angular`.
