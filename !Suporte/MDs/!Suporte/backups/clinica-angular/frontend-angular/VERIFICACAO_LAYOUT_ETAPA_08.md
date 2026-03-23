# Verificacao de Layout - Etapa 8

Escopo da etapa:
- Robustez operacional pos-corte: controle de modo do front + smoke test automatizado.
- Sem alteracoes de componentes visuais.

Referencias de layout preservadas:
- `clinica/!Suporte/layout_2/doctor_dashboard_summary/code.html`
- `clinica/!Suporte/layout_2/daily_appointment_schedule/code.html`
- `clinica/!Suporte/layout_2/patient_directory_list/code.html`
- `clinica/!Suporte/layout_2/patient_medical_records/code.html`
- `clinica/!Suporte/layout_2/prescription_and_certificate_issuer/code.html`
- `clinica/!Suporte/layout_2/formulário_de_anamnese_clínica/code.html`
- `clinica/!Suporte/layout_2/configurações_do_aplicativo/code.html`

## Entregas tecnicas da etapa
1. Controle de modo do front em `clinica/index.php`:
- `CLINICA_FRONT_MODE=auto` (padrao)
- `CLINICA_FRONT_MODE=angular`
- `CLINICA_FRONT_MODE=legacy`
- Fallback manual por URL continua: `?legacy=1`

2. Script de smoke test pos-corte:
- `clinica/frontend-angular/scripts/post_cut_smoke.ps1`
- Cobre: root, fallback legado, rotas Angular principais, assets (`main-*.js`/`styles-*.css`) e API.

3. Guia operacional:
- `clinica/frontend-angular/OPERACAO_POS_CORTE.md`

## Verificacao tecnica
- Build Angular: OK (`npm.cmd run build`).
- Sintaxe PHP: OK (`php -l clinica/index.php`).
- Smoke test: OK (`powershell -ExecutionPolicy Bypass -File .\scripts\post_cut_smoke.ps1`).

## Verificacao visual
- Nesta etapa nao houve edicao de HTML/CSS das telas.
- Conclusao: aderencia de layout/design aos modelos `layout_2` permanece preservada.
