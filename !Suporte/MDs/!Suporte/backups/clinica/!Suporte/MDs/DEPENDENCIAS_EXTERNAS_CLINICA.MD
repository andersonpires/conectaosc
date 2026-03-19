# Dependências externas do App `/clinica` (fora da raiz `/clinica`)

## Escopo
Este documento rastreia dependências do app `clinica` para módulos/arquivos fora de `clinica/`, principalmente no app raiz `/conectaosc`.

## Contexto de entrada (ConectaOSC -> Clínica)
- Origem: `template/menu.php:44`
- Chamada: link para `$_SESSION['BASE_URL']/clinica/` com `target="_blank"`.
- Tipo: URL montada por sessão (`BASE_URL`) no app principal.

## Matriz de dependências de arquivos
| Módulo de origem (em `/clinica`) | Arquivo de origem | Dependência externa (em `/conectaosc`) | Como a chamada é feita | Tipo de acoplamento |
|---|---|---|---|---|
| Bootstrap front da clínica | `clinica/index.php` | `conectabd/conexao.php` | `require_once $conectaoscPath . '/conectabd/conexao.php'`, com `$conectaoscPath = dirname(__DIR__)` | Caminho relativo por `dirname` |
| Bootstrap front da clínica | `clinica/index.php` | `login.php` | `header('Location: ' . $_SESSION['BASE_URL'] . '/login.php?...')` com `BASE_URL` contendo `/conectaosc` | URL por sessão + path hardcoded (`/conectaosc`) |
| Bootstrap API | `clinica/api/index.php` | `conectabd/conexao.php` | `require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php'` | Caminho via sessão (`BASE_PATH`) |
| Core DB da API | `clinica/api/app/Core/Database.php` | `conectabd/conexao.php` | `$_SESSION['BASE_PATH'] ?? dirname(dirname(dirname(dirname(__DIR__))))` e depois `require_once` | Fallback por `__DIR__` + sessão |
| Controller de prontuários (IA) | `clinica/api/app/Controllers/ProntuariosController.php` | `temp/openaikey.php` | `require_once $basePath . '/temp/openaikey.php'` com `$basePath` vindo de sessão/fallback `dirname(...)` | Caminho via sessão + fallback `__DIR__` |
| Controller de prontuários (link PDF) | `clinica/api/app/Controllers/ProntuariosController.php` | `clinica/gerarProntuarioPdf.php` (endpoint sob app raiz `/conectaosc`) | monta URL com `$_SESSION['BASE_URL']` ou `http(s)://{host}/conectaosc` | URL com base hardcoded em `/conectaosc` |
| Geração de PDF | `clinica/gerarProntuarioPdf.php` | `conectabd/conexao.php` | `require_once $basePath . '/conectabd/conexao.php'` | Caminho via sessão/fallback |
| Geração de PDF | `clinica/gerarProntuarioPdf.php` | `lib/tcpdf/tcpdf.php` | `require_once $basePath . '/lib/tcpdf/tcpdf.php'` | Dependência de biblioteca compartilhada do app raiz |
| Geração de PDF | `clinica/gerarProntuarioPdf.php` | `lib/tcpdf/fonts/licorice.php` e `lib/tcpdf/fonts/Licorice-Regular.ttf` | `file_exists(...)` e carregamento condicional | Caminho absoluto interno ao `/conectaosc` |
| Geração de PDF | `clinica/gerarProntuarioPdf.php` | `assets/img/logos/*`, `assets/img/sistema/logo.png`, `assets/img/fotos/*` | resolução de logo por lista de candidatos com `$basePath` | Dependência de assets globais do sistema principal |
| Geração de PDF assinado | `clinica/gerarProntuarioPdf.php` | `app/assinatura/assinados/` | grava arquivo PDF em `$basePath . '/app/assinatura/assinados/'` | Dependência de diretório/módulo de assinatura do app raiz |
| Validação pública do PDF | `clinica/verProntuarioPdf.php` | `conectabd/conexao.php` | `require_once dirname(__DIR__) . '/conectabd/conexao.php'` | Caminho por `dirname(__DIR__)` |
| Frontend SPA (logo no header) | `clinica/index.html` | `assets/img/sistema/logo.png` | `<img src="../assets/img/sistema/logo.png">` | Caminho relativo (`../`) |
| Frontend SPA (erro de sessão) | `clinica/src/app.js` | `login.php` | monta `${window.location.origin}/conectaosc/login.php?redirect=...` | URL hardcoded com `/conectaosc` |
| Migrations de suporte | `clinica/!Suporte/migrations/*_run_*.php` | `conectabd/conexao.php` | padrão: `require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php'` | Caminho via sessão |
| Migration 001 (fallback visual) | `clinica/!Suporte/migrations/001_run_alter_tbuser_profissional_saude.php` | `login.php` | link com `dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))) . '/login.php'` | URL relativa calculada via `SCRIPT_NAME` |

## Dependências de convenção/path (sem `require`, mas com acoplamento de URL)
- `clinica/api/app/Core/Env.php`: define base fixa `'/conectaosc'` para montar `APP_BASE_URL`, `FRONT_BASE_URL` e `API_BASE_URL`.
- `clinica/api/index.php`: roteamento considera `'/conectaosc/clinica/api'` ou fallback `'/clinica/api'`.
- `clinica/index.php`: redirect de login usa `'/conectaosc/clinica/'` como retorno.

## Dependências de banco compartilhado (fora do domínio isolado da clínica)
Mesmo quando não há `require` de arquivo externo além de `conectabd/conexao.php`, vários módulos da clínica dependem de tabelas legadas do ConectaOSC:
- `tbUser` (perfil profissional, especialidade e identificação de usuário).
- `tbAluno` (dados de pacientes/alunos).
- `tbConfig` (logo de impressão).
- `tbpdf_assinado` (registro de PDFs assinados, módulo de assinatura).

## Formas de chamada identificadas
- `dirname(...)`/`__DIR__`: usado para subir da pasta `clinica` até a raiz `/conectaosc`.
- Sessão (`$_SESSION['BASE_PATH']`, `$_SESSION['BASE_URL']`): principal mecanismo para resolver caminhos e URLs do app principal.
- Hardcode de URL/path: ocorrências com `'/conectaosc'` e, em produção, `https://iteva.com.br/conectaosc`.
- Caminho relativo estático: `../assets/...` no HTML.

## Pontos críticos de acoplamento
- Dependência forte de `$_SESSION['BASE_PATH']` e `$_SESSION['Cod']` para autenticação e acesso à conexão compartilhada.
- Dependência explícita de estrutura física do app raiz (`conectabd`, `lib/tcpdf`, `assets`, `app/assinatura`, `temp/openaikey.php`).
- Dependência de path hardcoded `/conectaosc` em múltiplos pontos, dificultando execução sob outro subdiretório sem ajuste.
