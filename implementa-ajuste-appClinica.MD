# Plano de Implementação — Ajustes App Clínica (ConectaOSC)

> Versão revisada após análise crítica do sistema real. Substitui o plano anterior.

---

## Avaliação do Plano Original

O plano original captura bem a direção geral, mas apresenta **lacunas técnicas críticas** que causariam falhas silenciosas durante a implementação. As seções abaixo detalham o que foi corrigido e por quê.

### Lacunas críticas encontradas

**1. `licenca_administrativa` não é carregado na sessão**
`clinica/index.php` e `clinica/api/index.php` carregam apenas `profissional_saude` e `especialidade_id` da tabela `tbUser`. O novo campo precisa ser consultado e salvo em `$_SESSION` nos dois arquivos — e o guarda de acesso em `clinica/index.php` (linha 189) também precisa ser atualizado.

**2. Endpoint `/api/v1/me` da aplicação principal não mencionado**
Ambos os bootstraps fazem uma chamada HTTP para o endpoint `/api/v1/me` da aplicação principal (fora do diretório `clinica/`) para resolver dados do usuário. Esse endpoint precisa retornar `licenca_administrativa`. O plano anterior ignora completamente esse endpoint.

**3. `window.__CLINICA_BOOTSTRAP__` incompleto**
O objeto bootstrap injetado pelo `clinica/index.php` atualmente contém apenas `usuario.nome` e `usuario.fotoUrl`. Para que o SPA condicione menus e ações por papel do usuário, precisa receber `profissional_saude`, `licenca_administrativa` e `is_admin`. O plano anterior menciona injetar a role mas não especifica os campos.

**4. `clinica/index.html` ignorado — é onde ficam os botões de navegação**
Os botões da barra de navegação inferior estão no HTML estático (`clinica/index.html`), não no `app.js`. Os novos menus "Evoluções" e "Configurações" precisam ser adicionados lá. O plano anterior cita apenas `app.js`.

**5. `clinica/api/index.php` não mencionado para novas rotas**
Cada novo endpoint (tipos-consulta CRUD, evoluções CRUD) precisa ser registrado no arquivo de bootstrap da API. Esse arquivo é o equivalente ao `web.php` da clínica. O plano anterior não o menciona.

**6. Validação de permissão para `profissional_saude` / `licenca_administrativa` em `ColaboradorFlow.php`**
A regra de negócio "apenas administradores podem definir esses campos" precisa ser validada no `handlePost` do `ColaboradorFlow.php`. O plano anterior menciona a regra mas não aponta onde implementar o check no backend.

**7. Abordagem de anamnese unificada é tecnicamente ruim**
As duas anamneses servem populações clinicamente distintas:
- **Atual (`tb_anamnese_psi`)**: Avaliação psicológica de adultos — MMSE, saúde mental, funcionalidade.
- **Novo roteiro solicitado**: Anamnese pediátrica/familiar — história gestacional, desenvolvimento psicomotor, alimentação, escolaridade.

Fundir tudo em uma única tabela criaria ~70+ colunas com a maioria NULL por registro, além de um formulário confuso para o profissional. A solução correta é manter tabelas separadas com um design de tela que permita navegar entre elas.

---

## Arquitetura de Implementação Corrigida

---

## Etapa 1 — Banco de Dados

### 1.1 — Script `008_add_licenca_administrativa_tbuser.sql`

```sql
-- Migration 008: Licença Administrativa no App Clínica
SET NAMES utf8mb4;

ALTER TABLE tbUser
  ADD COLUMN IF NOT EXISTS licenca_administrativa TINYINT(1) NOT NULL DEFAULT 0,
  ADD INDEX IF NOT EXISTS idx_licenca_adm (licenca_administrativa);
```

### 1.2 — Script `009_tipos_consulta_crud_seed.sql`

`tb_tipo_consulta` já existe (migration 002). Apenas garantir dados iniciais e que registros fixos do frontend sejam migrados para o banco:

