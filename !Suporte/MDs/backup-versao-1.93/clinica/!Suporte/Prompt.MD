# Prompt.MD — Geração Completa do App Clínica (ConectaOSC)

## Objetivo
Desenvolver o **App Clínica** integrado ao ConectaOSC, com:
- Back-end em **PHP 8.1+**, arquitetura **MVC sem framework**
- **API REST JSON**
- Front-end em **HTML5, CSS, JS, Tailwind, TypeScript e Bootstrap**
- Layout **mobile-first** com experiência visual estilo app SaaS
- Compatível com:
  - Produção: `https://iteva.com.br/conectaosc/`
  - Desenvolvimento: `http://localhost/conectaosc`

---

## 1) Contexto obrigatório de execução

O módulo será executado dentro de `/conectaosc`.

### Estrutura correta (obrigatória)
- **Front-end:** `/conectaosc/clinica/`
- **Back-end/API:** `/conectaosc/clinica/api/`

> **Regra crítica:** NÃO usar `api/clinica`. O padrão correto é **`clinica/api`**.

---

## 2) Regras obrigatórias de ambiente (auto-detecção)

Implementar detecção automática de ambiente em classe central (`App\Core\Env` ou equivalente), usando `$_SERVER`:

- Se host contiver `localhost` ou `127.0.0.1` -> `development`
- Caso contrário -> `production`

### Configurações automáticas obrigatórias
- `APP_ENV` = `development|production`
- `APP_BASE_PATH` = `/conectaosc`
- `CLINICA_BASE_PATH` = `/conectaosc/clinica`
- `APP_BASE_URL`:
  - dev: `http://localhost/conectaosc`
  - prod: `https://iteva.com.br/conectaosc`
- `FRONT_BASE_URL` = `{APP_BASE_URL}/clinica`
- `API_BASE_URL` = `{APP_BASE_URL}/clinica/api`

### Sessão/cookies e segurança
- `session.cookie_httponly = true`
- `session.cookie_secure = true` em produção / false em localhost
- `session.cookie_samesite = Lax` (Strict quando viável)
- `session.gc_maxlifetime` conforme política do sistema principal
- timezone padrão: `America/Sao_Paulo`
- CORS restrito ao próprio domínio/origem
- Tratamento de erro com JSON padronizado (sem stack trace em produção)

---

## 3) Stack e padrões técnicos

### Back-end
- PHP 8.1+
- MVC sem framework
- API REST (JSON)
- PDO + MySQL
- Prepared statements em 100% das consultas com input externo
- Código organizado por camadas: Core, Controllers, Services, Repositories/Models, Middlewares

### Front-end
- HTML5 + CSS + JS + TypeScript
- Tailwind CSS + Bootstrap
- Lucide Icons (preferencial)
- UI responsiva mobile-first
- Acessibilidade WCAG AA mínimo

---

## 4) Banco de dados (conexão obrigatória)

Usar fallback automático caso `$_ENV` não exista:

