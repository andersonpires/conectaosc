# ConectaOSC APIs (`api/v1` + `clinica/api`)

## Visão Geral
- Sistema: ConectaOSC, com duas APIs REST ativas no projeto.
- Objetivo:
1. `api/v1`: API principal administrativa (configuração, beneficiários, cursos, turmas, matrículas, relatórios, eventos, colaboradores).
2. `clinica/api`: API do módulo Clínica (agenda, consultas, pacientes, prontuários, anamnese, feriados).
- Base URLs identificáveis:
1. `https://{host}/{base}/api/v1`
2. `https://{host}/{base}/clinica/api`
- Versionamento:
1. `api/v1` é versionada em rota (`v1`).
2. `clinica/api` não expõe versão em rota.
- Formato padrão:
1. JSON UTF-8 para quase todos endpoints.
2. Exceções com retorno HTML: alguns relatórios (`/api/v1/relatorios/.../html`) e fluxo de PDF em prontuário (`/clinica/api/prontuarios/pdf`).

---

## Autenticação
- Mecanismo: sessão PHP por cookie (`PHPSESSID3`), sem JWT/Bearer.
- Origem da sessão: login no sistema web ConectaOSC.
- Header/cookie esperado:
1. `Cookie: PHPSESSID3={session_id}`
2. Em alguns cenários, cookie legado de autenticação (`login_v43`, nome pode variar via `temp/setCookie.env`) é usado para restaurar sessão.
- Middlewares:
1. `api/v1`: `SessionAuthMiddleware` exige `$_SESSION['Cod']`.
2. `clinica/api`: `AuthMiddleware::requireAuth()` e `requireProfissionalSaude()` para rotas restritas.
- Exemplo básico:
```bash
curl -X GET "https://{host}/{base}/api/v1/me" \
  -H "Accept: application/json" \
  -b "PHPSESSID3=SEU_SESSION_ID"
```

Observação: não existe endpoint público de login nestas APIs; autenticação é herdada do sistema web.

---

## Convenções Gerais
- `Content-Type` aceito:
1. `application/json` em boa parte dos `POST/PUT`.
2. `application/x-www-form-urlencoded` ou `multipart/form-data` em vários endpoints de `api/v1` (muitos controllers leem `$_POST`).
- Headers comuns:
1. `Accept: application/json`
2. `Content-Type: application/json` (quando body JSON)
3. `Cookie: PHPSESSID3=...`
- Timezone: `America/Sao_Paulo`.
- Datas:
1. Predominante: `YYYY-MM-DD`.
2. Alguns campos de legado usam `dd/mm/YYYY` internamente.
- Paginação:
1. Implementada em `GET /api/v1/beneficiarios/busca` (`page`, `per_page`, `meta`).
- Filtros/ordenação:
1. Feitos por query/body endpoint a endpoint.
2. Ordenação geralmente fixa em SQL (`ORDER BY Nome...`).
- Idempotência:
1. `GET` idempotente.
2. `PUT` e `DELETE` variam por regra de negócio (alguns são “soft delete”).
- Rate limit:
1. `clinica/api` tem middleware de 60 req/60s por IP.
2. Observação: implementação em array estático em memória de processo PHP; pode não funcionar de forma consistente entre requisições/processos.
- Padrão de erro:
1. Estrutura padrão: `success`, `message`, `data`, `errors`.
2. Códigos observados: `400, 401, 403, 404, 422, 429, 500`.

---

## Endpoints

## API Principal (`/api/v1`)

### [GET] /api/v1/
**Descrição**  
Health check da API.

**Autenticação**  
Não exige.

**Headers**  
`Accept: application/json`

**Path Params**  
Nenhum.

**Query Params**  
Nenhum.

**Request Body**  
Não aplicável.

**Exemplo de Requisição**
```bash
curl -X GET "https://{host}/{base}/api/v1/"
```

**Resposta de Sucesso**
- `200 OK`
```json
{
  "success": true,
  "message": "API is running",
  "data": {
    "app": "ConectaOSC3 API",
    "version": "v1",
    "php": "8.x",
    "timestamp": "2026-03-09T12:34:56-03:00"
  },
  "errors": []
}
```