```sql
-- Migration 009: Seed tipos de consulta (se ainda não existirem)
SET NAMES utf8mb4;

INSERT IGNORE INTO tb_tipo_consulta (nome, ativo) VALUES
  ('Anamnese', 1),
  ('Consulta Online', 1),
  ('Consulta Simples', 1),
  ('PAI', 1),
  ('Psicodiagnóstico', 1),
  ('Retorno', 1);
```

### 1.3 — Script `010_create_tb_anamnese_roteiro.sql`

Tabela separada para a anamnese pediátrica/familiar (novo roteiro). A `tb_anamnese_psi` permanece intacta para a avaliação de adultos.

```sql
-- Migration 010: Anamnese Roteiro Pediátrico/Familiar
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS tb_anamnese_roteiro (
    id INT(11) NOT NULL AUTO_INCREMENT,
    consulta_id INT(11) NULL DEFAULT NULL,
    aluno_id INT(11) NOT NULL,
    profissional_id INT(11) NOT NULL,

    -- Identificação e queixa principal
    nome_paciente VARCHAR(200) NULL,
    motivo_atendimento TEXT NULL,

    -- Acompanhamentos anteriores/atuais
    acomp_psicologico TINYINT(1) NULL,
    acomp_psi_onde VARCHAR(200) NULL,
    acomp_psi_quando VARCHAR(100) NULL,
    acomp_psicopedagogico TINYINT(1) NULL,
    acomp_psicoped_onde VARCHAR(200) NULL,
    acomp_psicoped_quando VARCHAR(100) NULL,
    acomp_fonoaudiologico TINYINT(1) NULL,
    acomp_fono_onde VARCHAR(200) NULL,
    acomp_fono_quando VARCHAR(100) NULL,
    acomp_neurologico TINYINT(1) NULL,
    acomp_neuro_onde VARCHAR(200) NULL,
    acomp_neuro_quando VARCHAR(100) NULL,
    acomp_terapia_ocupacional TINYINT(1) NULL,
    acomp_to_onde VARCHAR(200) NULL,
    acomp_to_quando VARCHAR(100) NULL,
    acomp_fisioterapia TINYINT(1) NULL,
    acomp_fisio_onde VARCHAR(200) NULL,
    acomp_fisio_quando VARCHAR(100) NULL,
    acomp_outros TEXT NULL,

    -- 01 - História gestacional
    historia_gestacional TEXT NULL,

    -- 02 - Antecedentes mórbidos
    antecedentes_morbidos TEXT NULL,

    -- 03 - Psicomotor
    psicomotor TEXT NULL,

    -- 04 - Linguagem
    linguagem TEXT NULL,

    -- 05 - Alimentação
    alimentacao TEXT NULL,

    -- 06 - Sono
    sono TEXT NULL,

    -- 07 - Escolaridade
    escolaridade TEXT NULL,
    escola_nome VARCHAR(200) NULL,
    escola_telefone VARCHAR(30) NULL,

    -- 08 - Sociabilidade
    sociabilidade TEXT NULL,

    -- 09 - Sexualidade
    sexualidade TEXT NULL,

    -- 10 - Situação socioeconômica
    situacao_socioeconomica TEXT NULL,

    -- 11 - Aspectos sensoriais
    dificuldade_visao TINYINT(1) NULL,
    dificuldade_visao_desc TEXT NULL,
    dificuldade_audicao TINYINT(1) NULL,

    -- 12 - Antecedentes familiares
    antec_fam_doencas TINYINT(1) NULL,
    antec_fam_doencas_quais VARCHAR(200) NULL,
    antec_fam_alcoolismo TINYINT(1) NULL,
    antec_fam_homicidio TINYINT(1) NULL,
    antec_fam_def_mental TINYINT(1) NULL,
    antec_fam_suicidio TINYINT(1) NULL,
    antec_fam_drogadicao TINYINT(1) NULL,
    antec_fam_outros TEXT NULL,
    antec_fam_grau_parentesco VARCHAR(200) NULL,

    -- Constelação familiar
    num_irmaos VARCHAR(100) NULL,
    posicao_familiar VARCHAR(100) NULL,
    situacao_pais VARCHAR(30) NULL,

    -- Dados de triagem
    triagem_por VARCHAR(200) NULL,
    triagem_inicio VARCHAR(20) NULL,
    triagem_termino VARCHAR(20) NULL,
    hipotese_diagnostica TEXT NULL,
    conclusao VARCHAR(20) NULL,
    indicacao_terapeutica TEXT NULL,
    necessidade_atendimento VARCHAR(30) NULL,
    profissional_triagem VARCHAR(200) NULL,
    data_triagem DATE NULL,

    -- Impressões gerais (checkboxes)
    impressao_tranquilo TINYINT(1) NULL DEFAULT 0,
    impressao_ansioso TINYINT(1) NULL DEFAULT 0,
    impressao_seguro TINYINT(1) NULL DEFAULT 0,
    impressao_alegre TINYINT(1) NULL DEFAULT 0,
    impressao_queixoso TINYINT(1) NULL DEFAULT 0,
    impressao_intolerante TINYINT(1) NULL DEFAULT 0,
    impressao_atencao TINYINT(1) NULL DEFAULT 0,
    impressao_adequacao_respostas TINYINT(1) NULL DEFAULT 0,

    -- Campos finais
    rotina_paciente TEXT NULL,
    perdas_recentes TEXT NULL,
    outras_informacoes TEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_consulta (consulta_id),
    KEY idx_aluno (aluno_id),
    KEY idx_profissional (profissional_id),
    KEY idx_created (created_at),
    CONSTRAINT fk_rot_consulta FOREIGN KEY (consulta_id) REFERENCES tb_consulta (id) ON DELETE SET NULL,
    CONSTRAINT fk_rot_aluno FOREIGN KEY (aluno_id) REFERENCES tbAluno (IdUsuario) ON DELETE RESTRICT,
    CONSTRAINT fk_rot_profissional FOREIGN KEY (profissional_id) REFERENCES tbUser (IdColaborador) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
```

