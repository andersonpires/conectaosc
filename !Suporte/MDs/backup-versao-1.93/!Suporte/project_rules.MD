# Guia de Regras e Padrões de Desenvolvimento  
## ConectaOSC – Gestão de OSCs

Este documento define **todas as regras técnicas, arquiteturais e funcionais obrigatórias** do projeto **ConectaOSC**.

Sempre que uma IA gerar **código, SQL, arquitetura, lógica de negócio, endpoints, páginas, componentes de UI ou testes**, ela **DEVE obedecer integralmente** às regras descritas abaixo.

---

## 1. Arquitetura Geral

- Aplicação **monolítica em PHP** (front + back no mesmo projeto).
- Não existe separação de front/back em projetos distintos.
- As páginas PHP são renderizadas no servidor e usam templates compartilhados.
- Sempre usar codificação UTF‑8.
---

## 2. Stack Tecnológica Oficial

- PHP (uso predominante de **PDO**).
- Banco MySQL.
- Bibliotecas PHP via Composer:
  - `google/apiclient` (ver `composer.json`).
- Front-end baseado em **Bootstrap 5** e pacote `assets/css/app.css`.
- Ícones e UI:
  - Font Awesome 6.5
  - Feather Icons
- JS utilitários:
  - Alpine.js (CDN)
  - Chart.js (embutido no app template)
  - DataTables (CDN)

---

## 3. Configuração de Caminhos (Base Path)

### Arquivo de referência
- `config.php`

### Regras
- `BASE_PATH` = caminho absoluto do servidor (`__DIR__`).
- `BASE_URL` = caminho relativo do projeto (`dirname($_SERVER['SCRIPT_NAME'])`).
- **Toda inclusão de arquivos** deve usar `$_SESSION['BASE_PATH']` ou `BASE_PATH`.
- **Toda URL** para assets/rotas deve usar `$_SESSION['BASE_URL']` ou `BASE_URL`.

### Regra obrigatória
- Sempre garantir que `$_SESSION['BASE_PATH']` e `$_SESSION['BASE_URL']` estejam definidos (feito no `login.php`).

---

## 4. Sessão, Cookies e Autenticação

### Sessão
- Sempre iniciar sessão com:
  - `session_set_cookie_params(['httponly' => true])`
  - `session_start()`
  - `session_regenerate_id(true)`
- Tempo de sessão: `86400` segundos (24h) via `session.gc_maxlifetime`.

### Fuso horário
- **Obrigatório**: `date_default_timezone_set('America/Sao_Paulo')`.

### Cookie de login persistente
- Cookie: `login_v3`
- Formato: Base64 JSON `{ token, id }`
- Expiração: 30 dias
- Deve ser limpo em logout e em inconsistências de token.

### Token de sessão
- Gerado como `time() * 10` + mês (`date('M')`).
- Validação feita em `checa-token.php`:
  - sufixo do token deve ser o mês atual
  - token % 10 deve ser 0
  - usuário deve ter horário permitido

---

## 5. Autenticação, Permissões e Horários

### Autenticação
- Login é realizado via **email + senha** em `login.php`.
- Hash de senha deve usar `password_hash`.
- Verificação deve usar `password_verify`.

### Permissões por página
- O sistema trabalha com **permissões por página**.
- Tabela base: `tbPermissaoPagina`.
- View de apoio: `vwPermissaoUsuario`.
- Sessão armazena `PaginasPermitidas`.

### Regra obrigatória
Toda página protegida deve:
1. Incluir `checa-token.php`.
2. Validar se a página está em `$_SESSION['PaginasPermitidas']`.
3. Negar acesso para usuários sem permissão.

### Horários de acesso
- Controle feito por `tbPermissaoHorario`.
- Função: `verificaHorarioPermissao()` em `funcoes.php`.
- Se o usuário não tiver horário autorizado, o login deve ser bloqueado.

### Exceção
- Usuário com tipo `Superadministrador` **pode acessar todas as páginas**.

---

## 6. Conexão com Banco de Dados (Regra Crítica)

### Conexão principal
Arquivo: `conectabd/conexao.php`

Credenciais padrão (atuais):
- Host: `162.241.2.214`
- Banco: `mwtech63_matricula`
- Usuário: `mwtech63_admin_matricula`
- Senha: `Iteva@100`
- Porta: `3306`

### Conexão local alternativa
Arquivo: `conectabd/conexao_server.php`

Credenciais:
- Host: `localhost`
- Banco: `mwtech63_matricula`
- Usuário: `mwtech63_admin_matricula`
- Senha: `Iteva@100`
- Porta: `3306`

### Conexão auxiliar (legado)
Arquivo: `lib/loginserver.php`

Credenciais:
- Host: `localhost`
- Usuário: `root`
- Senha: `F@ce1991!`
- Banco: `mwtech63_matricula`
- Porta: `3306`

### Regras obrigatórias
- Sempre usar **PDO**.
- Charset obrigatório: `utf8` (definido no DSN).
- `PDO::ATTR_ERRMODE` = `PDO::ERRMODE_EXCEPTION`.
- `PDO::ATTR_DEFAULT_FETCH_MODE` = `PDO::FETCH_ASSOC`.
- Nunca usar `mysqli` em novas funcionalidades (apenas legado em `app/evento/antigos`).
- Nunca expor credenciais em respostas ou logs públicos.

