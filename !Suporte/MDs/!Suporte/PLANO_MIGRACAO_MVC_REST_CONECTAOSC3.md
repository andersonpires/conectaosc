# Plano Tecnico de Migracao - ConectaOSC3

## Objetivo
Migrar o sistema atual para arquitetura separada:
- Front-end em `conectaosc3/` no padrao MVC sem framework.
- Back-end em `conectaosc3/back-end/` no padrao MVC REST API (PHP 8.1+), retornando JSON.
- Eliminar dependencia de `BASE_URL` e `BASE_PATH` para rotas e includes.
- Preservar layout, design, fluxos e CRUDs existentes.

## Status atual da execucao
- Sprint 1: concluida (infra de `back-end/`, roteador REST, middleware de sessao e `.htaccess` com `/api/v1`).
- Sprint 2: concluida (modulo Config via API).
- Sprint 3: concluida (CRUD Curso/Turma via API).
- Sprint 4: concluida (fluxos principais de Matricula via API, incluindo lote/ativacao/listagens auxiliares).
- Sprint 5: em andamento (Beneficiario e Relatorios base migrados para `back-end/src`, incluindo wrappers `get/*.php`, `get/getTurmasNome.php`, pacote de frequencia/exportacoes (`getFrequencia`, `getEXCEL`, `getPDF`), bloco SWOT/fichas customizadas e verificacao de exclusao (`getVerificaDelete`) sem SQL direto nos endpoints principais).
- Sprint 6: em andamento (Evento, Perfil, Permissao, Pagina e CRM/Atendimento migrados para servicos/repositorios em `back-end/src`; paginas e rotinas auxiliares de CRM/Atendimento (`crm.php`, `index.php`, `notificarUsuario.php`, `verificaTarefasCron.php`) sem SQL direto).
- Sprint 7: em andamento (limpeza gradual de legado de base path/url iniciada com `bootstrap/runtime.php` e aplicacao no fluxo de relatorio de presenca e telas de relatorios principais).
- Sprint 9: concluida para integracao do `clinica/` com base dinamica do projeto e consumo prioritario da API REST do `conectaosc3` (sem alterar estrutura interna do app).
- Pendencias principais: consolidacao final das rotas REST no front, limpeza final de legado (`BASE_URL`/`BASE_PATH`) e validacao completa anti-link quebrado.

## Premissas
- Nao alterar tecnologia atual de front (HTML5, CSS, JS, Tailwind e afins).
- Manter compatibilidade funcional durante migracao incremental.
- Evitar big-bang; migrar por modulos com validacao completa por onda.
- A pasta `clinica/` e um app independente com front/back ja organizados:
  - Nao alterar organizacao de pastas e arquivos de `clinica/`.
  - Nao refatorar arquitetura interna de `clinica/` nesta migracao.
  - Apenas analisar e ajustar chamadas de `clinica/` para `conectaosc3/` ao final do processo.

## Estrutura alvo
### Front (raiz)
- `public/index.php` (front controller)
- `app/Front/Controllers`
- `app/Front/Views`
- `app/Front/Routes/web.php`
- `app/Front/Core` (router, request, response, view renderer)
- `assets/` (mantido)
- `template/` (mantido e gradualmente organizado em views/parciais)

### Back-end (nova pasta)
- `back-end/public/index.php` (API front controller)
- `back-end/src/Core` (Router, Request, Response, ErrorHandler, Middleware)
- `back-end/src/Controllers`
- `back-end/src/Services`
- `back-end/src/Repositories`
- `back-end/src/Models`
- `back-end/src/Middlewares`
- `back-end/routes/api.php`
- `back-end/config` (app, db, env)
- `back-end/storage/logs`

## Convencoes de API
- Prefixo: `/api/v1`
- JSON padrao:
```json
{
  "success": true,
  "message": "ok",
  "data": {},
  "errors": []
}
```
- Metodos:
  - `GET` lista/detalhe
  - `POST` criacao/acoes
  - `PUT/PATCH` atualizacao
  - `DELETE` remocao