**Respostas de Erro**  
`404` rota inválida, `500` erro interno.

**Regras de Negócio / Observações**  
Mesmo comportamento em `GET /api/v1/health`.

---

### [GET] /api/v1/health
Mesmo contrato de `GET /api/v1/`.

---

### [GET] /api/v1/me
**Descrição**  
Retorna usuário autenticado na sessão.

**Autenticação**  
Obrigatória (sessão).

**Headers**  
`Accept: application/json`, `Cookie`.

**Path Params**  
Nenhum.

**Query Params**  
Nenhum.

**Request Body**  
Não aplicável.

**Exemplo de Requisição**
```bash
curl -X GET "https://{host}/{base}/api/v1/me" -b "PHPSESSID3=..."
```

**Resposta de Sucesso**
- `200 OK`
```json
{
  "success": true,
  "message": "Authenticated user",
  "data": {
    "id": 12,
    "name": "Nome",
    "email": "email@dominio.com",
    "perfil": null,
    "nome_completo": "Nome Sobrenome",
    "profissional_saude": 1,
    "especialidade_id": 3
  },
  "errors": []
}
```

**Respostas de Erro**
- `401 Unauthorized` (sem login).

**Regras de Negócio / Observações**  
`nome_completo/profissional_saude/especialidade_id` são enriquecidos via `tbUser`.

---

### [GET] /api/v1/config
### [PUT] /api/v1/config
**Descrição**  
Lê e atualiza configuração principal (`tbConfig`).

**Autenticação**  
Obrigatória.

**Headers**  
`Accept`, `Cookie`, e para `PUT`: `Content-Type: application/json`.

**Path Params**  
Nenhum.

**Query Params**  
Nenhum.

**Request Body (PUT)**
Campos sanitizados:
- `NomeSistema`, `CorPrimaria`, `CorSecundaria`, `LogoSistema`, `LogoImpressao`, `ShortcutIcon`, `TituloPagina`, `MetaDescription`, `MetaAuthor`, `CargoDirigente`, `MetaKeywords`, `APIzap`, `HeaderEmail`, `TermosUso` (string)
- `IdColaboradorDirigente` (int|null)

Exemplo:
```json
{
  "NomeSistema": "ConectaOSC",
  "CorPrimaria": "#123456",
  "IdColaboradorDirigente": 10
}
```

**Exemplo de Requisição**
```bash
curl -X PUT "https://{host}/{base}/api/v1/config" \
  -H "Content-Type: application/json" \
  -b "PHPSESSID3=..." \
  -d "{\"NomeSistema\":\"ConectaOSC\"}"
```

**Resposta de Sucesso**
- `200 OK` com objeto completo de configuração.

**Respostas de Erro**
- `401` não autenticado
- `422` falha de atualização

**Regras de Negócio / Observações**  
Atualiza sempre o primeiro registro de `tbConfig`.

---

### [GET] /api/v1/beneficiarios/resumo-ativos
### [GET] /api/v1/beneficiarios/detalhado-ativos
### [GET] /api/v1/beneficiarios/busca
### [GET] /api/v1/beneficiarios/{id}
### [DELETE] /api/v1/beneficiarios/{id}
**Descrição**  
Consulta beneficiários ativos, busca paginada, detalhe e inativação (soft delete).

**Autenticação**  
Obrigatória.

**Headers**  
`Accept`, `Cookie`.

**Path Params**
- `id` (int) em `/{id}`.

**Query Params (`/busca`)**
- `search` string
- `page` int (default 1)
- `per_page` int (default 15, máx 100)
- `habilitado` int|null (default 1)

**Request Body**
Não aplicável (exceto `DELETE`, sem body obrigatório).

**Exemplo de Requisição**
```bash
curl -X GET "https://{host}/{base}/api/v1/beneficiarios/busca?search=maria&page=1&per_page=10" -b "PHPSESSID3=..."
```

**Resposta de Sucesso**
- `200` lista/detalhe
- `DELETE`: `{ "deleted": true|false }`

**Respostas de Erro**
- `404` no `GET /{id}` quando não encontrado
- `422` no `DELETE` em falhas de negócio

