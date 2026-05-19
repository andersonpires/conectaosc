# ConectaOSC & App Clínica - Documentação e Funcionalidades

## 1. Visão Geral do Projeto

O projeto é um sistema de gestão integrado composto por **dois aplicativos em um**:
1. **ConectaOSC**: Um sistema ERP e CRM focado na gestão geral de uma Organização da Sociedade Civil (OSC) ou instituição educacional/social. Modelo Mobile First.
2. **App Clínica**: Um submódulo dedicado exclusivamente a profissionais de saúde (médicos, psicólogos, terapeutas, etc.) para gestão de pacientes, consultas e prontuários eletrônicos.

A arquitetura centraliza a autenticação e o banco de dados, permitindo que os colaboradores transitem entre o sistema administrativo (ConectaOSC) e o sistema clínico (App Clínica) de acordo com suas permissões.

---

## 2. Tecnologias Utilizadas (Tech Stack)

O sistema foi construído utilizando tecnologias robustas e integrações modernas:

*   **Backend:** PHP 8+ (com tipagem estrita `declare(strict_types=1)`). Utiliza frameworks MVC customizados criados internamente (`FrontEnd\` para o ConectaOSC e `App\` para a API da Clínica).
*   **Banco de Dados:** MySQL / MariaDB (acessado via PDO).
*   **Frontend (ConectaOSC):** Arquitetura baseada em Server-Side Rendering (SSR) gerando páginas dinâmicas, com uso de sessões para controle de estado.
*   **Frontend (App Clínica):** Single Page Application (SPA) utilizando HTML, Vanilla JavaScript (`app.js`) e **Tailwind CSS** para estilização.
*   **Integrações de API Externas:**
    *   **OpenAI API:** Utilizada para alimentar a "Bia" (Assistente Virtual do sistema) e gerar resumos/textos de prontuários com Inteligência Artificial.
    *   **Versatilis:** Integração com sistema externo de gestão de clínicas.
    *   **Twilio:** Para envio de comunicações (SMS/WhatsApp).
    *   **API de CPF e Invertexto:** Para validação de dados de beneficiários e manipulação de textos.

---

## 3. ConectaOSC (Módulo Principal)

O **ConectaOSC** é o coração administrativo da instituição. Ele funciona baseado em um modelo MVC (Model-View-Controller), mas separa regras de negócio complexas em classes chamadas **"Flows"** (ex: `BeneficiarioCadastroFlow`, `MatriculaController`).

### Funcionalidades Principais:

*   **Módulo Acadêmico / Educação:**
    *   Gestão de Cursos e Turmas (planejamento, cronograma).
    *   Matrículas (realização, cancelamento, emissão de contratos em PDF).
    *   Chamada (lista de presença e faltas).
    *   Avaliações e Quizz (ex: avaliação de professores).
*   **Gestão de Beneficiários:**
    *   Cadastro e listagem completa de beneficiários, incluindo fotos e consulta automática de CPF.
    *   Avaliação de vulnerabilidade social (com auxílio de IA/Stream).
    *   Controle de aniversariantes.
*   **CRM e Atendimento:**
    *   Registro e histórico de atendimentos e interações com os beneficiários.
*   **Assinatura Digital:**
    *   Geração, upload, preview e validação de PDFs com assinatura digital integrada.
*   **Gestão de Eventos:**
    *   Inscrições em eventos, lista de inscritos, disparo e verificação de e-mails.
*   **Relatórios:**
    *   Geração de relatórios gerenciais (frequência, matrículas, relatórios personalizados, análise SWOT, ficha de cadastro e anamnese IPAI).
*   **Administração e RH:**
    *   Gestão de Colaboradores e Perfis de Acesso (Permissões de páginas).
    *   Configurações gerais do sistema e gestão de feriados.
    *   Controle de Projetos e Alertas do sistema.
*   **Bia (Inteligência Artificial):**
    *   Assistente virtual integrada ao sistema para responder dúvidas e auxiliar nas operações da ONG em tempo real.

---

## 4. App Clínica (Módulo de Saúde)

O **App Clínica** fica localizado no diretório `/clinica`. Ele possui uma interface própria (SPA) e uma API REST exclusiva (`/clinica/api`).

### Modelo de Acesso e Segurança:
O acesso a este módulo é estritamente bloqueado. Apenas usuários autenticados no ConectaOSC que possuam a flag de **Profissional de Saúde** (`profissional_saude = 1`) em seu cadastro têm permissão de entrada.

### Funcionalidades Principais:

*   **Agenda e Consultas:**
    *   Controle diário da agenda (dias com agendamento, consultas aguardando atendimento, em atendimento).
    *   Ações na consulta: Iniciar atendimento, confirmar, cancelar, excluir ou reverter atendimento.
*   **Gestão de Pacientes:**
    *   Listagem e visualização de prontuários associados aos beneficiários do ConectaOSC.
*   **Prontuário Eletrônico:**
    *   Registro completo da consulta e da evolução do paciente.
    *   **Integração com IA:** Geração assistida de textos para prontuários utilizando a API da OpenAI.
    *   Geração de Prontuários em PDF (em lote ou individual).
*   **Anamnese:**
    *   Ficha clínica inicial estruturada para coleta do histórico de saúde do paciente.
*   **Cadastros Base:**
    *   Gestão de Especialidades, Tipos de Consulta e Profissionais de saúde atuantes.

---

## 5. Modo de Funcionamento e Fluxo de Trabalho

1.  **Autenticação Unificada:** O usuário entra pela tela de login principal (`/login`). O sistema cria um cookie de sessão único (`login_v4` / `PHPSESSID`).
2.  **Roteamento:**
    *   Se acessar a raiz ou rotas normais, o `app/index.php` e `FrontRouter` assumem o controle, renderizando páginas em PHP.
    *   Se acessar `/clinica`, o `clinica/index.php` valida a sessão, verifica se é profissional de saúde, e carrega a SPA (`app.js`).
    *   As chamadas da SPA são direcionadas para `/clinica/api/`, onde um roteador próprio (`\App\Core\Router`) responde em formato JSON.
3.  **Processamento em Lote e Assíncrono:** O sistema possui suporte a fluxos complexos, processamento de contratos em lote (arquivos ZIP), exportação robusta de PDFs e chamadas de stream (ex: `stream-ia` para ver a IA digitando o prontuário em tempo real).
4.  **Integração de Dados:** Os pacientes da Clínica são, em grande parte, os mesmos beneficiários cadastrados no módulo ConectaOSC. Isso cria um banco de dados centralizado, permitindo que o atendimento social e o clínico conversem sem redundância de dados.

## 6. Formato de ajuste, adaptação ou implantação de novas funcionalidades

1. Nunca salve BOM, use UTF-8, no front-end atue sempre com a ortografia e acentuação correta para português do Brasil.
2. Sempre faça a verificação se não há mojibake para ser corrigido ao final das implementações ou ajustes feitos.
3.  Verificar o arquivo .htaccess, api/core/router.php, api/routes/api.php, app/routes/web.php e app/core/FrontRouter.php para entender a estrutura de rotas (esses arquivos são a primeira coisa a serem verificados, pois eles são os responsáveis por redirecionar as requisições para os arquivos corretos).
4.  Entender o arquivo index.php (para entender como o sistema funciona).
5.  Entender os arquivos do diretório `app/` (esses arquivos contêm a lógica do sistema, como classes, funções, etc.).
6.  Entender os arquivos do diretório `app/Core/` (esses arquivos contêm a lógica do sistema, como classes, funções, etc.).
7.  Entender os arquivos do diretório `FrontEnd/` (esses arquivos contêm a lógica do sistema, como classes, funções, etc.).
8.  Entender os arquivos do diretório `FrontEnd/flows/` (esses arquivos contêm a lógica do sistema, como classes, funções, etc.).
9.  Entender os arquivos do diretório `FrontEnd/pages/` (esses arquivos contêm a lógica do sistema, como classes, funções, etc.).
10. Entender os arquivos do diretório `FrontEnd/views/` (esses arquivos contêm a lógica do sistema, como classes, funções, etc.).
11. Entender os arquivos do diretório `FrontEnd/views/pages/` (esses arquivos contêm a lógica do sistema, como classes, funções, etc.).
12. Entender os arquivos do diretório `clinica/` (esses arquivos contêm a lógica do sistema, como classes, funções, etc.).
13. Entender os arquivos do diretório `clinica/api/` (esses arquivos contêm a lógica do sistema, como classes, funções, etc.).
14. Entender os arquivos do diretório `clinica/api/flows/` (esses arquivos contêm a lógica do sistema, como classes, funções, etc.).
15. Entender os arquivos do diretório `clinica/api/pages/` (esses arquivos contêm a lógica do sistema, como classes, funções, etc.).
16. Entender os arquivos do diretório `clinica/api/views/` (esses arquivos contêm a lógica do sistema, como classes, funções, etc.).
17. Entender os arquivos do diretório `clinica/api/views/pages/` (esses arquivos contêm a lógica do sistema, como classes, funções, etc.).