**Nota:** `consulta_id` é nullable para permitir anamnese avulsa (sem consulta vinculada) e para que o mesmo paciente possa ter múltiplas anamneses.

### 1.4 — Script `011_create_tb_evolucao_clinica.sql`

```sql
-- Migration 011: Tabela de Evoluções Clínicas
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS tb_evolucao_clinica (
    id INT(11) NOT NULL AUTO_INCREMENT,
    aluno_id INT(11) NOT NULL,
    profissional_id INT(11) NOT NULL,
    consulta_id INT(11) NULL DEFAULT NULL,
    especialidade_id INT(11) NULL DEFAULT NULL,
    data_evolucao DATE NOT NULL,
    hora_evolucao TIME NOT NULL,
    conteudo LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_aluno (aluno_id),
    KEY idx_profissional (profissional_id),
    KEY idx_consulta (consulta_id),
    KEY idx_data (data_evolucao),
    CONSTRAINT fk_evol_aluno FOREIGN KEY (aluno_id) REFERENCES tbAluno (IdUsuario) ON DELETE RESTRICT,
    CONSTRAINT fk_evol_profissional FOREIGN KEY (profissional_id) REFERENCES tbUser (IdColaborador) ON DELETE RESTRICT,
    CONSTRAINT fk_evol_consulta FOREIGN KEY (consulta_id) REFERENCES tb_consulta (id) ON DELETE SET NULL,
    CONSTRAINT fk_evol_especialidade FOREIGN KEY (especialidade_id) REFERENCES tb_especialidade (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
```

### 1.5 — Script executor PHP: `run_migrations_clinica.php` (raiz do projeto)

Arquivo PHP para executar remotamente todas as migrations acima em sequência, com verificação de integridade antes de cada ALTER/CREATE.

---

## Etapa 2 — Backend: Permissões e Acesso