**Regras de Negócio / Observações**
- `DELETE` faz `UPDATE tbAluno SET Habilitado=0` (não remove fisicamente).
- `resumo-ativos` e `detalhado-ativos` incluem agregados de turmas/interesses.

---

### [GET] /api/v1/colaboradores-publicos/{id}/nome
### [GET] /api/v1/colaboradores/profissionais-saude
### [GET] /api/v1/colaboradores/{id}
**Descrição**  
Consulta colaboradores e profissionais de saúde.

**Autenticação**
- `colaboradores-publicos/{id}/nome`: não exige.
- Demais: exigem.

**Query Params (`/profissionais-saude`)**
- `search` string
- `especialidade_id` int
- `habilitado` int (default 1)

**Resposta de Sucesso**
- `200` com campos normalizados (`id`, `nome_completo`, etc).

**Respostas de Erro**
- `404` para IDs inexistentes
- `401` rotas privadas sem sessão

**Observações**
- Endpoint público retorna apenas `id` + `nome_completo`.

---

### [GET] /api/v1/cursos
### [GET] /api/v1/cursos/ativos
### [POST] /api/v1/cursos
### [PUT] /api/v1/cursos/{id}
### [DELETE] /api/v1/cursos/{id}
### [GET] /api/v1/projetos
**Descrição**  
CRUD de cursos e listagem de projetos.

**Autenticação**  
Obrigatória.

**Request Body (`POST/PUT /cursos`)**
Campos aceitos (variações de maiúsculas/minúsculas são suportadas):
- `NomeCurso`/`nomecurso` string
- `Duracao`/`duracao` decimal string
- `Tipo`/`tipo`
- `CargaHoraria`/`cargah`
- `Termo`
- `Projeto` ou `IdProjeto`
- `Programa`
- `Informacoes`/`informacoes`
- `Habilitado`/`habilitado` (`1`, `Ativo`, etc)
- `idade-min`/`IdadeMin`
- `idade-max`/`IdadeMax`

Exemplo:
```json
{
  "NomeCurso": "Informática Básica",
  "Duracao": "6",
  "Tipo": "Presencial",
  "CargaHoraria": "120",
  "Projeto": 2,
  "Habilitado": 1
}
```

**Resposta de Sucesso**
- `POST`: `201` com `{ "id": <novo_id> }`
- `PUT/DELETE`: `200` com `updated/deleted`.

**Respostas de Erro**
- `422` em falhas de validação/persistência
- `401` sem sessão

**Observações**
- `DELETE` remove fisicamente de `tbCurso`.

---

### [GET] /api/v1/turmas
### [GET] /api/v1/turmas/por-curso
### [POST] /api/v1/turmas
### [PUT] /api/v1/turmas/{id}
### [DELETE] /api/v1/turmas/{id}
**Descrição**  
CRUD de turmas e busca por curso.

**Autenticação**  
Obrigatória.

**Query Params (`/por-curso`)**
- `IdCurso` int obrigatório
- `somenteAtivos` int default `1`

**Request Body**
- `POST`: suporta múltiplas turmas via `NomeTurma` array.
- Campos: `NomeTurma`, `IdCurso`, `Municipio`, `Local`, `Obs`, `max-matriculas`/`MaxMatriculas`.
- `PUT`: mesma ideia com variantes (`nometurma`, `curso`, etc).

Exemplo POST:
```json
{
  "IdCurso": 3,
  "NomeTurma": ["Turma A", "Turma B"],
  "Municipio": "Fortaleza",
  "Local": "Unidade 1",
  "MaxMatriculas": 30
}
```

**Resposta de Sucesso**
- `POST`: `201` com `{ "count": 2 }`
- `PUT/DELETE`: `updated/deleted`.

**Respostas de Erro**
- `422` (`IdCurso` inválido etc)
- `401`

**Observações**
- `DELETE` é físico em `tbTurma`.

---

### [POST] /api/v1/matriculas/lote
### [GET] /api/v1/matriculas/por-turma
### [POST] /api/v1/matriculas/{id}/ativar
### [PUT] /api/v1/matriculas/{id}
### [DELETE] /api/v1/matriculas/{id}
**Descrição**  
Operações de matrícula, incluindo processamento em lote com validações de regra de negócio.

**Autenticação**  
Obrigatória.