---

## 7. Templates e Layout Padrão

Todas as páginas autenticadas devem seguir o padrão:

1. `template/header.php`
2. `template/menu.php`
3. `template/topo.php`
4. Conteúdo da página
5. `template/footer.php`

### Header
- Carrega metadados de SEO com dados da tabela `tbConfig`.
- Inclui CSS principal: `assets/css/app.css`.
- Inclui CSS dinâmico: `assets/css/override.php?versao=...`.
- Inclui Bootstrap 5, DataTables, Font Awesome e Inter.

### Menu
- Sidebar é construída com links para módulos em `app/`.
- Item ativo é marcado com base em `basename($_SERVER['PHP_SELF'])`.
- Novas páginas devem:
  - Ter o `id` do `<li>` igual ao nome do arquivo PHP.
  - Ser registradas em `tbPermissaoPagina`.
  - Ser adicionadas no menu quando necessário.

### Topo
- Mostra avatar, nome do usuário e menu de ações.
- Acesso a perfil, configurações, backup e logout.

### Footer
- Exibe nome do sistema a partir de `tbConfig`.
- Link para termos de uso via `template/paginasFooter.php`.

---

## 8. Padrão para Criação de Páginas

### Obrigatório em qualquer nova página autenticada
1. Iniciar sessão e definir timezone.
2. Garantir `$_SESSION['BASE_PATH']` e `$_SESSION['BASE_URL']`.
3. Incluir `checa-token.php`.
4. Incluir templates padrão (header/menu/topo/footer).

### Estrutura mínima recomendada
- Bloco PHP de autenticação.
- HTML com wrapper:
  - `<div class="wrapper">`
  - `<div class="main">`
- Scripts no final com `assets/js/app.js`.

---

## 9. Pastas Principais e Responsabilidades

- `app/`: módulos funcionais (beneficiário, cursos, turmas, relatório, etc.).
- `template/`: layout padrão (header, menu, topo, footer, páginas auxiliares).
- `conectabd/`: conexões com banco de dados.
- `get/`: endpoints para exportação e relatórios (PDF, Excel, JSON, HTML).
- `assets/`: CSS, JS, imagens e estilos.
- `fonts/`: fontes e recursos tipográficos adicionais.
- `lib/`: bibliotecas auxiliares (PDF e utilitários).
- `banco/`: scripts SQL e backups históricos.
- `temp/`: chaves e integrações externas (API keys) e logs.
- `vendor/`: dependências instaladas via Composer.
- `testes/`: scripts de teste e diagnóstico.

---

## 10. Relatórios e Exportações

- Endpoints em `get/` geram relatórios em HTML, JSON, PDF e Excel.
- Funções típicas:
  - Frequência, matrícula, turmas, cursos.
- Os relatórios devem sempre:
  - Usar PDO com prepared statements.
  - Retornar dados coerentes com permissões do usuário.

---

## 11. Arquivos e Uploads

### Assinatura Digital
- Pasta: `app/assinatura/`
  - `originais/`: PDFs enviados.
  - `assinados/`: PDFs assinados.
  - `qrcodes/`: imagens dos QR Codes.

### Imagens de usuários
- Pastas em `assets/img/` (ex: `fotos`, `sistema`, `avatars`).

---

## 12. Segurança e Boas Práticas

- Sempre usar prepared statements no PDO.
- Nunca concatenar SQL manualmente com dados do usuário.
- Validar e sanitizar entradas (`htmlspecialchars`, `intval`, `filter_*`).
- Nunca expor arquivos de `temp/` em rotas públicas.
- Não versionar nem expor chaves de API em páginas.

---

## 13. Banco de Dados – Convenções e Estrutura

### Tabelas principais observadas
- `tbUser`
- `tbPermissao`
- `tbPermissaoPagina`
- `tbPermissaoHorario`
- `tbConfig`
- `tbAluno`
- `tbCurso`
- `tbTurma`
- `tbMatricula`
- `tbChamada`

### Views observadas
- `vwPermissaoUsuario`
- `vwFaixaetaria`

### Regra obrigatória
- Novas tabelas devem seguir o padrão de prefixo `tb` (ex: `tbNovaTabela`).
- Novas views devem seguir o padrão de prefixo `vw` (ex: `vwResumo`).

---

## 14. Regras de Logs e Erros

- Erros críticos devem ser registrados via `error_log()`.
- Não exibir stack trace ou detalhes sensíveis para usuário final.
- Arquivos de log já existentes: `error_log` na raiz e logs em `temp/`.

---

## 15. Backup e Scripts SQL

- Pastas `banco/` e `!Suporte/backups/` contêm dumps e scripts.
- Qualquer alteração estrutural nova deve ter script correspondente em `banco/`.
- Nunca rodar scripts SQL em produção sem backup recente.

---

## 16. Regra Final (Crítica)

Sempre que a IA:
- Criar código
- Criar SQL
- Criar páginas
- Criar endpoints
- Criar regras de negócio

Ela **DEVE**:
1. Seguir este documento integralmente.
2. Manter compatibilidade com o template atual.
3. Respeitar a arquitetura monolítica e o padrão de permissões.
4. Priorizar segurança, consistência e rastreabilidade.

Este documento é obrigatório, permanente e imutável, salvo decisão explícita do responsável técnico do projeto.