### 2.1 — `clinica/index.php` (MODIFICAR — 3 pontos)

**Ponto A:** Carregar `licenca_administrativa` do banco junto com `profissional_saude` (linhas 178-183 do fallback de BD, e a leitura do `/api/v1/me`).

**Ponto B:** Atualizar o guarda de acesso (linha 189):
```php
// Antes:
if ($profissionalSaudeAtual !== 1) { die('...'); }

// Depois:
$licencaAdm = (int)($_SESSION['licenca_administrativa'] ?? 0);
if ($profissionalSaudeAtual !== 1 && $licencaAdm !== 1) {
    header('Content-Type: text/html; charset=utf-8');
    die('<div ...>Acesso restrito ao App Clínica...</div>');
}
```

**Ponto C:** Expandir o `window.__CLINICA_BOOTSTRAP__` (linhas 210-218):
```php
$clinicaBootstrap = [
    'usuario' => [
        'nome'                  => (string)($_SESSION['Nome'] ?? ''),
        'fotoUrl'               => ...,
        'profissional_saude'    => (int)($_SESSION['profissional_saude'] ?? 0),
        'licenca_administrativa' => (int)($_SESSION['licenca_administrativa'] ?? 0),
        'is_admin'              => AuthMiddleware::isSuperAdmin() ? 1 : 0,
    ],
];
```

### 2.2 — `clinica/api/index.php` (MODIFICAR — 2 pontos)

**Ponto A:** Carregar e salvar `licenca_administrativa` na sessão (blocos de fallback de BD e de leitura do `/api/v1/me`).

**Ponto B:** Registrar as novas rotas (ver Etapa 5).

### 2.3 — `clinica/api/app/Middlewares/AuthMiddleware.php` (MODIFICAR)

Adicionar dois novos métodos mantendo os atuais:

```php
// Permite profissional de saúde OU licença administrativa
public static function requireAcessoClinica(): void
{
    self::requireAuth();
    $prof = (int)($_SESSION['profissional_saude'] ?? 0);
    $adm  = (int)($_SESSION['licenca_administrativa'] ?? 0);
    if ($prof !== 1 && $adm !== 1) {
        JsonResponse::error('Acesso restrito ao App Clínica', [], 403);
    }
}

// Verifica se tem licença administrativa (sem acesso clínico)
public static function isLicencaAdministrativa(): bool
{
    return (int)($_SESSION['licenca_administrativa'] ?? 0) === 1
        && (int)($_SESSION['profissional_saude'] ?? 0) !== 1;
}
```

Os métodos `requireAuth()` e `requireProfissionalSaude()` permanecem inalterados.

### 2.4 — Endpoint `/api/v1/me` da aplicação principal (IDENTIFICAR E MODIFICAR)

Localizar o endpoint `/api/v1/me` no sistema principal (fora do diretório `clinica/`) — deve estar em `app/` ou na API principal — e adicionar o campo `licenca_administrativa` ao array de resposta.

### 2.5 — `app/controllers/ColaboradorFlow.php` — `handlePost` (MODIFICAR)

Antes de processar `profissional_saude` e `licenca_administrativa` no POST, verificar se o usuário logado tem permissão de administrador:

```php
// No handlePost, antes de aceitar os campos clínicos:
$podeDefinirClinica = $this->currentUserIsAdmin();

$dados['profissional_saude']     = $podeDefinirClinica ? (int)($_POST['profissional_saude'] ?? 0) : /* valor atual do BD */;
$dados['licenca_administrativa'] = $podeDefinirClinica ? (int)($_POST['licenca_administrativa'] ?? 0) : /* valor atual do BD */;
```

### 2.6 — `app/views/colaborador/formColaborador.php` (MODIFICAR)

Alterar o campo atual "Profissional de Saúde" para um bloco condicional:

- Rótulo: **"Habilitar para App Clínica"**
- Radio/select com opções: `Não habilitado`, `Profissional de Saúde`, `Licença Administrativa`
- O bloco de especialidade aparece somente quando "Profissional de Saúde" for selecionado (JS inline ou Tailwind `hidden`)
- O bloco inteiro fica desabilitado (readonly visual) se o usuário logado não for administrador

---

## Etapa 3 — Tipos de Consulta (CRUD Administrativo)

### 3.1 — `clinica/api/app/Controllers/TiposConsultaController.php` (MODIFICAR)

Adicionar métodos ao controller existente (que já tem `index()`):

```
store()   — POST /tipos-consulta      — cria novo tipo; requer isSuperAdmin()
update()  — PUT  /tipos-consulta/{id} — edita nome; requer isSuperAdmin()
toggle()  — POST /tipos-consulta/{id}/toggle — ativa/inativa; requer isSuperAdmin()
```

### 3.2 — `clinica/src/pages/configuracoes.js` (NOVO)

Página da SPA acessível apenas para `is_admin === 1`. Contém:
- Submenu com "Tipos de Consulta" (inicialmente único item)
- Listagem com nome, status e ações (editar, ativar/inativar)
- Modal de criação/edição

### 3.3 — `clinica/index.html` (MODIFICAR — novo botão de navegação)

Adicionar botão na `<nav>` inferior, **após "Atendimento"**, visível apenas para administradores (ocultado via JS pós-carregamento com base em `window.__CLINICA_BOOTSTRAP__.usuario.is_admin`):

```html
<button type="button" data-page="configuracoes" class="nav-btn ...">
  <i data-lucide="settings" class="w-6 h-6 mb-0.5"></i>
  <span class="text-xs">Configurações</span>
</button>
```

### 3.4 — `clinica/src/app.js` (MODIFICAR)

- Importar `renderConfiguracoes` de `./pages/configuracoes.js`
- Adicionar `configuracoes: 'configuracoes'` ao `ROUTE_BY_PAGE`
- Adicionar `case 'configuracoes': await renderConfiguracoes(main); break;`
- Ocultar botão de configurações para não-admins após bootstrap

### 3.5 — `clinica/src/pages/agenda.js` — campo tipo de consulta (MODIFICAR)

O select de tipo de consulta no modal de agendamento deve buscar dados do endpoint `GET /tipos-consulta` em vez de lista fixa hardcoded.

---

## Etapa 4 — Solicitação de Confirmação de Consulta

### 4.1 — `clinica/src/pages/agenda.js` (MODIFICAR)

Ao clicar em "Solicitar confirmação" no modal de agendamento:

1. Buscar dados completos da consulta (paciente, data, hora, profissional, especialidade, tipo, telefone/WhatsApp do paciente).
2. Abrir modal com:
   - Cabeçalho: dados da consulta
   - Geração automática de mensagem sugerida (interpolada em JS)
   - Textarea editável com a mensagem
   - Botões habilitados conforme contato disponível: WhatsApp / SMS / aviso "Sem contato cadastrado"
3. Ao clicar em qualquer botão de envio, exibir: `"Funcionalidade de envio ainda não disponível. Em fase de produção."`
4. Preparar estrutura para futura integração com Twilio (já presente no sistema principal).

**Verificação de contato:** Os campos `WhatsApp` e `Telefone` (ou similar) do paciente devem ser verificados na resposta de `GET /pacientes/{id}`.

---

## Etapa 5 — Fluxo de Iniciar Atendimento

### 5.1 — `clinica/src/pages/agenda.js` (MODIFICAR)

Interceptar clique em "Iniciar atendimento":
- Se o usuário for `licenca_administrativa` (não profissional), bloquear com mensagem de acesso.
- Se for profissional, verificar `tipo_consulta` do agendamento:
  - Tipo **Anamnese** → oferecer escolha: "Anamnese Adulto (avaliação psicológica)" ou "Anamnese Roteiro (pediátrico/familiar)"
  - Qualquer outro tipo → opção de "Iniciar Evolução Clínica" e, se disponível, "Registrar Prontuário"