**Query Params (`/por-turma`)**
- `turma` int obrigatório
- `ativo` int default `1`

**Request Body**
- `POST /lote`:
```json
{
  "codAluno": [101, 102],
  "CursoTurma": [
    { "IdCurso": 3, "IdTurma": 8 }
  ],
  "dataMatricula": "2026-03-09"
}
```
- `PUT /{id}`:
```json
{ "curso": 3, "idturma": 8, "datamatricula": "09/03/2026" }
```
- `DELETE /{id}` exige também `curso` e `idturma` no body para inativar.

**Resposta de Sucesso**
- `POST /lote`: `201` com `resultado.linhas` (status por aluno).
- `GET /por-turma`: lista de matrículas.
- `POST /ativar`: `{ "updated": true|false }`
- `DELETE`: `{ "deleted": true|false }`

**Respostas de Erro**
- `422` validações de lote/parâmetros
- `500` erro inesperado em lote
- `401`

**Observações**
- `DELETE` em matrícula é soft delete (`Habilitado=0`), não exclusão física.

---

### [POST] /api/v1/relatorios/cursos/options
### [POST] /api/v1/relatorios/turmas/options
### [GET] /api/v1/relatorios/projetos
### [POST] /api/v1/relatorios/custom-alunos
### [POST] /api/v1/relatorios/custom-alunos/html
### [POST] /api/v1/relatorios/presenca-curso-turma
### [POST] /api/v1/relatorios/presenca-curso-turma/html
### [POST] /api/v1/relatorios/matriculados
### [GET|POST] /api/v1/relatorios/frequencia/mensal
### [GET|POST] /api/v1/relatorios/frequencia/intervalo
**Descrição**  
Endpoints de relatórios em JSON e HTML.

**Autenticação**  
Obrigatória.

**Request Body / Query principais**
- `cursos/options`: `somenteAtivos`
- `turmas/options`: `IdCurso`, `somenteAtivos`, `todasTurmas`
- `custom-alunos`: `curso`, `turma`, `matriculasAtivas`, `filtroAtivos`, `interesses[]`, `colunas[]`
- `presenca-curso-turma`: `dataInicio`, `dataFim`, `turma[]`, `dias[]`
- `matriculados`: `curso`, `turma`
- `frequencia/mensal`: `curso`, `turma`, `mesAno` (`YYYY-MM`), `habilitado`
- `frequencia/intervalo`: `curso2`, `turma2`, `dataInicio`, `dataFim`, `habilitado2`, `turmaInterval`

**Exemplo**
```bash
curl -X POST "https://{host}/{base}/api/v1/relatorios/custom-alunos" \
  -b "PHPSESSID3=..." \
  -d "curso=3&turma=todas&colunas[]=Nome&colunas[]=CPF"
```

**Resposta de Sucesso**
- JSON para quase todos.
- HTML puro em:
1. `/custom-alunos/html`
2. `/presenca-curso-turma/html`

**Respostas de Erro**
- Predominantemente `200` com array vazio quando filtros inválidos.
- `401` sem sessão.

**Observações**
- Há comportamento “silencioso” em validação: vários cenários retornam `[]` em vez de erro HTTP.

---

### [GET] /api/v1/eventos/inscritos
### [GET] /api/v1/eventos/inscritos/{id}
### [GET] /api/v1/eventos/inscritos/email-exists
### [POST] /api/v1/eventos/inscritos
### [PUT] /api/v1/eventos/inscritos/{id}
### [DELETE] /api/v1/eventos/inscritos/{id}
**Descrição**  
CRUD de inscritos em evento (`InscritosEvento`) e verificação de e-mail.

**Autenticação**
- Públicas: `GET email-exists`, `POST inscritos`.
- Privadas: listar, detalhar, atualizar, excluir.

**Query Params (`email-exists`)**
- `email` string
- `ignoreId` int opcional

**Request Body (`POST/PUT`)**
```json
{
  "nomeCompleto": "Maria Silva",
  "email": "maria@dominio.com",
  "telefone": "85999999999",
  "tel2": "",
  "dataNascimento": "1990-01-01",
  "organizacaoSocial": "ONG X",
  "cargoFuncao": "Coordenadora",
  "enderecoOrganizacao": "Rua Y",
  "motivacaoEvento": "Interesse no tema",
  "necessidadesEspeciais": "",
  "confirmacaoParticipacao": 1
}
```

