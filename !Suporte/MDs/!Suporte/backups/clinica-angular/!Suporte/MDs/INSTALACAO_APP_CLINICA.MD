# Guia de Instalação – App Clínica (ConectaOSC)

## Pré-requisitos

- PHP 8.1+
- MySQL com banco `mwtech63_matricula`
- Apache com mod_rewrite
- Usuário logado no ConectaOSC

## Passo 1: Executar migrations

**Ordem obrigatória:**

1. Faça login no ConectaOSC (ex.: `http://localhost/conectaosc/login.php` ou `https://iteva.com.br/conectaosc/login.php`).

2. Execute as migrations no navegador:

   - **Migration 001** – Adicionar `profissional_saude` em tbUser:  
     `http://localhost/conectaosc/clinica/!Suporte/migrations/001_run_alter_tbuser_profissional_saude.php`

   - **Migration 002** – Criar tabelas da clínica:  
     `http://localhost/conectaosc/clinica/!Suporte/migrations/002_run_create_tables_clinica.php`

   - **Migration 003** – Seeds (especialidades e tipos de consulta):  
     `http://localhost/conectaosc/clinica/!Suporte/migrations/003_run_seeds_clinica.php`

## Passo 2: Configurar profissionais de saúde

1. Acesse **Usuários do Sistema** (formColaborador).
2. Ao cadastrar ou editar um colaborador, marque o checkbox **Profissional de saúde** para quem poderá:
   - iniciar atendimentos na agenda;
   - gerar e salvar prontuários com IA.

## Passo 3: Acessar o App Clínica

- **Desenvolvimento:** `http://localhost/conectaosc/clinica/`
- **Produção:** `https://iteva.com.br/conectaosc/clinica/`

O usuário precisa estar logado no ConectaOSC para acessar o App Clínica.

## Estrutura de pastas

```
/conectaosc/clinica/
  index.html          # Front-end SPA
  tailwind.config.js
  src/
    app.js
    pages/
    services/
  api/
    index.php          # Entrypoint da API
    .htaccess
    app/
      Core/
      Controllers/
      Middlewares/
  !Suporte/
    migrations/
    MDs/
```

## API REST

Base: `/conectaosc/clinica/api/`

Endpoints principais:

- `GET /pacientes`, `GET /pacientes/{id}`
- `GET /especialidades`, `GET /tipos-consulta`, `GET /profissionais`
- `POST /consultas`, `PUT /consultas/{id}`
- `POST /consultas/{id}/confirmacao`, `POST /consultas/{id}/cancelar`, `POST /consultas/{id}/iniciar-atendimento`
- `GET /agenda?view=day|week|month&date=YYYY-MM-DD`
- `GET /prontuarios`, `POST /prontuarios`, `PUT /prontuarios/{id}`, `DELETE /prontuarios/{id}`
- `POST /prontuarios/gerar-ia`, `GET /prontuarios/{id}/pdf`

## Integração com IA

A geração de prontuário com IA usa a chave em `/conectaosc/temp/openaikey.php`.

## Dúvidas

Consulte `project_rules.MD` e `Prompt.MD` na pasta `!Suporte`.