### 5.2 — `clinica/api/app/Controllers/ConsultasController.php` (MODIFICAR)

`iniciarAtendimento()` deve chamar `AuthMiddleware::requireProfissionalSaude()` antes de qualquer ação — garantia no backend.

---

## Etapa 6 — Anamnese (dois modelos coexistentes)

### 6.1 — Design de tela

A tela de atendimento (`clinica/src/pages/atendimento.js`) apresenta:
- Se houver anamnese PSI existente → exibir card com link para visualizar/editar
- Se houver anamnese Roteiro existente → exibir card com link
- Botão "Nova Anamnese" com sub-opções: "Avaliação Psicológica (adulto)" e "Roteiro Pediátrico/Familiar"

### 6.2 — `clinica/api/app/Controllers/AnamneseController.php` (MODIFICAR)

Manter os métodos atuais para `tb_anamnese_psi`. Adicionar query string `?tipo=roteiro` para direcionar para `tb_anamnese_roteiro`.

### 6.3 — `clinica/api/app/Controllers/AnamneseRoteiroController.php` (NOVO)

Controller dedicado para o novo roteiro pediátrico/familiar:
- `index()` — lista por `aluno_id`
- `show(id)`
- `store()` — requer `requireProfissionalSaude()`
- `update(id)` — requer autor

### 6.4 — Formulário HTML/JS

O formulário do roteiro pediátrico usará:
- Checkboxes para acompanhamentos anteriores
- Checkboxes para antecedentes familiares
- Checkboxes para impressões gerais
- Radio para conclusão (elegível/inelegível), situação dos pais
- Inputs text/date para campos de triagem
- Textareas para histórias narrativas
- Nenhum campo obrigatório — exibir indicadores de progresso apenas

---

## Etapa 7 — Módulo de Evoluções

### 7.1 — `clinica/api/app/Controllers/EvolucoesController.php` (NOVO)

```
index()       GET  /evolucoes            — lista com filtros; requireAcessoClinica()
show(id)      GET  /evolucoes/{id}       — detalhe; bloqueia conteúdo para licenca_administrativa
store()       POST /evolucoes            — requireProfissionalSaude()
update(id)    PUT  /evolucoes/{id}       — requer autor === usuário logado
paciente(id)  GET  /evolucoes/paciente/{id} — evolucoes de um paciente
```

Regra: `isLicencaAdministrativa()` recebe 403 ao tentar acessar `show()`, `store()` ou `update()`.

### 7.2 — `clinica/index.html` (MODIFICAR — novo botão de navegação)

Adicionar botão "Evoluções" **entre "Prontuário" e "Atendimento"**, visível apenas para `profissional_saude === 1`:

```html
<button type="button" data-page="evolucoes" class="nav-btn ...">
  <i data-lucide="clipboard-list" class="w-6 h-6 mb-0.5"></i>
  <span class="text-xs">Evoluções</span>
</button>
```

### 7.3 — `clinica/src/pages/evolucoes.js` (NOVO)

- Cabeçalho com filtros: paciente (busca), profissional, especialidade, data
- Tabela: data+profissional | início do texto | botão "Ver"
- Modal ou página de detalhe com TinyMCE (já carregado em `index.html`) em modo leitura
- Edição habilitada apenas para o autor

### 7.4 — `clinica/src/pages/pacientes.js` (MODIFICAR)

Ao clicar em um paciente, adicionar opção "Ver Evoluções" que navega para `evolucoes` com filtro `aluno_id` pré-aplicado. Ocultar essa opção para `licenca_administrativa`.

### 7.5 — `clinica/src/app.js` (MODIFICAR)

- Importar `renderEvolucoes` de `./pages/evolucoes.js`
- Adicionar `evolucoes: 'evolucoes'` ao `ROUTE_BY_PAGE`
- Adicionar `case 'evolucoes': await renderEvolucoes(main, options); break;`
- Ocultar botão "Evoluções" para usuários sem `profissional_saude`

