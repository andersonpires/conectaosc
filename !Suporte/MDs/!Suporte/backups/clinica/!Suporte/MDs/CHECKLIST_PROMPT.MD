# Checklist Prompt.MD — Itens Implementados

## Concluídos ✅

### Estrutura
- [x] Front-end em `/conectaosc/clinica/`
- [x] API em `/conectaosc/clinica/api/`
- [x] Pastas: assets, src/pages, services, types, utils, components
- [x] API: config, storage/logs

### Banco de Dados
- [x] Migration tbUser.profissional_saude
- [x] Tabelas: tb_especialidade, tb_tipo_consulta, tb_consulta, tb_agenda_clinica, tb_prontuario
- [x] Seeds: especialidades e tipos de consulta
- [x] formColaborador: checkbox Profissional de saúde

### API REST
- [x] Todos os endpoints mínimos
- [x] GET /prontuarios/{id}
- [x] GET /agenda/em-atendimento
- [x] Formato JSON padronizado
- [x] Autenticação e autorização
- [x] Rate limit básico

### Pacientes
- [x] Listagem ativos tbAluno
- [x] Busca por nome/contato/documento
- [x] Menu rápido Agendar consulta

### Agenda
- [x] Visual tipo calendário
- [x] Grade diária 07:00 às 22:00, linhas 30min
- [x] Navegação Hoje, <, >
- [x] Visões Dia, Semana, Mês
- [x] Blocos de consulta com nome do paciente
- [x] Modal: Editar, Solicitar confirmação, Cancelar, Iniciar atendimento
- [x] Novo agendamento

### Prontuário
- [x] Tabela decrescente por data/hora
- [x] Ações: Editar, PDF, Excluir
- [x] Permissões: Superadmin OU profissional emissor
- [x] Readonly e ocultar Salvar quando sem permissão
- [x] PDF com TCPDF e logo (logo.png/logoimpressao/logo_sys)

### Atendimento
- [x] Formulário clínico
- [x] Dados de tbAluno (via API)
- [x] Consultas em atendimento (seleção)
- [x] IA via openaikey.php
- [x] Textarea editável, salvar após revisão

### UI/UX
- [x] Mobile-first
- [x] Menu inferior fixo (4 módulos)
- [x] Tailwind config com design tokens
- [x] Bootstrap, Lucide Icons
- [x] Área mínima clicável 44x44

### Segurança
- [x] Prepared statements
- [x] Validação de payload
- [x] Sem stack trace em produção
- [x] Tratamento de erros JSON

---

## Observações
- TypeScript: projeto usa JS; migration para TS é opcional.
- CORS: restrito ao mesmo domínio.
- Guia de instalação em `!Suporte/MDs/INSTALACAO_APP_CLINICA.MD`.
