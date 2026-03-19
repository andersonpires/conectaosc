# Verificacao de Layout - Etapa 6

Escopo da etapa:
- Corte controlado de entrada do app (`/clinica`) para front Angular, com fallback legado.
- Ajustes de roteamento Apache para assets Angular e deep-links SPA.
- Sem mudanca estrutural de componentes de layout das telas.

Referencias de layout mantidas:
- `clinica/!Suporte/layout_2/doctor_dashboard_summary/code.html`
- `clinica/!Suporte/layout_2/daily_appointment_schedule/code.html`
- `clinica/!Suporte/layout_2/patient_directory_list/code.html`
- `clinica/!Suporte/layout_2/patient_medical_records/code.html`
- `clinica/!Suporte/layout_2/prescription_and_certificate_issuer/code.html`
- `clinica/!Suporte/layout_2/formulário_de_anamnese_clínica/code.html`
- `clinica/!Suporte/layout_2/configurações_do_aplicativo/code.html`

## Alteracoes tecnicas desta etapa
- `clinica/index.php`
  - Continua validando sessao e perfil profissional.
  - Passa a servir Angular por padrao quando o build existe:
    - `frontend-angular/dist/frontend-angular/browser/index.html`
  - Fallback para front legado via query string:
    - `/clinica/?legacy=1`

- `clinica/.htaccess`
  - Mapeia assets do Angular em `/clinica/frontend-angular/*` para a pasta de build.
  - Suporta deep-link SPA sob `/clinica/frontend-angular/...`.
  - Mantem API (`/clinica/api`) fora das regras SPA.
  - Mantem deep-links principais (`/dashboard`, `/agenda`, `/pacientes`, `/paciente/:id`, `/prontuario`, `/anamnese`, `/configuracoes`) redirecionando para `index.php`.

## Verificacao visual
- Nao houve alteracao de markup/scss das paginas nesta etapa.
- Checagem de nao-regressao textual/estrutural confirmou elementos-chave de layout nas telas principais.
- Conclusao: aderencia aos modelos `layout_2` permanece mantida.

## Verificacao tecnica
- Build Angular: OK (`npm.cmd run build`).
- Sintaxe PHP (`clinica/index.php`): OK (`php -l`).
- Observacao de ambiente: aviso de extensao `imagick` no PHP local (nao bloqueante para esta mudanca).