---

## Etapa 8 — Cadastro e Edição de Evolução

### 8.1 — Tela de criação/edição (dentro de `evolucoes.js` ou sub-página)

Componentes obrigatórios:
- Cabeçalho: nome do paciente, foto, data de nascimento
- Dados do agendamento (se vinculado): tipo, profissional, especialidade
- `<input type="date">` e `<input type="time">` para data/hora da evolução (pré-preenchidos com a consulta, editáveis)
- Editor TinyMCE com template inicial sugerido pré-carregado (negrito, listas, etc.)
- Botão "Salvar" e "Imprimir/PDF"

**Template sugerido pré-carregado no editor:**
```
Dados observados durante o atendimento:

Queixas ou temas principais abordados:

Estado cognitivo:

Estado de humor:

Aspectos sociais:

Impressões do profissional:

Condutas adotadas:

Encaminhamentos ou orientações:

Postura/plano para próxima consulta:
```

### 8.2 — Persistência

Ao salvar: `data_evolucao` e `hora_evolucao` vêm dos inputs, `created_at` é automático, `profissional_id` vem da sessão.

---

## Etapa 9 — Impressão/PDF de Evolução

### 9.1 — `clinica/pdf_evolucao.php` (NOVO)

Seguir exatamente o mesmo padrão de `clinica/gerarProntuarioPdf.php` / `clinica/verProntuarioPdf.php`:
- Cabeçalho com logo da instituição (`tbConfig`)
- Dados do paciente
- Corpo com conteúdo da evolução (HTML parseado do TinyMCE)
- Rodapé com nome, especialidade e assinatura do profissional
- Validar `requireProfissionalSaude()` antes de gerar

---

## Etapa 10 — Registro de todas as novas rotas em `clinica/api/index.php`

```php
// Tipos de consulta — CRUD administrativo
$tip = new \App\Controllers\TiposConsultaController();
$router->get('/tipos-consulta', [$tip, 'index']);          // já existe
$router->post('/tipos-consulta', [$tip, 'store']);         // NOVO
$router->put('/tipos-consulta/{id}', [$tip, 'update']);    // NOVO
$router->post('/tipos-consulta/{id}/toggle', [$tip, 'toggle']); // NOVO

// Evoluções
$evol = new \App\Controllers\EvolucoesController();
$router->get('/evolucoes', [$evol, 'index']);               // NOVO
$router->get('/evolucoes/paciente/{id}', [$evol, 'paciente']); // NOVO
$router->get('/evolucoes/{id}', [$evol, 'show']);           // NOVO
$router->post('/evolucoes', [$evol, 'store']);              // NOVO
$router->put('/evolucoes/{id}', [$evol, 'update']);         // NOVO

// Anamnese roteiro (pediátrico)
$rot = new \App\Controllers\AnamneseRoteiroController();
$router->get('/anamnese-roteiro', [$rot, 'index']);         // NOVO
$router->get('/anamnese-roteiro/{id}', [$rot, 'show']);     // NOVO
$router->post('/anamnese-roteiro', [$rot, 'store']);        // NOVO
$router->put('/anamnese-roteiro/{id}', [$rot, 'update']);   // NOVO
```

---

## Lista completa de arquivos a criar ou modificar

### Criar (NOVO)
| Arquivo | Descrição |
|---|---|
| `clinica/zz_Suporte/migrations/008_add_licenca_administrativa_tbuser.sql` | Coluna tbUser |
| `clinica/zz_Suporte/migrations/009_tipos_consulta_crud_seed.sql` | Seed tipos de consulta |
| `clinica/zz_Suporte/migrations/010_create_tb_anamnese_roteiro.sql` | Tabela anamnese pediátrica |
| `clinica/zz_Suporte/migrations/011_create_tb_evolucao_clinica.sql` | Tabela de evoluções |
| `run_migrations_clinica.php` | Executor PHP de migrations |
| `clinica/api/app/Controllers/EvolucoesController.php` | CRUD de evoluções |
| `clinica/api/app/Controllers/AnamneseRoteiroController.php` | CRUD anamnese pediátrica |
| `clinica/src/pages/evolucoes.js` | SPA — tela de evoluções |
| `clinica/src/pages/configuracoes.js` | SPA — tela de configurações |
| `clinica/pdf_evolucao.php` | Gerador de PDF de evolução |

