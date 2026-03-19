# Checklist de Homologacao - Sprint 8

## Objetivo
Validar funcionalmente os modulos principais com foco em regras de negocio, permissao por perfil e estabilidade final antes de producao.

## Escopo da Rodada
- Autenticacao e sessao.
- Navegacao por menu (Friendly URL).
- Modulos: Beneficiario, Matricula, Evento, Relatorios, Clinica.
- API REST principal.

## Perfis para Teste
- Administrador.
- Perfil operacional (sem acesso administrativo).
- Perfil sem permissao para Clinica.
- Perfil profissional de saude (acesso Clinica).

## Criterios de Aprovacao
- [x] Nenhum erro fatal/warning em tela.
- Rotas sem quebra e sem retorno indevido para login.
- Operacoes CRUD principais com retorno esperado.
- Permissoes respeitadas por perfil.
- Consistencia entre front e API.

## Bloco 1 - Autenticacao e Sessao
- [x] Login com credenciais validas.
- [ ] Login com credenciais invalidas.
- [x] Logout e retorno para `/login/`.
- [ ] Sessao expirada redireciona corretamente para login.
- [ ] Redirect apos login respeita `?redirect=`.

## Bloco 2 - Menu e Navegacao
- [x] Menu carrega sem links `*.php` visiveis ao usuario.
- [ ] Item ativo do menu acompanha rota atual.
- [x] Todas as rotas de primeiro nivel abrem sem erro.
- [ ] Submenus expandem/colapsam corretamente.

## Bloco 3 - Beneficiario
- [ ] Cadastro novo (dados obrigatorios).
- [ ] Edicao de cadastro existente.
- [ ] Exclusao/inativacao conforme regra.
- [ ] Listagens e busca por CPF/nome.
- [ ] Exportacoes/PDF vinculados.

## Bloco 4 - Cursos/Turmas/Matriculas
- [ ] Cadastro e edicao de curso.
- [ ] Cadastro e edicao de turma.
- [ ] Matricula em lote com validacao de faixa etaria.
- [ ] Validacao de limite maximo de turma.
- [ ] Inativacao/reativacao de matricula.

## Bloco 5 - Eventos
- [ ] Inscricao publica.
- [ ] Bloqueio de email duplicado.
- [ ] Edicao de inscrito.
- [ ] Exclusao de inscrito.
- [ ] Listagem administrativa de inscritos.

## Bloco 6 - Relatorios
- [ ] Frequencia mensal.
- [ ] Frequencia por intervalo.
- [ ] Matriculados.
- [ ] Presenca por aula/turma.
- [ ] Relatorio personalizado.

## Bloco 7 - Clinica
- [ ] Acesso permitido para profissional de saude.
- [ ] Acesso bloqueado para perfil sem permissao.
- [ ] Fluxo de entrada via menu principal.
- [ ] API da clinica responde sem erro de sessao.
- [ ] Retorno ao ConectaOSC sem quebrar sessao.

## Bloco 8 - API (Smoke)
- [x] `GET /api/v1/health` = 200.
- [x] `GET /api/v1/me` = 200 autenticado.
- [x] `GET /api/v1/config` = 200 autenticado.
- [x] `GET /api/v1/cursos` = 200 autenticado.
- [x] `GET /api/v1/turmas` = 200 autenticado.
- [x] `GET /api/v1/eventos/inscritos` = 200 autenticado.

## Bloco 9 - API Minima Para Integracao do Clinica
- [x] `GET /api/v1/me` retorna tambem `nome_completo`.
- [x] `GET /api/v1/me` retorna tambem `profissional_saude`.
- [x] `GET /api/v1/me` retorna tambem `especialidade_id`.
- [x] `GET /api/v1/colaboradores/profissionais-saude` = 200 autenticado.
- [ ] `GET /api/v1/colaboradores/profissionais-saude` aceita filtro `search`.
- [ ] `GET /api/v1/colaboradores/profissionais-saude` aceita filtro `especialidade_id`.
- [x] `GET /api/v1/colaboradores/profissionais-saude` retorna `id`, `nome`, `sobrenome`, `nome_completo`, `especialidade_id`, `profissional_saude`, `habilitado`.
- [x] `GET /api/v1/beneficiarios/{id}` = 200 autenticado para registro existente.
- [x] `GET /api/v1/beneficiarios/{id}` = 404 para registro inexistente.
- [x] `GET /api/v1/beneficiarios/{id}` retorna os campos minimos usados pelo `clinica`.
- [x] `GET /api/v1/beneficiarios/busca` = 200 autenticado.
- [ ] `GET /api/v1/beneficiarios/busca` aceita `search`.
- [ ] `GET /api/v1/beneficiarios/busca` aceita `page`.
- [ ] `GET /api/v1/beneficiarios/busca` aceita `per_page`.
- [ ] `GET /api/v1/beneficiarios/busca` retorna `items` e `meta`.
- [ ] `GET /api/v1/beneficiarios/busca` respeita ordenacao e paginacao.
- [x] Opcional: `GET /api/v1/colaboradores/{id}` = 200 autenticado para registro existente.
- [x] Opcional: `GET /api/v1/colaboradores/{id}` = 404 para registro inexistente.
- [x] `GET /api/v1/colaboradores-publicos/{id}/nome` = 200 publico para registro existente.
- [x] `GET /api/v1/colaboradores-publicos/{id}/nome` = 404 publico para registro inexistente.
- [x] `GET /api/v1/colaboradores-publicos/{id}/nome` retorna apenas `id` e `nome_completo`.

## Bloco 10 - Clinica Integrado Com Endpoints Minimos
- [x] `clinica/index.php` consegue validar perfil usando dados vindos de `/api/v1/me`.
- [x] `clinica/api/index.php` consegue montar sessao clinica usando dados vindos de `/api/v1/me`.
- [x] `clinica/api/app/Controllers/ProfissionaisController.php` deixa de depender de SQL direto em `tbUser`.
- [x] `clinica/api/app/Controllers/PacientesController.php` usa lookup unitario de beneficiario quando aplicavel.
- [x] `clinica/api/app/Controllers/ProntuariosController.php` usa lookup unitario de beneficiario quando aplicavel.
- [x] `clinica/api/app/Controllers/AgendaController.php` resolve nome de profissional sem `JOIN tbUser`.
- [x] `clinica/api/app/Controllers/AnamneseController.php` resolve nome de profissional sem `JOIN tbUser`.
- [x] `clinica/gerarProntuarioPdf.php` resolve nome de profissional via API.
- [x] `clinica/verProntuarioPdf.php` resolve nome de profissional via endpoint publico minimo.
- [ ] Fluxo de pacientes do `clinica` funciona sem regressao visual.
- [ ] Fluxo de prontuario/IA do `clinica` funciona sem regressao funcional.

## Evidencias
- Data da rodada: 2026-02-18
- Responsavel:
- Ambiente:
- Arquivo de evidencias: `EVIDENCIAS_SPRINT8_RODADAS.md`

## Resultado Final da Sprint 8
- Status: [ ] Aprovado [x] Aprovado com ressalvas [ ] Reprovado
- Observacoes:
- Homologacao tecnica concluida com rotas friendly URL, API smoke e correcoes de estabilidade aplicadas.
- Ressalvas: pendente homologacao funcional completa por perfil e regras de negocio (blocos 3 a 7).