```php
if (!isset($_ENV['DB_HOST'])) $_ENV['DB_HOST'] = '162.241.2.214';
if (!isset($_ENV['DB_NAME'])) $_ENV['DB_NAME'] = 'mwtech63_matricula';
if (!isset($_ENV['DB_USER'])) $_ENV['DB_USER'] = 'mwtech63_admin_matricula';
if (!isset($_ENV['DB_PASS'])) $_ENV['DB_PASS'] = 'Iteva@100';
if (!isset($_ENV['DB_PORT'])) $_ENV['DB_PORT'] = '3306';

Regras obrigatórias de PDO:

charset=utf8mb4

PDO::ATTR_ERRMODE = PDO::ERRMODE_EXCEPTION

PDO::ATTR_DEFAULT_FETCH_MODE = PDO::FETCH_ASSOC

nunca usar mysqli em código novo

nunca exibir credenciais em logs/respostas

5) Estrutura de pastas obrigatória
/conectaosc/
  clinica/
    index.html (ou entry front)
    assets/
    src/
      pages/
      components/
      services/
      types/
      utils/
    api/
      index.php
      app/
        Core/
        Controllers/
        Models/
        Services/
        Middlewares/
        Helpers/
      config/
      routes/
      storage/logs/

6) Modelo de dados (criar migrations SQL)
6.1 Alteração em tabela existente
tbUser

Adicionar:

profissional_saude TINYINT(1) NOT NULL DEFAULT 0

índice para profissional_saude

Também adaptar formColaborador:

checkbox: Profissional de saúde

persistência em tbUser.profissional_saude

6.2 Tabelas novas obrigatórias
tb_especialidade

id PK AI

nome varchar(100) UNIQUE

ativo tinyint(1) default 1

created_at, updated_at

Seeds iniciais:

Fisioretapia

Psicologia

Nutrição

Fonaudiologia

tb_tipo_consulta

id PK AI

nome varchar(100) UNIQUE

ativo tinyint(1) default 1

created_at, updated_at

Seeds:

Consulta simples

Consulta on-line

Anamnese

Retorno

IPAI

tb_consulta

Campos mínimos:

id PK AI

aluno_id FK tbAluno

profissional_id FK tbUser nullable (quando plantonista)

profissional_nome_livre varchar(120) nullable (para “Plantonista”)

especialidade_id FK tb_especialidade

tipo_consulta_id FK tb_tipo_consulta

data_consulta date

hora_inicio_prevista time

duracao_minutos_prevista int

hora_fim_prevista time

hora_fim_real time nullable

status enum('agendada','confirmacao_solicitada','cancelada','em_atendimento','concluida')

observacao text nullable

criado_por FK tbUser

created_at, updated_at

tb_agenda_clinica

id PK AI

consulta_id FK tb_consulta UNIQUE

data_agenda date

inicio datetime

fim_previsto datetime

status varchar(30)

created_at, updated_at

tb_prontuario

id PK AI

consulta_id FK tb_consulta

aluno_id FK tbAluno

profissional_id FK tbUser

conteudo_ia longtext

conteudo_editado longtext

versao int default 1

status enum('rascunho','finalizado')

created_at, updated_at

Criar índices por:

paciente, profissional, data, status

integridade referencial consistente (RESTRICT/SET NULL conforme contexto)

7) Regras funcionais obrigatórias
7.1 Pacientes

Listar pacientes ativos da tbAluno

Busca rápida por nome/contato/documento

Ao selecionar paciente, abrir menu rápido para Agendar consulta

Agendar consulta (campos)

data

hora

duração prevista

tipo de consulta (tb_tipo_consulta)

término previsto calculado automaticamente

término real (posterior, editável)

contato do paciente (tbAluno)

especialidade (tb_especialidade)

profissional:

opção fixa “Plantonista”

lista de tbUser com profissional_saude = 1

botão “Agendar consulta”

Persistência em:

tb_consulta

tb_agenda_clinica

7.2 Agenda

Visual estilo Google Calendar

Grade diária: 07:00 às 22:00

Linhas a cada 30 minutos

Topo com mês e ano atuais

Navegação:

botão Hoje (ativo por padrão)

< voltar dia

> avançar dia

Alternância de visão: Dia (padrão), Semana, Mês

Blocos de consulta na grade com nome do paciente

Clique no bloco abre modal com dados completos consulta + paciente

Ações do modal

Editar agendamento

Solicitar confirmação

Cancelar agendamento

Iniciar atendimento (somente se usuário logado for profissional de saúde)

7.3 Prontuário

Tabela em ordem decrescente por data/hora:

Data e hora

Profissional de saúde emissor

Ações

Ações:

Editar

Imprimir PDF (TCPDF em /conectaosc/lib, com logoimpressao e padrão visual da plataforma)

Excluir

Permissões de editar/excluir:

Superadministrador OU

o próprio profissional emissor

Sem permissão:

exibir prontuário em readonly

ocultar botão “salvar alterações”

7.4 Atendimento

Formulário clínico para profissional

Parte dos dados vem de tbAluno e da consulta

Enviar dados para IA (fluxo similar a avaliarVulnerabilidade.php)

Criar processo/endpoint criarProntuario.php

Utilizar /temp/openaikey.php

Resposta da IA em textarea editável

Profissional revisa e só depois salva

Persistir em tb_prontuario (conteudo_ia + conteudo_editado)

8) API REST (obrigatória)

Formato padrão:
{
  "success": true,
  "message": "Operação realizada com sucesso",
  "data": {},
  "errors": []
}

Endpoints mínimos:

GET /clinica/api/pacientes?search=...

GET /clinica/api/pacientes/{id}

POST /clinica/api/consultas

PUT /clinica/api/consultas/{id}

POST /clinica/api/consultas/{id}/confirmacao

POST /clinica/api/consultas/{id}/cancelar

POST /clinica/api/consultas/{id}/iniciar-atendimento

GET /clinica/api/agenda?view=day|week|month&date=YYYY-MM-DD

GET /clinica/api/especialidades

GET /clinica/api/tipos-consulta

GET /clinica/api/profissionais

GET /clinica/api/prontuarios?aluno_id=...

POST /clinica/api/prontuarios/gerar-ia

POST /clinica/api/prontuarios

PUT /clinica/api/prontuarios/{id}

DELETE /clinica/api/prontuarios/{id}

GET /clinica/api/prontuarios/{id}/pdf

Aplicar:

autenticação e autorização

validação de payload

status HTTP correto

rate limit básico em endpoints sensíveis

9) UI/UX obrigatória (Tailwind + Bootstrap)
9.1 Layout

Mobile-first obrigatório

Aparência de app mesmo no desktop

Menu inferior fixo com ícones:

Pacientes

Agenda

Prontuário

Atendimento

9.2 Design Tokens

Usar rigorosamente os tokens definidos:

paleta de cores primárias/secundárias/neutras/semânticas

tipografia Figtree/Roboto Mono

escala de spacing 4px

radius, shadows, breakpoints, animações, transições

componentes: botões, inputs, cards, tabelas, badges, modais etc.

9.3 Tailwind config

Gerar tailwind.config.js completo com:

cores customizadas (monday-blue, purple-accent, etc.)

spacing custom

borderRadius custom

boxShadow custom (incluindo shadow-monday)

fontes e tamanhos

9.4 Acessibilidade

foco visível

contraste WCAG AA

teclado navegável

área mínima clicável 44x44

10) Segurança, logs e qualidade

Prepared statements obrigatórios

sanitização e validação de todas entradas

prevenção de XSS/CSRF

não expor segredos/tokens

logs técnicos sem dados sensíveis

respostas de erro padronizadas

em produção: sem stack trace para usuário

11) Entregáveis esperados

Estrutura de pastas completa em /conectaosc/clinica e /conectaosc/clinica/api

Núcleo MVC + Router + Middlewares

Migrações SQL + seeds iniciais

Endpoints REST implementados

Front mobile-first completo (4 módulos)

Integração IA para prontuário (gerar, editar, salvar)

PDF via TCPDF padronizado

Guia de instalação/deploy por ambiente (dev/prod)

12) Critério final de aceitação

A solução só é aceita se:

funcionar corretamente em localhost/conectaosc e iteva.com.br/conectaosc

usar padrão de pastas /conectaosc/clinica/api/

manter front em /conectaosc/clinica/

respeitar integralmente as regras de negócio, permissões e design system

não quebrar compatibilidade com o ConectaOSC existente