**Resposta de Sucesso**
- `POST`: `201` com `id`.
- `GET/{id}`: `200` quando existe, `404` quando não existe (com payload de sucesso=false? neste endpoint mantém envelope com `success:true` e status 404).

**Respostas de Erro**
- `422` e-mail duplicado e outras falhas
- `401` sem sessão nas rotas protegidas

**Observações**
- Divergência comportamental: `GET /{id}` devolve status `404` com envelope de sucesso padrão do controller.

---

## API Clínica (`/clinica/api`)

### [GET] /clinica/api/
**Descrição**  
Status da API clínica e presença de sessão.

**Autenticação**  
Não exige.

**Resposta**
```json
{
  "success": true,
  "data": { "ok": true, "session": true, "msg": "Sessao OK" }
}
```

---

### [GET] /clinica/api/pacientes
### [GET] /clinica/api/pacientes/{id}
**Descrição**  
Busca pacientes por texto e detalhe de paciente.

**Autenticação**  
Obrigatória.

**Query Params (`/pacientes`)**
- `search` string obrigatório para retornar lista
- `limit` int (1..20, default 4)

**Resposta de Sucesso**
- `/pacientes`: `data.pacientes[]`
- `/pacientes/{id}`: `data.paciente`

**Respostas de Erro**
- `400` ID inválido
- `404` paciente não encontrado
- `401` sem sessão

**Observações**
- Tenta primeiro consumir `api/v1/beneficiarios/*`; se falhar, cai para consulta direta ao banco.

---

### [GET] /clinica/api/especialidades
### [GET] /clinica/api/tipos-consulta
### [GET] /clinica/api/profissionais
**Descrição**  
Listas auxiliares do módulo clínica.

**Autenticação**  
Obrigatória.

**Resposta**
- `especialidades`: `data.especialidades[]`
- `tipos-consulta`: `data.tipos_consulta[]`
- `profissionais`: `data.profissionais[]` (`id`, `nome`)

**Observações**
- `/profissionais` tenta API principal e faz fallback para DB.

---

### [POST] /clinica/api/consultas
### [PUT] /clinica/api/consultas/{id}
### [POST] /clinica/api/consultas/{id}/confirmacao
### [POST] /clinica/api/consultas/{id}/cancelar
### [POST] /clinica/api/consultas/{id}/excluir
### [POST] /clinica/api/consultas/{id}/iniciar-atendimento
### [POST] /clinica/api/consultas/{id}/reverter-atendimento
**Descrição**  
Gestão de consultas e transição de status.

**Autenticação**
- `store/update/confirmacao/cancelar/excluir/reverter`: exige sessão.
- `iniciar-atendimento`: exige `profissional_saude=1`.

**Request Body (`POST /consultas`)**
```json
{
  "aluno_id": 123,
  "profissional_id": 45,
  "profissional_nome_livre": null,
  "especialidade_id": 2,
  "tipo_consulta_id": 1,
  "data_consulta": "2026-03-09",
  "hora_inicio_prevista": "14:00",
  "duracao_minutos_prevista": 60,
  "observacao": "Retorno"
}
```

**Request Body (`PUT /consultas/{id}`)**  
Campos parciais permitidos: profissional, data/hora, duração, observação, especialidade, tipo.

**Resposta de Sucesso**
- `POST`: `201` com `id` da consulta.
- `PUT`: `200` com `id`.
- Endpoints de status: `200` com `Status atualizado`.
- `excluir`: `200` com `id`.

**Respostas de Erro**
- `400` ID/data/hora inválidos
- `403` iniciar-atendimento sem perfil
- `404` consulta não encontrada
- `422` conflito com prontuário/anamnese vinculado ou validação
- `500` falha de transação

**Observações**
- Cria/atualiza também registro em `tb_agenda_clinica`.
- `excluir` remove fisicamente consulta e agenda se não houver vínculos.

---

