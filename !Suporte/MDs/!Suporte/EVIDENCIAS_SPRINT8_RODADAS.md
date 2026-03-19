# Evidencias - Sprint 8 (Rodadas Automatizadas)

## Ambiente
- Base URL: `http://localhost/conectaosc3`
- Data da execucao: 2026-02-18
- Usuario de teste: `anderson@iteva.org.br`

## Rodada 1 (smoke amplo)
### Resultado
- Auth e navegacao front: OK.
- Rotas front testadas: dashboard, swot, chamada, feriados, crm, backup, alertas, beneficiarios, cursos, turmas, matriculas, eventos, relatorios, clinica.
- CRUD evento (create/delete): OK.
- API autenticada: retornou `401` nesta rodada apos ciclo login/logout/login dentro do mesmo script.

### Observacao tecnica
- A anomalia de `401` foi reproduzida apenas no encadeamento especifico do script da Rodada 1.
- Rodada 2 foi executada com sessao continua para confirmar estado real.

## Rodada 2 (confirmacao de sessao continua)
### Resultado
- `GET /api/v1/me` apos login: `200`.
- API smoke autenticada:
  - `GET /api/v1/health` -> `200`
  - `GET /api/v1/me` -> `200`
  - `GET /api/v1/config` -> `200`
  - `GET /api/v1/cursos` -> `200`
  - `GET /api/v1/turmas` -> `200`
  - `GET /api/v1/eventos/inscritos?limit=3` -> `200`
- CRUD curso com rollback:
  - create -> OK (`id=31`)
  - delete -> OK
- Logout final -> `http://localhost/conectaosc3/login/`

## Conclusao das Rodadas
- Estado funcional principal aprovado para homologacao manual guiada por negocio.
- Sem bloqueios tecnicos criticos para avancar com Sprint 8 (fase manual por modulo/perfil).

## Rodada 3 (friendly URL persistente em modulos front)
### Resultado
- Login com sessao ativa via `email/password`: OK.
- Navegacao mantendo URL amigavel (sem cair em `app/*.php`):
  - `/dashboard/` -> `200`
  - `/cursos/` -> `200`
  - `/turmas/` -> `200`
  - `/colaboradores/` -> `200`
  - `/swot/` -> `200`
  - `/feriados/` -> `200`
  - `/projetos/` -> `200`
- Fluxo de edicao de inscrito:
  - `/eventos/inscritos/editar/?IdInscrito=1` redireciona para `/eventos/inscritos/?erro=Inscrito+n�o+encontrado` quando o registro nao existe.

### Observacao tecnica
- Lint PHP executado nos arquivos alterados: sem erros de sintaxe.
- Aviso nao bloqueante de ambiente persistente: extensao `imagick` ausente no PHP CLI.

## Rodada 4 (correcao final de encoding e redirects residuais)
### Resultado
- Limpeza de texto corrompido em `app/evento/alteraInscrito.php`: concluida.
- Redirects de `app/permissao/formPaginas.php` migrados para friendly URL:
  - `save/delete/cancel` agora retornam para `/permissoes/paginas/`.
- Validacao HTTP apos login:
  - `/permissoes/paginas/` -> `200`
  - `/colaboradores/reset-senha/` -> `200`
  - `/eventos/inscritos/editar/?IdInscrito=1` -> fallback funcional para `/eventos/inscritos/?erro=...` quando inexistente.

### Validacao tecnica
- `php -l` sem erro para:
  - `app/evento/alteraInscrito.php`
  - `app/permissao/formPaginas.php`
- Varredura sem ocorrencias para:
  - caracteres corrompidos (`�`, `�`, `�`) nos arquivos ajustados
  - redirects `Location: *.php` nos modulos corrigidos

## Rodada 5 (dashboard console e favicon)
### Resultado
- `assets/js/app.js` restaurado para versao valida e carregado no dashboard/login.
- Cache bust aplicado em:
  - `index.php` -> `assets/js/app.js?v=<filemtime>`
  - `login.php` -> `assets/js/app.js?v=<filemtime>`
- Favicon corrigido com fallback para arquivo existente:
  - `template/header.php`
  - `login.php`
- Validacoes HTTP:
  - Login favicon: `200` em `/assets/img/icons/icon-48x48.png`
  - Dashboard autenticado referencia `app.js?v=...`
  - Dashboard autenticado referencia favicon `.../assets/img/icons/icon-48x48.png`

### Observacao tecnica
- O erro em cascata (`feather`, `Chart`, `jsVectorMap`, `flatpickr`) e consequencia tipica de falha de parse em `app.js`.
- Com o bundle valido + cache bust, o navegador deve carregar a versao corrigida sem exigir limpeza manual de cache.

## Rodada 6 (varredura final de modulos auxiliares)
### Resultado
- Front router recebeu suporte `POST` para rotas administrativas usadas por formularios:
  - `/colaboradores/`
  - `/projetos/`
  - `/permissoes/`
  - `/permissoes/paginas/`
- Modulo Quizz integrado ao roteamento amigavel:
  - `GET /quizz/avaliacao-professor/`
  - `POST /quizz/avaliacao-professor/`
  - `GET /quizz/avaliacao-professor/sucesso/`
- Ajustes de links/acoes para URLs amigaveis em:
  - `app/colaborador/formColaborador.php`
  - `app/projeto/formProjeto.php`
  - `app/permissao/listPermissaoView.php`
  - `app/permissao/formPermissaoView.php`
  - `app/quizz/cadAvaliaprofessor.php`
  - `app/quizz/formAvaliaprofessor.php`
  - `app/quizz/professoressucess.php`
  - `app/quizz/menuprofessor.php`

### Validacao
- `php -l` sem erros nos arquivos alterados.
- Smoke autenticado:
  - `/colaboradores/` -> `200`
  - `/projetos/` -> `200`
  - `/permissoes/?view=list` -> `200`
  - `/permissoes/?view=form` -> `200`
  - `/quizz/avaliacao-professor/` -> `200`
  - `/quizz/avaliacao-professor/sucesso/` -> `200`
  - `POST /projetos/` -> `200` com retorno para rota amigavel
- Varredura sem ocorrencias de redirects `Location: *.php` nos modulos ativos (excluidos backups/migrations/testes).

## Rodada 7 (correcao de fatals em modulos admin)
### Resultado
- Corrigida causa raiz de `Fatal error` em:
  - `/feriados/`
  - `/colaboradores/`
  - `/projetos/`
- Causa: conexao PDO carregada em escopo local de include (render via metodo) e indisponivel para `global $pdo` dos models.
- Ajuste aplicado:
  - `conectabd/conexao.php` agora publica `\$pdo` em `\$GLOBALS['pdo']`.

### Validacao
- Reteste HTTP autenticado:
  - `/feriados/` -> `200` sem `Fatal error/Warning`
  - `/colaboradores/` -> `200` sem `Fatal error/Warning`
  - `/projetos/` -> `200` sem `Fatal error/Warning`
- API `GET /api/v1/me` em sessao limpa com `Accept: application/json` -> `200`.
