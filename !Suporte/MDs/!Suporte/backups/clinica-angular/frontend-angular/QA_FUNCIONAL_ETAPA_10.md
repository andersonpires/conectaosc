# QA Funcional Pos-Corte (Etapa 10)

## Script de fluxo funcional
Arquivo:
- `scripts/functional_flow_check.ps1`

### Execucao basica (sem sessao)
```powershell
npm run verify:flow
```

### Execucao autenticada (via variavel de ambiente)
1. Obtenha seu `PHPSESSID` no navegador apos login no ConectaOSC.
2. Defina a variavel e execute:
```powershell
$env:CLINICA_PHPSESSID="SEU_PHPSESSID"
npm run verify:flow:auth
```

### Execucao autenticada (direta)
```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\functional_flow_check.ps1 -PhpSessId "SEU_PHPSESSID" -RequireAuth
```

## O que o script valida
- Rotas Angular principais em `/clinica/frontend-angular/*` retornando 200.
- API sem sessao retornando bloqueio (401/404) para endpoint protegido.
- Com sessao (`PHPSESSID`) valida tambem:
  - Front protegido em `/clinica/` (modo angular e legacy).
  - Deep-links protegidos (`/dashboard`, `/pacientes`, `/anamnese`).
  - Endpoints de API chave (`/api`, `/api/pacientes`, `/api/agenda`, `/api/prontuarios`, `/api/anamnese`).

## Observacao
- Este script valida disponibilidade de fluxo HTTP/roteamento.
- Validacao de interacao de UI (clicar, preencher, salvar) ainda deve ser feita manualmente ou com automacao E2E dedicada.
