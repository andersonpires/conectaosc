# Guia Oficial de Regras e Padroes de Desenvolvimento
## ConectaOSC - Arquitetura Atual (exceto /clinica)

Este documento substitui regras antigas e define o padrao oficial do sistema ConectaOSC no estado atual do projeto.
Escopo: todo o sistema principal, exceto o app Clinica em `/clinica`.

---

## 1. Arquitetura Geral Atual

- A aplicacao continua monolitica em PHP (frontend e backend no mesmo repositorio).
- Entrada web principal:
  - `index.php` (raiz) -> `app/index.php`.
- A camada web usa roteamento interno em `app/routes/web.php`, com controllers em `app/controllers`.
- As telas SSR (server-side rendered) permanecem em `app/views/*`.
- A API REST principal fica em `api/public/index.php` com rotas em `api/routes/api.php` sob prefixo `/api/v1`.
- Codificacao obrigatoria: UTF-8.

---

## 2. Stack Tecnologica Oficial

- PHP 8+.
- MySQL.
- PDO como driver oficial de banco.
- Composer:
  - `google/apiclient` (conforme `composer.json`).
- Front-end:
  - Bootstrap 5
  - CSS principal em `app/assets/css/app.css`
  - CSS dinamico em `app/assets/css/override.php`
  - Font Awesome
  - Feather Icons
  - Alpine.js
  - DataTables

---

## 3. Base Path e Runtime (regra oficial)

### Arquivo de referencia
- `bootstrap/runtime.php`

### Regras obrigatorias
- Nao usar `config.php` raiz como base de arquitetura (arquivo legado/nao usado no fluxo atual).
- Definicoes de runtime devem vir de:
  - `base_para_path`
  - `base_para_url`
- Em paginas SSR, usar:
  - `$BASE_para_PATH`
  - `$BASE_para_URL`
- Includes de arquivo devem resolver a partir de `$BASE_para_PATH`.
- URLs internas e de assets devem resolver a partir de `$BASE_para_URL`.

---

## 4. Sessao, Cookie e Autenticacao

### Sessao
- Sessao oficial usa nome `PHPSESSID3`.
- Inicializacao principal de sessao ocorre via `bootstrap/runtime.php` (web) e `api/public/index.php` (API).
- Timezone obrigatorio:
  - `America/Sao_Paulo`

### Cookie de autenticacao persistente
- Nome de cookie de auth:
  - configuravel por `temp/setCookie.env`
  - fallback atual: `login_v43`
- Formato:
  - Base64 de JSON `{ token, id }`
- Expiracao:
  - 30 dias quando "lembrar" estiver ativo.
- Deve ser limpo em logout e em inconsistencias de autenticacao.

### Token de sessao legado
- Mantido por compatibilidade:
  - `time() * 10` + mes abreviado (`date('M')`)
- Validacao de token/horario/permissao centralizada em `api/legacy/checa-token.php`.

---

## 5. Autorizacao, Permissoes e Protecao de Paginas (regra critica)

### Modelo oficial
- Permissao por pagina baseada em:
  - `tbPermissaoPagina`
  - `vwPermissaoUsuario`
  - `$_SESSION['PaginasPermitidas']`
- Excecao:
  - `Superadministrador` pode acessar todas as paginas.

### Regra obrigatoria para paginas protegidas SSR
- Toda pagina/rota protegida deve aplicar validacao de autenticacao + permissao antes de renderizar:
  1. incluir `api/legacy/checa-token.php` diretamente na view
  2. ou garantir que o controller/flow execute essa validacao antes de `require` da view.

### Regra obrigatoria adicional
- Dashboard autenticado tambem deve obedecer a validacao de permissao/token/horario (mesma regra das demais paginas protegidas).

### APIs
- Endpoints de `/api/v1` protegidos devem usar middleware de sessao (`SessionAuthMiddleware`) conforme roteamento.

---

## 6. Banco de Dados (regra critica)

### Conexao oficial
- Arquivo: `api/conectabd/conexao.php`

### Regras obrigatorias
- Usar apenas PDO para novas implementacoes.
- Charset obrigatorio: `utf8mb4`.
- `PDO::ATTR_ERRMODE = PDO::ERRMODE_EXCEPTION`.
- `PDO::ATTR_DEFAULT_FETCH_MODE = PDO::FETCH_ASSOC`.
- Proibido usar `mysqli` em novas funcionalidades.
- Nao expor credenciais em saidas publicas.