## Cronograma de execucao (8 sprints)

## Sprint 0 - Preparacao e baseline (1 a 2 dias)
1. Criar branch de migracao.
2. Inventariar paginas e endpoints usados em producao.
3. Congelar alteracoes funcionais paralelas.
4. Definir checklist de regressao funcional.
5. Registrar baseline:
   - URLs atuais.
   - CRUDs por modulo.
   - Principais relatorios/exportacoes.

Entregaveis:
- Documento de inventario.
- Matriz de risco e rollback.

## Sprint 1 - Fundacao da infraestrutura (2 a 3 dias)
1. Criar `back-end/` com bootstrap PHP 8.1+.
2. Implementar roteador REST e dispatcher.
3. Implementar tratamento global de erro JSON.
4. Implementar middleware de autenticacao/autorizacao.
5. Implementar conexao de banco desacoplada de sessao legado.
6. Criar `.htaccess` do back-end.
7. Criar front controller do front com `.htaccess` na raiz.

Arquivos novos previstos:
- `back-end/public/index.php`
- `back-end/routes/api.php`
- `back-end/src/Core/*`
- `back-end/src/Middlewares/*`
- `.htaccess` (raiz)
- `public/index.php` (raiz)

Entregaveis:
- Rotas `GET /api/v1/health` e `GET /api/v1/me` funcionando.

## Sprint 2 - Piloto modulo Config (2 dias)
Escopo de origem:
- `app/config/formConfig.php`
- `app/config/salvarConfig.php`

1. Criar API:
   - `GET /api/v1/config`
   - `PUT /api/v1/config`
2. Criar repositorio e service de configuracoes.
3. Adaptar front da tela de configuracao para consumir API (AJAX/fetch).
4. Trocar submit direto para `.php` por rota front e chamada API.
5. Validar mensagens de sucesso/erro mantendo UI atual.

Entregaveis:
- Modulo Config 100% via API.
- Sem dependencia direta de `BASE_URL`/`BASE_PATH` no fluxo novo.

## Sprint 3 - Modulos Curso e Turma (3 dias)
Escopo de origem:
- `app/curso/*`
- `app/turma/*`
- endpoints auxiliares em `get/getCursos.php`, `get/getTurmas.php`, `get/getTurmasNome.php`

1. Criar endpoints REST de curso/turma (CRUD + listagens auxiliares).
2. Migrar forms/listagens para chamadas API.
3. Substituir `action="*.php"` por rotas front.
4. Revisar exclusao/edicao e validacao de dependencia entre curso-turma.

Entregaveis:
- CRUD de curso/turma completo via API.

## Sprint 4 - Modulo Matricula (3 a 4 dias)
Escopo de origem:
- `app/matricula/*`
- `get/getMatricula.php`
- `get/getMatriculaPDF.php`
- `get/getPDF_ficha_cadastro.php`

1. Criar API de matriculas (lista, cria, altera, exclui, detalhes).
2. Extrair logica de geracao de contrato/PDF para service dedicado.
3. Manter downloads funcionando via endpoint de arquivo controlado.
4. Atualizar listagens/filtros/paginacao no front.

Entregaveis:
- Fluxo completo de matricula e documentos sem regressao visual.

## Sprint 5 - Modulos Beneficiario e Relatorios (4 dias)
Escopo de origem:
- `app/beneficiario/*`
- `app/relatorio/*`
- `get/getEXCEL.php`, `get/getEXCELInterval.php`, `get/getPDF*.php`, `get/getCustomAluno*.php`

1. Criar endpoints para relatorios JSON e HTML quando necessario.
2. Padronizar exportacoes (PDF/Excel) em servicos de back-end.
3. Manter mesmas opcoes de filtro/periodo/campos.
4. Validar performance de consultas pesadas.