### [GET] /clinica/api/agenda
### [GET] /clinica/api/agenda/em-atendimento
### [GET] /clinica/api/agenda/consultas-atendimento
### [GET] /clinica/api/agenda/consultas-aguardando-prontuario
### [GET] /clinica/api/agenda/dias-com-agendamento
**Descrição**  
Consultas para visualização de agenda e operação clínica.

**Autenticação**  
Obrigatória.

**Query Params principais**
- `/agenda`: `view=day|week|month`, `date=YYYY-MM-DD`, `especialidade_id`, `profissional_id`, `hora_inicio`, `hora_fim`
- `/consultas-atendimento`: `date=YYYY-MM-DD`
- `/dias-com-agendamento`: `mes=YYYY-MM`

**Resposta de Sucesso**
- `agenda`: `data.eventos[]`
- `em-atendimento`: `data.consultas[]`
- `consultas-atendimento`: `data.consultas[]`
- `consultas-aguardando-prontuario`: `data.consultas[]`
- `dias-com-agendamento`: `data.dias[]`

**Respostas de Erro**
- `400` data/mês inválidos
- `401` sem sessão

**Observações**
- Filtragem por permissão do usuário (superadmin/profissional/especialidade) é aplicada em consultas de atendimento.

---

### [GET] /clinica/api/feriados/verificar
**Descrição**  
Verifica se datas são feriado usando base local (`tb_feriados`) e BrasilAPI.

**Autenticação**  
Obrigatória.

**Query Params**
- `datas`: string CSV em `YYYY-MM-DD` (ex: `2026-01-01,2026-04-21`)

**Resposta de Sucesso**
```json
{
  "success": true,
  "data": {
    "feriados": {
      "2026-01-01": { "feriado": true, "nome": "Confraternização Universal", "fonte": "api" }
    }
  }
}
```

**Respostas de Erro**
- Geralmente `200` com objeto vazio se entrada inválida.
- `401` sem sessão.

---

### [GET] /clinica/api/prontuarios
### [GET] /clinica/api/prontuarios/pacientes
### [GET] /clinica/api/prontuarios/{id}
### [POST] /clinica/api/prontuarios
### [PUT] /clinica/api/prontuarios/{id}
### [DELETE] /clinica/api/prontuarios/{id}
### [POST] /clinica/api/prontuarios/gerar-ia
### [POST] /clinica/api/prontuarios/stream-ia
### [POST] /clinica/api/prontuarios/pdf
**Descrição**  
CRUD de prontuários, geração de conteúdo por IA (normal e streaming SSE) e emissão de PDF.

**Autenticação**
- Listar/detalhar/pdf: sessão.
- Criar e gerar IA: exige profissional de saúde.
- Atualizar/excluir: autor do prontuário ou superadmin.

**Query Params**
- `/prontuarios`: `aluno_id`, `paciente_nome`, `data`, `hora_inicio`, `hora_fim`, `profissional_id`
- `/prontuarios/pacientes`: `search`

**Request Body**
- `POST /prontuarios`:
```json
{
  "consulta_id": 10,
  "aluno_id": 123,
  "conteudo_ia": "<p>...</p>",
  "conteudo_editado": "<p>...</p>",
  "status": "rascunho"
}
```
- `PUT /prontuarios/{id}`:
```json
{
  "conteudo_editado": "<p>Atualizado</p>",
  "status": "finalizado"
}
```
- `POST /gerar-ia` e `/stream-ia`:
```json
{
  "consulta_id": 10,
  "aluno_id": 123,
  "observacoes_adicionais": "Paciente ansioso hoje."
}
```
- `POST /pdf`:
1. Individual: `id`, `assinar=0|1`
2. Lote: `ids[]`, `assinar=0|1`

**Resposta de Sucesso**
- CRUD: envelopes JSON padrão.
- `gerar-ia`: retorna `data.conteudo_ia` (HTML).
- `stream-ia`: `text/event-stream` com eventos `delta`, `done`, `error`.
- `pdf`: responde com HTML auto-submit (lote) ou redireciona para geração PDF.

**Respostas de Erro**
- `400` IDs/campos inválidos
- `403` sem permissão de edição/exclusão/lote
- `404` prontuário/paciente não encontrado
- `422` falta `aluno_id`
- `500` falha de IA/configuração

