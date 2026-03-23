# Guia de Regras e Padrões de Desenvolvimento   
## ConectaOSC – App Clínica 

Este documento define **todas as regras técnicas, arquiteturais e funcionais obrigatórias** do projeto **App Clínica**.

Sempre que uma IA gerar **código, SQL, arquitetura, lógica de negócio, endpoints, páginas, componentes de UI ou testes**, ela **DEVE obedecer integralmente** às regras descritas abaixo.

---

## 1. Arquitetura Geral

O projeto **App Clínica** roda dentro do ecossistema **ConectaOSC**, na pasta `/conectaosc`, e deve manter compatibilidade com o sistema principal.

### 1.1 Arquitetura oficial do App Clínica
- **Back-end:** PHP 8.1+ em **MVC sem framework**.
- **API:** REST com respostas em JSON.
- **Front-end:** HTML5, CSS, JS, TypeScript, Tailwind CSS e Bootstrap.
- **Layout:** Mobile-first, visual SaaS estilo app (inclusive no desktop).

### 1.2 Regra geral
- Toda funcionalidade nova da clínica deve seguir a arquitetura MVC + API REST.
- Qualquer integração com legado deve ser feita sem quebrar o ConectaOSC existente.
- Codificação UTF-8 obrigatória em arquivos, banco e respostas da API.

---

## 2. Estrutura Oficial de Pastas (Regra Crítica)

### 2.1 Caminho obrigatório
- **Front-end:** `/conectaosc/clinica/`
- **API/Back-end:** `/conectaosc/clinica/api/`

> Regra crítica: **não usar** `api/clinica`.  
> O padrão obrigatório é **`clinica/api`**.