Entregaveis:
- Relatorios preservados com mesmo comportamento funcional.

## Sprint 6 - Modulos CRM/Atendimento/Evento/Permissao/Perfil (4 dias)
Escopo de origem:
- `app/crm/*`
- `app/atendimento/*`
- `app/evento/*`
- `app/permissao/*`
- `app/perfil/*`

1. Migrar endpoints e regras de permissao por perfil.
2. Padronizar respostas de notificacoes e operacoes assicronas.
3. Revisar fluxo de upload (notas/imagens/anexos).
4. Validar redirecionamentos autenticados.

Entregaveis:
- Modulos administrativos principais migrados.

## Sprint 7 - Limpeza de legado e hardening (2 a 3 dias)
1. Encerrar uso de `config.php` legado para `BASE_URL/BASE_PATH`.
2. Remover includes acoplados a `$_SESSION['BASE_PATH']` nos fluxos migrados.
3. Adicionar redirects 301/302 temporarios para rotas antigas `.php`.
4. Revisar logs de erro e warnings.
5. Revisar headers de seguranca e CORS da API.

Entregaveis:
- Sistema operando por rotas novas com camada de compatibilidade minima.

## Sprint 8 - Homologacao final e go-live (2 dias)
1. Executar checklist final de regressao.
2. Rodar varredura de links quebrados.
3. Validar CRUD por modulo com usuarios reais de teste.
4. Executar plano de rollback documentado.
5. Publicar e monitorar 48h.

Entregaveis:
- Go-live com monitoramento e plano de suporte pos-implantacao.

## Sprint 9 - Integracao final com app Clinica (ultima etapa) (1 a 2 dias)
Escopo restrito:
- Somente pontos em que `clinica/` chama rotas, endpoints ou paginas do `conectaosc3/`.
- Sem reorganizar pastas/arquivos do app `clinica/`.

1. Mapear chamadas atuais de `clinica/` para o sistema principal.
2. Identificar rotas legadas antigas (ex.: `*.php` diretos) que ainda sejam consumidas.
3. Redirecionar/atualizar chamadas para o novo padrao:
   - front por rotas amigaveis
   - dados por `/api/v1/...`
4. Validar sessao/autenticacao entre apps sem regressao.
5. Executar smoke test especifico do `clinica/` apos ajustes.

Entregaveis:
- `clinica/` integrado ao novo padrao do `conectaosc3/` sem alteracao estrutural interna.

### Endpoints minimos para concluir a migracao do `clinica/`
Status:
- Implementados no `conectaosc3/api` e consumidos pelo runtime do `clinica/`.

1. Ajustar `GET /api/v1/me`
- Status atual:
  - Implementado.
- Campos minimos a acrescentar:
  - `nome_completo`
  - `profissional_saude`
  - `especialidade_id`
- Impacto no `clinica/`:
  - Permite reduzir dependencia local no bootstrap de sessao de `clinica/index.php` e `clinica/api/index.php`.

Exemplo de retorno:
```json
{
  "success": true,
  "message": "Authenticated user",
  "data": {
    "id": 12,
    "name": "Sara",
    "email": "sara@iteva.org.br",
    "perfil": "Administrador",
    "nome_completo": "Sara Belem Beneduce",
    "profissional_saude": 1,
    "especialidade_id": 3
  },
  "errors": []
}
```

2. Criar `GET /api/v1/colaboradores/profissionais-saude`
- Status atual:
  - Implementado.
- Finalidade:
  - Substituir leitura direta de `tbUser` filtrada por `profissional_saude = 1`.
- Query params opcionais:
  - `search`
  - `especialidade_id`
  - `habilitado`
- Campos minimos de retorno:
  - `id`
  - `nome`
  - `sobrenome`
  - `nome_completo`
  - `especialidade_id`
  - `profissional_saude`
  - `habilitado`