**Observações**
- Se `consulta_id` não for informado em `POST /prontuarios`, controller cria consulta “concluída” automática.
- O endpoint documentado internamente como `GET /prontuarios/{id}/pdf` não está registrado no router atual. O ativo é `POST /prontuarios/pdf`.
- Conteúdo de IA usa modelo `gpt-4o-mini` via chamada direta à API OpenAI.

---

### [GET] /clinica/api/anamnese
### [GET] /clinica/api/anamnese/{id}
### [POST] /clinica/api/anamnese
### [PUT] /clinica/api/anamnese/{id}
**Descrição**  
CRUD parcial de anamnese psicológica.

**Autenticação**
- GET: sessão.
- POST/PUT: profissional de saúde.
- PUT: autor ou superadmin.

**Query Params (`GET /anamnese`)**
- `consulta_id` int
- `aluno_id` int

**Request Body (`POST`)**
- Obrigatórios: `consulta_id`, `aluno_id`
- Demais: dezenas de campos de avaliação (`crenca_religiao`, `qualidade_sono`, `lin_fluencia`, `observacoes_gerais`, etc). Campos vazios viram `null`.

Exemplo:
```json
{
  "consulta_id": 10,
  "aluno_id": 123,
  "crenca_religiao": "Católica",
  "qualidade_sono": "Regular",
  "observacoes_gerais": "Boa adesão."
}
```

**Request Body (`PUT`)**
- Atualização parcial dos mesmos campos da tabela.

**Resposta de Sucesso**
- `POST`: `201` com `id`.
- `PUT`: `200` com mensagem.
- `GET`: listas/detalhe.

**Respostas de Erro**
- `400` ID inválido
- `403` sem permissão
- `404` anamnese não encontrada
- `422` obrigatórios ausentes
- `401` sem sessão

---

## Modelos de Dados

## Envelope padrão de resposta
```json
{
  "success": true,
  "message": "string",
  "data": {},
  "errors": []
}
```

## Usuário autenticado (`/api/v1/me`)
- `id` int
- `name` string|null
- `email` string|null
- `perfil` string|null
- `nome_completo` string
- `profissional_saude` int|null
- `especialidade_id` int|null

## Beneficiário (`tbAluno`, campos mais usados)
- `IdUsuario` int
- `Nome`, `Apelido`, `CPF`, `Nascimento`, `Telefone`, `WhatsApp`, `Email`
- Endereço: `CEP`, `Endereco`, `Numero`, `Complemento`, `Bairro`, `Cidade`, `UF`
- Situação: `Habilitado` int

## Curso (`tbCurso`)
- `IdCurso`, `NomeCurso`, `Duracao`, `Tipo`, `CargaHoraria`
- `Termo`, `IdProjeto`, `Programa`, `Informacoes`
- `Habilitado`, `IdadeMin`, `IdadeMax`

## Turma (`tbTurma`)
- `IdTurma`, `NomeTurma`, `IdCurso`
- `Municipio`, `Local`, `Obs`, `MaxMatriculas`, `Habilitado`

## Matrícula (`tbMatricula`)
- `IdMatricula`, `IdUsuario`, `IdCurso`, `IdTurma`
- `vData`, `Habilitado`, `DtMudaHabilitado`, `UserMudaHabilitado`

## Consulta clínica (`tb_consulta`)
- `id`, `aluno_id`, `profissional_id`, `profissional_nome_livre`
- `especialidade_id`, `tipo_consulta_id`
- `data_consulta`, `hora_inicio_prevista`, `hora_fim_prevista`, `duracao_minutos_prevista`
- `status`, `observacao`, `criado_por`

## Prontuário (`tb_prontuario`)
- `id`, `consulta_id`, `aluno_id`, `profissional_id`
- `conteudo_ia`, `conteudo_editado`
- `versao`, `status` (`rascunho|finalizado`)
- `created_at`

## Anamnese (`tb_anamnese_psi`)
- Chaves: `id`, `consulta_id`, `aluno_id`, `profissional_id`
- Campos clínicos textuais extensos (contexto psicossocial, cognição, risco, humor, observações)

---

## Fluxos de Uso Comuns