### Modificar (EXISTENTE)
| Arquivo | O que muda |
|---|---|
| `clinica/index.php` | Carregar `licenca_administrativa`; atualizar guarda; expandir bootstrap JS |
| `clinica/api/index.php` | Carregar `licenca_administrativa` na sessão; registrar novas rotas |
| `clinica/api/app/Middlewares/AuthMiddleware.php` | Adicionar `requireAcessoClinica()` e `isLicencaAdministrativa()` |
| `clinica/api/app/Controllers/TiposConsultaController.php` | Adicionar `store()`, `update()`, `toggle()` |
| `clinica/api/app/Controllers/ConsultasController.php` | Garantir `requireProfissionalSaude()` em `iniciarAtendimento()` |
| `clinica/api/app/Controllers/AnamneseController.php` | Manter inalterado (adulto) — apenas ajustes de rota se necessário |
| `clinica/index.html` | Adicionar botões "Evoluções" e "Configurações" na nav |
| `clinica/src/app.js` | Importar e rotear novos módulos; ocultar menus por role |
| `clinica/src/pages/agenda.js` | Modal de confirmação; interceptar iniciar atendimento; tipo de consulta dinâmico |
| `clinica/src/pages/pacientes.js` | Adicionar "Ver Evoluções"; ocultar ações clínicas para admin |
| `clinica/src/pages/atendimento.js` | Apresentar as duas opções de anamnese; integrar evoluções |
| `app/controllers/ColaboradorFlow.php` | Validar permissão admin antes de salvar flags clínicos |
| `app/views/colaborador/formColaborador.php` | UI "Habilitar para App Clínica" com as duas opções |
| `app/models/colaborador/colaboradorModel.php` | Incluir `licenca_administrativa` em `create()` e `update()` |
| API principal `/api/v1/me` | Retornar `licenca_administrativa` no payload |

---

## Ordem de execução por etapas

1. **Banco de dados** — Rodar migrations 008, 009, 010, 011 via `run_migrations_clinica.php`
2. **Permissões backend** — `AuthMiddleware`, `clinica/index.php`, `clinica/api/index.php`, endpoint `/me` principal
3. **Colaborador** — `ColaboradorFlow.php`, `ColaboradorModel`, `formColaborador.php`
4. **Tipos de consulta** — `TiposConsultaController` + rotas + `configuracoes.js` + `index.html`
5. **Agenda: confirmação e iniciar atendimento** — `agenda.js` (2 fluxos)
6. **Anamnese roteiro** — `AnamneseRoteiroController` + rotas + UI no `atendimento.js`
7. **Evoluções** — `EvolucoesController` + `evolucoes.js` + `index.html` + `pacientes.js`
8. **PDF de evolução** — `pdf_evolucao.php`
9. **Verificação de mojibake e encoding UTF-8** — revisar todos os arquivos novos e alterados

---

## Cuidados obrigatórios

- Toda permissão é validada **no backend**, independente da UI.
- `requireProfissionalSaude()` permanece inalterado em todas as rotas clínicas sensíveis.
- `licenca_administrativa` é tratado como papel distinto — nunca herda permissão clínica.
- Nenhum dado da `tb_anamnese_psi` ou `tb_prontuario` é exposto a usuários com `licenca_administrativa`.
- `consulta_id` nullable nas tabelas de anamnese e evolução para suportar registros avulsos.
- Verificação de mojibake em todos os arquivos PHP modificados antes de commit.
- Imports em `app.js` com `?v=` atualizados a cada modificação de página JS.
