# Verificacao de Layout - Etapa 3

Referencias desta etapa:
- `clinica/!Suporte/layout_2/daily_appointment_schedule/code.html`
- `clinica/!Suporte/layout_2/prescription_and_certificate_issuer/code.html`

## Agenda (`/agenda`)
- Aderente: cabecalho com mes + acao de filtro, status row superior e strip semanal com dia ativo.
- Aderente: blocos de horario com cards de evento, marcador de horario atual e FAB.
- Aderente: barra inferior com 5 itens no mesmo padrao visual do modelo.
- Parcial: o modelo possui slots estaticos por hora; no Angular os cards foram ligados aos eventos reais da API (`/agenda?view=day`).

## Prontuario / Emissao (`/prontuario`)
- Aderente: header "Emitir Documento", tabs Receita/Atestado, busca de paciente e card de paciente selecionado.
- Aderente: bloco de detalhes, assinatura e CTA fixo "Emitir e Imprimir".
- Aderente: barra inferior no mesmo arranjo do modelo.
- Parcial: campos de medicamento/atestado ainda estao em versao simplificada, priorizando integracao com prontuarios reais da API.

## Consistencia de navegacao
- Dashboard, Agenda, Paciente, Prontuario e Configuracoes agora compartilham navegação por rotas reais:
  - `/dashboard`
  - `/agenda`
  - `/paciente`
  - `/prontuario`
  - `/configuracoes`

## Verificacao tecnica da etapa
- Build Angular: OK (`npm.cmd run build`).
- API backend preservada: chamadas continuam em `/clinica/api`.
- Front legado permanece intacto em paralelo.