### SQL seguro (obrigatorio)
- Sempre usar `prepare` + bind/execute para entradas de usuario.
- Proibido concatenar input de usuario direto em SQL (`$_GET`, `$_POST`, etc.).
- Para clausulas `IN`, montar placeholders e bind parametrizado.

---

## 7. Templates e Layout Padrao (arquitetura atual)

### Padrao de includes SSR autenticado
- `app/views/partials/header.php`
- `app/views/partials/menu.php`
- `app/views/partials/topo.php`
- conteudo da pagina
- `app/views/partials/footer.php`

### Header
- SEO e configuracoes vindas de `tbConfig`.
- Carrega CSS principal e override dinamico.

### Menu/Topo/Footer
- Menu lateral por rotas atuais.
- Topo com perfil/acoes.
- Footer com nome do sistema e link de termos.

---

## 8. Padrao para Criacao de Paginas e Rotas

### Nova tela SSR autenticada
1. Resolver runtime (`$BASE_para_PATH`, `$BASE_para_URL`).
2. Aplicar validacao de autenticacao/permissao antes da renderizacao.
3. Usar partials oficiais (`header/menu/topo/footer`).
4. Usar `app/assets/js/app.js` no fim da pagina.

### Nova rota web
- Registrar em `app/routes/web.php`.
- Implementar controller/flow em `app/controllers`.
- Quando protegida, obrigatorio aplicar regra de permissao da secao 5.

### Nova rota API
- Registrar em `api/routes/api.php`.
- Controller em `api/controllers`.
- Service/repository/model conforme padrao atual.
- Quando protegida, usar middleware de sessao.

---

## 9. Estrutura de Pastas Oficiais (atual)

- `app/`: frontend SSR, rotas web, controllers e views.
- `app/views/partials/`: layout padrao atual.
- `api/`: API v1, endpoints legados (`api/get`, `api/legacy`) e cron.
- `api/conectabd/`: conexao PDO oficial.
- `bootstrap/`: runtime e bootstrap compartilhado.
- `banco/`: dumps/scripts SQL.
- `temp/`: configuracoes locais, chaves e arquivos temporarios sensiveis.
- `vendor/`: dependencias Composer.

### Observacao de migracao
- Regras antigas que citavam `template/`, `conectabd/` raiz e `get/` raiz como padrao oficial foram descontinuadas.

---

## 10. Relatorios e Exportacoes

- Fluxo atual de relatorios/exportacoes concentra-se em:
  - `api/get/*`
  - `api/controllers/RelatorioController.php`
  - `api/services/RelatorioService.php`
- `get/` raiz nao e padrao oficial para novos endpoints.
- Todo relatorio novo deve respeitar:
  - permissao de acesso
  - SQL seguro com PDO
  - coerencia com filtros/perfis do usuario.

---

## 11. Seguranca e Boas Praticas

- Validar e sanitizar entradas.
- Escapar saida HTML com `htmlspecialchars` quando aplicavel.
- Nao deixar `display_errors=1` em fluxo de producao.
- Nao exibir stack trace/detalhe sensivel para usuario final.
- Nao expor chaves/tokens de terceiros em frontend ou respostas publicas.
- Nao servir conteudos sensiveis de `temp/` em rotas publicas.

---

## 12. Logs e Erros

- Erros criticos devem ser registrados com `error_log()` ou handler central.
- API deve manter respostas controladas por `api/core/ErrorHandler.php`.
- Mensagens de erro para usuario final devem ser genericas, sem detalhes internos.

---

## 13. Banco de Dados - Convencoes

- Prefixos mantidos:
  - tabelas novas: `tb*`
  - views novas: `vw*`
- Manter padroes de nomenclatura e relacionamento ja adotados no schema principal.

---

## 14. Backup e Scripts SQL

- Alteracoes estruturais devem gerar script em `banco/`.
- Qualquer operacao estrutural em producao exige backup recente.

---

## 15. Regra Final

Ao gerar ou alterar codigo, SQL, endpoint, pagina, controller ou regra de negocio, a IA deve:

1. Seguir este documento (arquitetura atual).
2. Preservar compatibilidade com o runtime e roteamento vigente.
3. Aplicar obrigatoriamente as regras criticas de permissao (secao 5) e SQL seguro (secao 6).
4. Priorizar seguranca, consistencia e rastreabilidade.

