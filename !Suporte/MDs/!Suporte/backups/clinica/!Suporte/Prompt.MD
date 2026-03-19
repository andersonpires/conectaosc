# Prompt.MD  Gera��o Completa do App Cl�nica (ConectaOSC)

## Objetivo
Desenvolver o **App Cl�nica** integrado ao ConectaOSC, com:
- Back-end em **PHP 8.1+**, arquitetura **MVC sem framework**
- **API REST JSON**
- Front-end em **HTML5, CSS, JS, Tailwind, TypeScript e Bootstrap**
- Layout **mobile-first** com experi�ncia visual estilo app SaaS
- Compat�vel com:
  - Produ��o: `https://iteva.com.br/conectaosc/`
  - Desenvolvimento: `http://localhost/conectaosc`

---

## 1) Contexto obrigat�rio de execu��o

O m�dulo ser� executado dentro de `/conectaosc`.

### Estrutura correta (obrigat�ria)
- **Front-end:** `/conectaosc/clinica/`
- **Back-end/API:** `/conectaosc/clinica/api/`

> **Regra cr�tica:** N�O usar `api/clinica`. O padr�o correto � **`clinica/api`**.

---

## 2) Regras obrigat�rias de ambiente (auto-detec��o)

Implementar detec��o autom�tica de ambiente em classe central (`App\Core\Env` ou equivalente), usando `$_SERVER`:

- Se host contiver `localhost` ou `127.0.0.1` -> `development`
- Caso contr�rio -> `production`

### Configura��es autom�ticas obrigat�rias
- `APP_ENV` = `development|production`
- `APP_BASE_PATH` = `/conectaosc`
- `CLINICA_BASE_PATH` = `/conectaosc/clinica`
- `APP_BASE_URL`:
  - dev: `http://localhost/conectaosc`
  - prod: `https://iteva.com.br/conectaosc`
- `FRONT_BASE_URL` = `{APP_BASE_URL}/clinica`
- `API_BASE_URL` = `{APP_BASE_URL}/clinica/api`

### Sess�o/cookies e seguran�a
- `session.cookie_httponly = true`
- `session.cookie_secure = true` em produ��o / false em localhost
- `session.cookie_samesite = Lax` (Strict quando vi�vel)
- `session.gc_maxlifetime` conforme pol�tica do sistema principal
- timezone padr�o: `America/Sao_Paulo`
- CORS restrito ao pr�prio dom�nio/origem
- Tratamento de erro com JSON padronizado (sem stack trace em produ��o)

---

## 3) Stack e padr�es t�cnicos

### Back-end
- PHP 8.1+
- MVC sem framework
- API REST (JSON)
- PDO + MySQL
- Prepared statements em 100% das consultas com input externo
- C�digo organizado por camadas: Core, Controllers, Services, Repositories/Models, Middlewares

### Front-end
- HTML5 + CSS + JS + TypeScript
- Tailwind CSS + Bootstrap
- Lucide Icons (preferencial)
- UI responsiva mobile-first
- Acessibilidade WCAG AA m�nimo

---

## 4) Banco de dados (conex�o obrigat�ria)

Usar fallback autom�tico caso `$_ENV` n�o exista:

```php
if (!isset($_ENV['DB_HOST'])) $_ENV['DB_HOST'] = '162.241.2.214';
if (!isset($_ENV['DB_NAME'])) $_ENV['DB_NAME'] = 'mwtech63_matricula';
if (!isset($_ENV['DB_USER'])) $_ENV['DB_USER'] = 'mwtech63_admin_matricula';
if (!isset($_ENV['DB_PASS'])) $_ENV['DB_PASS'] = 'Iteva@100';
if (!isset($_ENV['DB_PORT'])) $_ENV['DB_PORT'] = '3306';

Regras obrigat�rias de PDO:

charset=utf8mb4

PDO::ATTR_ERRMODE = PDO::ERRMODE_EXCEPTION

PDO::ATTR_DEFAULT_FETCH_MODE = PDO::FETCH_ASSOC

nunca usar mysqli em c�digo novo

nunca exibir credenciais em logs/respostas

5) Estrutura de pastas obrigat�ria
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
6.1 Altera��o em tabela existente
tbUser

Adicionar:

profissional_saude TINYINT(1) NOT NULL DEFAULT 0

�ndice para profissional_saude

Tamb�m adaptar formColaborador:

checkbox: Profissional de sa�de

persist�ncia em tbUser.profissional_saude

6.2 Tabelas novas obrigat�rias
tb_especialidade

id PK AI

nome varchar(100) UNIQUE

ativo tinyint(1) default 1

created_at, updated_at

Seeds iniciais:

Fisioretapia

Psicologia

Nutri��o

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

Campos m�nimos:

id PK AI

aluno_id FK tbAluno

profissional_id FK tbUser nullable (quando plantonista)

profissional_nome_livre varchar(120) nullable (para Plantonista)

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

Criar �ndices por:

paciente, profissional, data, status

integridade referencial consistente (RESTRICT/SET NULL conforme contexto)

7) Regras funcionais obrigat�rias
7.1 Pacientes

Listar pacientes ativos da tbAluno

Busca r�pida por nome/contato/documento

Ao selecionar paciente, abrir menu r�pido para Agendar consulta

Agendar consulta (campos)

data

hora

dura��o prevista

tipo de consulta (tb_tipo_consulta)

t�rmino previsto calculado automaticamente

t�rmino real (posterior, edit�vel)

contato do paciente (tbAluno)

especialidade (tb_especialidade)

profissional:

op��o fixa Plantonista

lista de tbUser com profissional_saude = 1

bot�o Agendar consulta

Persist�ncia em:

tb_consulta

tb_agenda_clinica

7.2 Agenda

Visual estilo Google Calendar

Grade di�ria: 07:00 �s 22:00

Linhas a cada 30 minutos

Topo com m�s e ano atuais

Navega��o:

bot�o Hoje (ativo por padr�o)

< voltar dia

> avan�ar dia

Altern�ncia de vis�o: Dia (padr�o), Semana, M�s

Blocos de consulta na grade com nome do paciente

Clique no bloco abre modal com dados completos consulta + paciente

A��es do modal

Editar agendamento

Solicitar confirma��o

Cancelar agendamento

Iniciar atendimento (somente se usu�rio logado for profissional de sa�de)

7.3 Prontu�rio

Tabela em ordem decrescente por data/hora:

Data e hora

Profissional de sa�de emissor

A��es

A��es:

Editar

Imprimir PDF (TCPDF em /conectaosc/lib, com logoimpressao e padr�o visual da plataforma)

Excluir

Permiss�es de editar/excluir:

Superadministrador OU

o pr�prio profissional emissor

Sem permiss�o:

exibir prontu�rio em readonly

ocultar bot�o salvar altera��es

7.4 Atendimento

Formul�rio cl�nico para profissional

Parte dos dados vem de tbAluno e da consulta

Enviar dados para IA (fluxo similar a avaliarVulnerabilidade.php)

Criar processo/endpoint criarProntuario.php

Utilizar /temp/openaikey.php

Resposta da IA em textarea edit�vel

Profissional revisa e s� depois salva

Persistir em tb_prontuario (conteudo_ia + conteudo_editado)

8) API REST (obrigat�ria)

Formato padr�o:
{
  "success": true,
  "message": "Opera��o realizada com sucesso",
  "data": {},
  "errors": []
}

Endpoints m�nimos:

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

autentica��o e autoriza��o

valida��o de payload

status HTTP correto

rate limit b�sico em endpoints sens�veis

9) UI/UX obrigat�ria (Tailwind + Bootstrap)
9.1 Layout

Mobile-first obrigat�rio

Apar�ncia de app mesmo no desktop

Menu inferior fixo com �cones:

Pacientes

Agenda

Prontu�rio

Atendimento

9.2 Design Tokens

Usar rigorosamente os tokens definidos:

paleta de cores prim�rias/secund�rias/neutras/sem�nticas

tipografia Figtree/Roboto Mono

escala de spacing 4px

radius, shadows, breakpoints, anima��es, transi��es

componentes: bot�es, inputs, cards, tabelas, badges, modais etc.

9.3 Tailwind config

Gerar tailwind.config.js completo com:

cores customizadas (monday-blue, purple-accent, etc.)

spacing custom

borderRadius custom

boxShadow custom (incluindo shadow-monday)

fontes e tamanhos

9.4 Acessibilidade

foco vis�vel

contraste WCAG AA

teclado naveg�vel

�rea m�nima clic�vel 44x44

10) Seguran�a, logs e qualidade

Prepared statements obrigat�rios

sanitiza��o e valida��o de todas entradas

preven��o de XSS/CSRF

n�o expor segredos/tokens

logs t�cnicos sem dados sens�veis

respostas de erro padronizadas

em produ��o: sem stack trace para usu�rio

11) Entreg�veis esperados

Estrutura de pastas completa em /conectaosc/clinica e /conectaosc/clinica/api

N�cleo MVC + Router + Middlewares

Migra��es SQL + seeds iniciais

Endpoints REST implementados

Front mobile-first completo (4 m�dulos)

Integra��o IA para prontu�rio (gerar, editar, salvar)

PDF via TCPDF padronizado

Guia de instala��o/deploy por ambiente (dev/prod)

12) Crit�rio final de aceita��o

A solu��o s� � aceita se:

funcionar corretamente em localhost/conectaosc e iteva.com.br/conectaosc

usar padr�o de pastas /conectaosc/clinica/api/

manter front em /conectaosc/clinica/

respeitar integralmente as regras de neg�cio, permiss�es e design system

n�o quebrar compatibilidade com o ConectaOSC existente