### 2.2 Estrutura mínima recomendada
```txt
/conectaosc/
  clinica/
    index.html (ou entrypoint front)
    assets/
    src/
      components/
      pages/
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
      routes/
      config/
      storage/logs/

3. Ambientes, Base URL e Sessão
3.1 Ambientes suportados

Produção: https://iteva.com.br/conectaosc

Desenvolvimento: http://localhost/conectaosc

3.2 Auto-detecção obrigatória

Detectar ambiente por $_SERVER['HTTP_HOST']:

host contém localhost ou 127.0.0.1 -> development

demais casos -> production

3.3 Constantes/parâmetros obrigatórios

APP_ENV

APP_BASE_PATH = /conectaosc

CLINICA_BASE_PATH = /conectaosc/clinica

APP_BASE_URL

FRONT_BASE_URL = {APP_BASE_URL}/clinica

API_BASE_URL = {APP_BASE_URL}/clinica/api

3.4 Sessão e cookies

session.cookie_httponly = true

session.cookie_secure = true em produção

session.cookie_samesite = Lax (ou Strict quando viável)

timezone padrão America/Sao_Paulo

CORS restrito às origens autorizadas do projeto

4. Stack Tecnológica Oficial
Back-end

PHP 8.1+

MVC sem framework

REST API JSON

PDO + MySQL

Arquitetura por camadas (Core, Controller, Service, Model/Repository, Middleware)

Front-end

HTML5, CSS3, JavaScript, TypeScript

Tailwind CSS (design tokens obrigatórios)

Bootstrap (suporte de layout/componentes)

Lucide Icons (preferencial)

Bibliotecas e integrações

TCPDF em /conectaosc/lib para PDFs

Integração com IA usando /temp/openaikey.php

Compatibilidade com autenticação e permissões do ConectaOSC

5. Conexão com Banco de Dados (Regra Crítica)
5.1 Configuração padrão obrigatória (fallback)
if (!isset($_ENV['DB_HOST'])) $_ENV['DB_HOST'] = '162.241.2.214';
if (!isset($_ENV['DB_NAME'])) $_ENV['DB_NAME'] = 'mwtech63_matricula';
if (!isset($_ENV['DB_USER'])) $_ENV['DB_USER'] = 'mwtech63_admin_matricula';
if (!isset($_ENV['DB_PASS'])) $_ENV['DB_PASS'] = 'Iteva@100';
if (!isset($_ENV['DB_PORT'])) $_ENV['DB_PORT'] = '3306';

5.2 Regras obrigatórias

Usar somente PDO em novos códigos.

DSN com charset=utf8mb4.

PDO::ATTR_ERRMODE = PDO::ERRMODE_EXCEPTION.

PDO::ATTR_DEFAULT_FETCH_MODE = PDO::FETCH_ASSOC.

Prepared statements obrigatórios para todo dado externo.

Proibido expor credenciais em logs, tela, JSON ou mensagens de erro públicas.

6. Segurança e Boas Práticas

Nunca concatenar SQL com input do usuário.

Validar e sanitizar todas as entradas (query string, body JSON, formulários).

Escapar saída para evitar XSS.

Implementar proteção CSRF em ações críticas no contexto web com sessão.

Controle de acesso por autenticação + autorização.

Em produção, nunca exibir stack trace.

Logs via error_log() sem dados sensíveis.

7. Modelagem de Dados e Convenções
7.1 Convenções

Tabelas novas com prefixo tb.

Views novas com prefixo vw.

Campos de auditoria: created_at, updated_at quando aplicável.

Índices para colunas de busca frequente.

7.2 Alteração obrigatória em tabela existente
tbUser

Adicionar:

profissional_saude TINYINT(1) NOT NULL DEFAULT 0

índice em profissional_saude

Também atualizar o formColaborador:

inserir checkbox Profissional de saúde

persistir em tbUser.profissional_saude

7.3 Novas tabelas obrigatórias
tb_especialidade

id (PK AI)

nome (varchar 100 unique)

ativo (tinyint(1) default 1)

created_at, updated_at

Seeds obrigatórios:

Fisioretapia

Psicologia

Nutrição

Fonaudiologia

tb_tipo_consulta

id (PK AI)

nome (varchar 100 unique)

ativo (tinyint(1) default 1)

created_at, updated_at

Seeds obrigatórios:

Consulta simples

Consulta on-line

Anamnese

Retorno

IPAI

tb_consulta

id (PK AI)

aluno_id (FK tbAluno)

profissional_id (FK tbUser, nullable)

profissional_nome_livre (varchar 120, nullable)

especialidade_id (FK tb_especialidade)

tipo_consulta_id (FK tb_tipo_consulta)

data_consulta (date)

hora_inicio_prevista (time)

duracao_minutos_prevista (int)

hora_fim_prevista (time)

hora_fim_real (time nullable)

status (agendada, confirmacao_solicitada, cancelada, em_atendimento, concluida)

observacao (text nullable)

criado_por (FK tbUser)

created_at, updated_at

tb_agenda_clinica

id (PK AI)

consulta_id (FK tb_consulta, unique)

data_agenda (date)

inicio (datetime)

fim_previsto (datetime)

status (varchar 30)

created_at, updated_at

tb_prontuario

id (PK AI)

consulta_id (FK tb_consulta)

aluno_id (FK tbAluno)

profissional_id (FK tbUser)

conteudo_ia (longtext)

conteudo_editado (longtext)

versao (int default 1)

status (rascunho, finalizado)

created_at, updated_at

7.4 Integridade e performance

Criar índices para aluno, profissional, data e status.

Definir FKs com políticas adequadas (RESTRICT ou SET NULL conforme regra de negócio).

Toda mudança estrutural deve ter script SQL versionado.

8. API REST – Padrão Obrigatório
8.1 Padrão de resposta JSON
{
  "success": true,
  "message": "Mensagem de retorno",
  "data": {},
  "errors": []
}

8.2 Convenções

HTTP methods corretos (GET, POST, PUT, DELETE).

Status code correto (200, 201, 400, 401, 403, 404, 422, 500).

Erro sem vazamento de dados internos.

Payloads validados antes de persistência.

8.3 Endpoints mínimos obrigatórios

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

9. Regras Funcionais do App Clínica
9.1 Pacientes

Exibir listagem de pacientes ativos vindos da tbAluno.

Busca rápida por nome/contato/documento.

Ao selecionar paciente, abrir menu rápido com ação de agendamento.

9.2 Agendar Consulta

Campos obrigatórios:

Data

Hora

Tempo de consulta (min)

Tipo de consulta (tb_tipo_consulta)

Término previsto calculado automaticamente

Término real (após atendimento)

Contato do paciente (da tbAluno)

Especialidade (tb_especialidade)

Profissional:

opção “Plantonista”

profissionais de tbUser com profissional_saude = 1

Botão “Agendar consulta”

Persistência obrigatória em:

tb_consulta

tb_agenda_clinica

9.3 Agenda

Modelo visual semelhante ao Google Calendar.

Exibir horários do dia de 07:00 às 22:00.

Grade com linhas de 30 em 30 minutos.

Topo com mês/ano atual.

Controles: Hoje, <, >.

Modos de visualização: Dia (padrão), Semana, Mês.

Consultas como blocos na grade (nome do paciente dentro do bloco).

Ao clicar no bloco:

abrir modal com dados completos da consulta e paciente

ações:

Editar agendamento

Solicitar confirmação

Cancelar agendamento

Iniciar atendimento (apenas para profissional de saúde)

9.4 Prontuário

Tabela em ordem decrescente por data/hora:

Data e hora

Nome do profissional emissor

Ações (editar, imprimir PDF, excluir)

Regras de permissão para editar/excluir:

Superadministrador, ou

próprio profissional que emitiu

Sem permissão:

leitura apenas (readonly)

ocultar botão de salvar alterações

PDF:

usar TCPDF da pasta /conectaosc/lib

incluir logoimpressao

seguir padrão visual dos demais relatórios da plataforma

9.5 Atendimento + IA

Formulário próprio para o profissional.

Parte dos dados vem de tbAluno e da consulta.

Fluxo de IA similar ao avaliarVulnerabilidade.php.

Processo/endpoint: criarProntuario.php.

Chave via /temp/openaikey.php.

Resultado da IA deve ser exibido em textarea editável.

Só salvar na tb_prontuario após revisão humana.

Armazenar conteudo_ia e conteudo_editado.

10. UI/UX, Design System e Componentes

A interface do App Clínica deve obedecer aos tokens e padrões definidos:

Paleta de cores (primárias, secundárias, neutras e semânticas)

Tipografia (Figtree + Roboto Mono)

Escala de espaçamento (base 4px)

Border radius

Shadows (incluindo shadow-monday)

Breakpoints responsivos

Estados de hover/focus/active

Componentes (botões, inputs, cards, tabela, badge, modal etc.)

10.1 Navegação

Mobile-first obrigatório.

Visual de app no desktop também.

Barra fixa inferior com ícones:

Pacientes

Agenda

Prontuário

Atendimento

10.2 Tailwind

tailwind.config.js deve conter todas as extensões de cor, tipografia, spacing, radius e sombra definidas no projeto.

Uso consistente de classes utilitárias conforme padrão.

11. Acessibilidade (Obrigatório)

Contraste mínimo WCAG AA.

Foco visível (focus-visible:ring-2 ...).

Navegação por teclado em todos componentes interativos.

Alvo mínimo clicável de 44x44.

Mensagens de erro e feedback claras ao usuário.

12. Permissões e Controle de Acesso

Toda rota sensível exige autenticação válida.

Ações de prontuário devem respeitar:

Superadministrador

Profissional emissor

Botão “Iniciar atendimento” visível apenas para profissional_saude = 1.

Toda permissão deve ser validada no back-end (não apenas no front-end).

13. Logs, Erros e Auditoria

Registrar erros críticos com error_log().

Não vazar informações internas em produção.

Padronizar mensagens de erro de API.

Registrar ações relevantes (criação/edição/cancelamento de consulta, geração/edição/exclusão de prontuário) quando possível.

14. SQL, Migração e Deploy

Toda alteração de banco exige script SQL versionado.

Criar scripts de seed para especialidades e tipos de consulta.

Executar backup antes de mudanças em produção.

Validar integridade de FKs e índices após deploy.

15. Regra Final (Crítica)

Sempre que a IA:

Criar código

Criar SQL

Criar endpoints

Criar interface

Criar lógica de negócio

Ela DEVE:
- Respeitar a estrutura oficial: front em /conectaosc/clinica/ e API em /conectaosc/clinica/api/.
- Implementar auto-detecção de ambiente (localhost vs produção).
- Manter compatibilidade total com o ConectaOSC.
- Aplicar segurança (PDO + prepared statements + validação + controle de acesso).
- Seguir integralmente o design system e o layout mobile-first.
- Cumprir as regras de permissão por perfil e por autoria de prontuário.
- Não expor credenciais, tokens, stack traces ou dados sensíveis.
- A cada vez que for rodado um prompt, antes de iniciar o código e após conlcuí-lo, o documento @project_rules deve ser avaliado para ver se o código atende as necessidades e regras do projeto
- Todas as alteraçoes no banco serão criadas por você através de arquivos .sql e com a criação de arquivos .php para rodarem o .sql no banco online, pedindo ao usuário para executar o arquivo. Esses arquivos devem ficar na pasta !Suporte/migrations
- Todos os arquivos .MD e arquivos que forem gerados como memória devem ficar na pasta !Suporte/MDs
- Este documento é obrigatório, permanente e só pode ser alterado com decisão explícita da liderança técnica do projeto.