- Impacto no `clinica/`:
  - Destrava `clinica/api/app/Controllers/ProfissionaisController.php`
  - Ajuda agenda, anamnese e prontuario a evitar join local com `tbUser` quando so precisarem do nome do profissional.

Exemplo de retorno:
```json
{
  "success": true,
  "message": "Profissionais carregados",
  "data": [
    {
      "id": 7,
      "nome": "Anderson",
      "sobrenome": "Pires",
      "nome_completo": "Anderson Pires",
      "especialidade_id": 2,
      "profissional_saude": 1,
      "habilitado": 1
    }
  ],
  "errors": []
}
```

3. Criar `GET /api/v1/beneficiarios/{id}`
- Status atual:
  - Implementado.
- Finalidade:
  - Evitar consumo de `beneficiarios/detalhado-ativos` inteiro quando o `clinica/` precisa de um unico beneficiario.
- Campos minimos:
  - `IdUsuario`
  - `Nome`
  - `Apelido`
  - `SexoBio`
  - `Nascimento`
  - `CPF`
  - `Identidade`
  - `NomeResp1`
  - `Parentesco`
  - `CpfResp1`
  - `TelefoneResp1`
  - `WhatsAppResp1`
  - `CEP`
  - `Endereco`
  - `Numero`
  - `Complemento`
  - `Bairro`
  - `Cidade`
  - `UF`
  - `Telefone`
  - `WhatsApp`
  - `Email`
  - `Habilitado`
- Impacto no `clinica/`:
  - Simplifica `PacientesController`
  - Simplifica `ProntuariosController` na geracao de contexto do aluno
  - Reduz payload e custo de rede.

4. Criar `GET /api/v1/beneficiarios/busca`
- Status atual:
  - Implementado.
- Finalidade:
  - Entregar listagem de beneficiarios/pacientes com busca e paginacao.
- Query params:
  - `search`
  - `page`
  - `per_page`
  - `habilitado`
- Estrutura sugerida:
```json
{
  "success": true,
  "message": "Beneficiarios carregados",
  "data": {
    "items": [],
    "meta": {
      "page": 1,
      "per_page": 15,
      "total": 120
    }
  },
  "errors": []
}
```
- Impacto no `clinica/`:
  - Substitui com mais eficiencia a lista completa de `detalhado-ativos` para a tela de pacientes.

5. Opcional util: `GET /api/v1/colaboradores/{id}`
- Status atual:
  - Implementado.
- Finalidade:
  - Lookup unitario de colaborador.
- Impacto:
  - Ajuda pontos do `clinica/` que precisam apenas resolver nome de profissional por `IdColaborador`.

6. Endpoint publico minimo adicional
- `GET /api/v1/colaboradores-publicos/{id}/nome`
- Finalidade:
  - Suportar validacao publica do prontuario da `clinica/` sem sessao autenticada.
- Campos expostos:
  - `id`
  - `nome_completo`
- Status atual:
  - Implementado.

### Ganho esperado por endpoint
1. `GET /api/v1/me`
- Remove parte do acoplamento de sessao/perfil do `clinica/`.

2. `GET /api/v1/colaboradores/profissionais-saude`
- Resolve o principal bloqueio restante: dependencia de `tbUser` para profissionais de saude.

3. `GET /api/v1/beneficiarios/{id}`
- Troca lookup SQL local por REST de baixo custo.

4. `GET /api/v1/beneficiarios/busca`
- Torna a tela de pacientes do `clinica/` mais eficiente.

### Conclusao pratica
O conjunto minimo foi implementado e ja esta em uso pelo runtime do `clinica/`:
1. `GET /api/v1/me`
2. `GET /api/v1/colaboradores/profissionais-saude`
3. `GET /api/v1/beneficiarios/{id}`
4. `GET /api/v1/beneficiarios/busca`
5. `GET /api/v1/colaboradores/{id}`
6. `GET /api/v1/colaboradores-publicos/{id}/nome`