### 1. Validar sessão e perfil
1. Chamar `GET /api/v1/me`.
2. Usar `profissional_saude` e `especialidade_id` para decisões no front clínica.

### 2. Fluxo consulta clínica
1. `POST /clinica/api/consultas`.
2. `GET /clinica/api/agenda?view=day&date=YYYY-MM-DD`.
3. `POST /clinica/api/consultas/{id}/iniciar-atendimento`.
4. `POST /clinica/api/consultas/{id}/reverter-atendimento` (se necessário).

### 3. Fluxo anamnese + prontuário com IA
1. `POST /clinica/api/anamnese`.
2. `POST /clinica/api/prontuarios/gerar-ia` ou `/stream-ia`.
3. `POST /clinica/api/prontuarios`.
4. `POST /clinica/api/prontuarios/pdf` para exportação.

### 4. Fluxo matrícula em lote
1. `GET /api/v1/cursos/ativos`.
2. `GET /api/v1/turmas/por-curso?IdCurso={id}`.
3. `POST /api/v1/matriculas/lote`.
4. `GET /api/v1/matriculas/por-turma?turma={id}`.

---

## Códigos de Status Encontrados

| Status | Contexto encontrado |
|---|---|
| 200 | Sucesso padrão |
| 201 | Criação de recurso (`cursos`, `turmas`, `matriculas/lote`, `consultas`, `prontuarios`, `anamnese`, `eventos`) |
| 400 | Parâmetro inválido (IDs, datas, mês, payload sem campos úteis) |
| 401 | Sessão ausente/inválida |
| 403 | Permissão insuficiente (profissional/autoria/superadmin) |
| 404 | Rota inexistente ou recurso não encontrado |
| 422 | Erro de validação/regra de negócio |
| 429 | Rate limit na API clínica |
| 500 | Erro interno, falha transacional ou integração externa (IA) |

---

## Problemas, Lacunas e Inconsistências

1. Endpoint documentado internamente `GET /clinica/api/prontuarios/{id}/pdf` não está roteado; fluxo ativo é `POST /clinica/api/prontuarios/pdf`.
2. Múltiplos endpoints de `api/v1` aceitam somente `$_POST` em vez de JSON; integração precisa atenção ao `Content-Type`.
3. Em `api/v1`, algumas operações de “não encontrado” retornam `200` com flag (`deleted=false/updated=false`) e não `404`.
4. Rate limit da clínica usa estado em memória (`static array`), podendo não persistir entre requests/processos.
5. Há credenciais de banco com defaults hardcoded em código.  
Observação: comportamento inferido do código, validar em ambiente real.
6. Mensagens em alguns arquivos apresentam problema de encoding (acentuação quebrada).
7. Não há especificação OpenAPI/Swagger nem coleção Postman versionada no backend analisado.
8. Existem endpoints legados script-based em `api/get/*.php` e `api/legacy/*.php` fora do padrão REST do roteador atual.

---

## Sugestão de Estrutura OpenAPI

```yaml
openapi: 3.0.3
info:
  title: ConectaOSC APIs
  version: "1.0.0-doc"
servers:
  - url: https://{host}/{base}/api/v1
    variables:
      host: { default: example.com }
      base: { default: conectaosc }
  - url: https://{host}/{base}/clinica/api
paths:
  /me:
    get:
      summary: Usuário autenticado
      security: [{ sessionCookie: [] }]
      responses:
        "200": { description: OK }
        "401": { description: Não autenticado }

  /beneficiarios/busca:
    get:
      summary: Busca paginada de beneficiários
      parameters:
        - in: query
          name: search
          schema: { type: string }
        - in: query
          name: page
          schema: { type: integer, default: 1 }
        - in: query
          name: per_page
          schema: { type: integer, default: 15, maximum: 100 }

  /consultas:
    post:
      summary: Agendar consulta (API Clínica)
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [aluno_id, especialidade_id, tipo_consulta_id, data_consulta, hora_inicio_prevista]
      responses:
        "201": { description: Criado }
        "422": { description: Dados inválidos }

components:
  securitySchemes:
    sessionCookie:
      type: apiKey
      in: cookie
      name: PHPSESSID3
```

Observação: esboço inicial; faltam schemas completos por endpoint para geração final.
