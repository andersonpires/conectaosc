# Verificacao de Layout - Etapa 11

Escopo da etapa:
- Fortalecimento da validacao funcional com suporte explicito a cenario autenticado.
- Revalidacao de aderencia visual aos modelos `layout_2`.
- Sem alteracao de layout (HTML/SCSS) das telas.

## Entregas
1. Script funcional atualizado:
- `clinica/frontend-angular/scripts/functional_flow_check.ps1`
- Novidades:
  - parametro `-RequireAuth`
  - validacoes autenticadas de front protegido (`/clinica/`, `/dashboard`, `/pacientes`, `/anamnese`)
  - validacoes autenticadas de API (`/api`, `/api/pacientes`, `/api/agenda`, `/api/prontuarios`, `/api/anamnese`)

2. Comando npm autenticado:
- `verify:flow:auth` em `clinica/frontend-angular/package.json`
- Usa `CLINICA_PHPSESSID` para executar checagem autenticada.

3. Guia QA atualizado:
- `clinica/frontend-angular/QA_FUNCIONAL_ETAPA_10.md`
- Inclui execucao por variavel de ambiente e modo direto com `-RequireAuth`.

## Resultado dos checks da etapa
- `npm run verify:flow`: OK
- `npm run verify:layout`: OK (todas as telas)
- `npm run build`: OK

## Verificacao de layout/design
- Nao houve alteracao de componentes visuais nesta etapa.
- Aderencia aos modelos em `clinica/!Suporte/layout_2` permaneceu preservada e foi revalidada automaticamente.
