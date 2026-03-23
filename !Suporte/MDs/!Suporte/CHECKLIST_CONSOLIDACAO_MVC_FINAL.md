# Checklist de Consolidacao MVC (Front)

Data: 2026-02-20

## Estrutura raiz
- [x] `index.php` na raiz
- [x] `.htaccess` na raiz
- [x] `app/` e `api/` como pastas principais
- [x] `clinica/` preservada sem reorganizacao interna

## Front MVC
- [x] `app/controllers` com controladores de rota
- [x] `app/routes/web.php` ativo
- [x] `app/core` ativo
- [x] `app/views/*` criado por modulo
- [x] Wrappers de compatibilidade mantidos em `app/<modulo>/*.php`

## Back MVC/API
- [x] `api/public/index.php` como entrypoint
- [x] `api/routes/api.php` com rotas REST
- [x] `api/controllers`, `api/services`, `api/repositories`, `api/core`
- [x] Respostas JSON padronizadas no back

## Rotas e consistencia
- [x] Rotas front validadas (targets existentes)
- [x] Rotas API validadas (targets existentes)
- [x] Lint geral da base (exceto `clinica`, `vendor`, `!Suporte`) sem erros de sintaxe
- [x] Integracao principal do `clinica/` com `/api/v1` concluida
- [x] Runtime do `clinica/` sem leitura direta de `tbUser` para nome/perfil compartilhado

## Pendencias tecnicas (nao bloqueantes da estrutura)
- [ ] Revisao de encoding UTF-8 em arquivos legados com textos corrompidos
- [ ] Limpeza futura de wrappers legados apos homologacao completa
- [ ] Testes de navegacao ponta-a-ponta em todos os CRUDs no navegador

## Observacao
- Warning do `imagick` observado no ambiente local de PHP nao invalida a sintaxe dos arquivos.
- Warning de `ftp` observado no ambiente local de PHP tambem nao invalida a sintaxe dos arquivos.
