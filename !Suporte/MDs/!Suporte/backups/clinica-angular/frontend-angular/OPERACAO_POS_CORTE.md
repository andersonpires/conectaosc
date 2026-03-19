# Operacao Pos-Corte - App Clinica (Angular)

## Modos de front (`clinica/index.php`)
A selecao do front agora suporta modo por variavel de ambiente:

- `CLINICA_FRONT_MODE=auto` (padrao)
  - Usa Angular quando build existe.
  - Cai para legado se build nao estiver disponivel.

- `CLINICA_FRONT_MODE=angular`
  - Forca Angular.
  - Se build nao existir, retorna `503`.

- `CLINICA_FRONT_MODE=legacy`
  - Forca front legado.

Tambem existe fallback manual por URL:
- `/clinica/?legacy=1`

## Smoke test automatizado
Script:
- `clinica/frontend-angular/scripts/post_cut_smoke.ps1`

Execucao (na raiz do projeto):
```powershell
cd clinica/frontend-angular
powershell -ExecutionPolicy Bypass -File .\scripts\post_cut_smoke.ps1
```

Opcional com base URL customizada:
```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\post_cut_smoke.ps1 -BaseUrl "http://localhost/conectaosc3/clinica"
```

## Checklist manual rapido
1. Abrir `/clinica/` sem sessao: deve redirecionar para login.
2. Abrir `/clinica/frontend-angular/dashboard`: deve carregar SPA.
3. Validar navegacao principal: Dashboard, Agenda, Pacientes, Prontuario, Anamnese, Configuracoes.
4. Validar API sem regressao: `/clinica/api/pacientes` responde JSON de autenticacao sem sessao.
5. Validar fallback legado: `/clinica/?legacy=1`.