## Mapeamento inicial de dominios para API
1. `config` -> `/api/v1/config`
2. `curso` -> `/api/v1/cursos`
3. `turma` -> `/api/v1/turmas`
4. `matricula` -> `/api/v1/matriculas`
5. `beneficiario` -> `/api/v1/beneficiarios`
6. `relatorio` -> `/api/v1/relatorios/*`
7. `evento` -> `/api/v1/eventos` e `/api/v1/inscricoes`
8. `perfil` -> `/api/v1/perfil`
9. `permissao` -> `/api/v1/permissoes` e `/api/v1/paginas`
10. `crm/atendimento` -> `/api/v1/crm/*` e `/api/v1/atendimento/*`

## Backlog tecnico por tipo de tarefa
1. Infra:
   - Autoload PSR-4.
   - Config por ambiente.
   - Logger.
2. API:
   - Validadores por request.
   - Middlewares auth/permissao/rate-limit.
   - Documentacao de rotas.
3. Front:
   - Helper central de chamada API.
   - Tratamento padrao de erros e loading.
   - Router front sem `.php`.
4. Dados:
   - Repositorios por contexto.
   - Refatoracao de SQL espalhado em controllers legados.
5. Qualidade:
   - Smoke tests por modulo.
   - Testes de regressao de CRUD.

## Checklist de revisao por modulo (obrigatorio)
1. Lista abre e pagina corretamente.
2. Cadastro cria registro com validacoes corretas.
3. Edicao persiste alteracoes sem quebrar tela.
4. Exclusao respeita regras de dependencia.
5. Filtros e buscas retornam mesmos resultados esperados.
6. Mensagens de sucesso/erro aparecem corretamente.
7. Permissoes de acesso estao consistentes por perfil.
8. Downloads (PDF/Excel/arquivos) funcionam.
9. Sem erro JS no console.
10. Sem warning/erro PHP no log do modulo.

## Checklist global anti-link quebrado
1. Varredura por referencias diretas a `.php` em `href`, `action`, JS.
2. Varredura por uso de `BASE_URL` e `BASE_PATH`.
3. Teste de navegacao com usuario autenticado e nao autenticado.
4. Teste de menu completo e breadcrumbs.
5. Conferencia de 404 customizada.
6. Conferencia de redirect apos login/logout.
7. Conferencia de upload e assets estaticos.

Comandos uteis de auditoria:
```powershell
rg -n --hidden -S "BASE_URL|BASE_PATH" -g "*.php"
rg -n --hidden -S "\\.php" app template assets *.php
rg -n --hidden -S "fetch\\(|\\$\\.post\\(|XMLHttpRequest\\(" app index.php login.php
```

## Critérios de aceite finais
1. Front opera por rotas amigaveis sem dependencia de base path em sessao.
2. Back-end responde em JSON padrao para operacoes de dados.
3. Nenhuma regressao funcional em CRUDs existentes.
4. Layout e UX permanecem equivalentes ao sistema atual.
5. Sem links quebrados nas telas principais.
6. Logs estaveis (sem aumento relevante de 4xx/5xx).

## Ordem de execucao recomendada (prioridade)
1. Config.
2. Curso/Turma.
3. Matricula.
4. Beneficiario.
5. Relatorios.
6. CRM/Atendimento.
7. Evento/Permissao/Perfil.
8. Integracao final do `clinica/` (somente redirecionamento de chamadas para novo padrao).

## Observacoes de risco
- O projeto possui historico de backups e arquivos antigos em `app/`; limitar migracao ao codigo realmente em uso.
- Existe exemplo funcional de router/API em `clinica/api` que pode ser reutilizado como referencia tecnica.
- A retirada de `BASE_URL`/`BASE_PATH` deve ocorrer por ondas para evitar quebra ampla.
- Ajustes em `clinica/` devem ocorrer somente no fim e de forma minima, para nao introduzir regressao em app ja estavel.
