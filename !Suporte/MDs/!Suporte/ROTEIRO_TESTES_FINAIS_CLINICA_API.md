# Checklist de Testes Finais - Clinica e API

Data base: 2026-02-28

## Objetivo
- [ ] Validar integracao do app `clinica/` com `/api/v1`
- [ ] Validar permissao de acesso por perfil
- [ ] Validar funcionamento basico dos endpoints novos
- [ ] Validar ausencia de regressao visivel nos fluxos principais

## Pre-condicoes
- [ ] Usuario autenticado no `conectaosc3`
- [ ] Usuario com `profissional_saude = 1`
- [ ] Usuario sem permissao para Clinica
- [ ] Pelo menos um beneficiario ativo
- [ ] Pelo menos um prontuario existente com PDF e validacao disponiveis

## Registro da rodada
- Ambiente: [ ] Localhost [ ] Producao
- Data/hora:
- Responsavel:
- Usuario profissional testado:
- Usuario sem permissao testado:
- Evidencias:
- Observacoes:

## Bloco 1 - API minima
- [ ] `GET /api/v1/me` autenticado retorna `200`
- [ ] `GET /api/v1/me` retorna `nome_completo`
- [ ] `GET /api/v1/me` retorna `profissional_saude`
- [ ] `GET /api/v1/me` retorna `especialidade_id`

- [ ] `GET /api/v1/colaboradores/profissionais-saude` autenticado retorna `200`
- [ ] `GET /api/v1/colaboradores/profissionais-saude` retorna `id`
- [ ] `GET /api/v1/colaboradores/profissionais-saude` retorna `nome`
- [ ] `GET /api/v1/colaboradores/profissionais-saude` retorna `sobrenome`
- [ ] `GET /api/v1/colaboradores/profissionais-saude` retorna `nome_completo`
- [ ] `GET /api/v1/colaboradores/profissionais-saude` retorna `especialidade_id`
- [ ] `GET /api/v1/colaboradores/profissionais-saude` retorna `profissional_saude`
- [ ] `GET /api/v1/colaboradores/profissionais-saude` retorna `habilitado`

- [ ] `GET /api/v1/beneficiarios/busca` autenticado retorna `200`
- [ ] `GET /api/v1/beneficiarios/busca` retorna lista de beneficiarios

- [ ] `GET /api/v1/beneficiarios/{id}` autenticado retorna `200`
- [ ] `GET /api/v1/beneficiarios/{id}` retorna campos principais do beneficiario

- [ ] `GET /api/v1/colaboradores/{id}` autenticado retorna `200`
- [ ] `GET /api/v1/colaboradores/{id}` retorna `nome_completo`

- [ ] `GET /api/v1/colaboradores-publicos/{id}/nome` sem login retorna `200`
- [ ] `GET /api/v1/colaboradores-publicos/{id}/nome` retorna apenas `id`
- [ ] `GET /api/v1/colaboradores-publicos/{id}/nome` retorna apenas `nome_completo`

## Bloco 2 - Entrada na Clinica
- [ ] Usuario profissional acessa `/clinica/` sem erro
- [ ] A tela inicial da Clinica abre normalmente
- [ ] Nao aparece erro de sessao
- [ ] Nao aparece erro de perfil

- [ ] Usuario sem permissao e bloqueado ao acessar `/clinica/`
- [ ] A mensagem de restricao de perfil aparece corretamente

## Bloco 3 - API interna da Clinica
- [ ] `GET /clinica/api/profissionais` retorna `200`
- [ ] `GET /clinica/api/pacientes` retorna `200`
- [ ] `GET /clinica/api/pacientes/{id}` retorna `200`
- [ ] `GET /clinica/api/prontuarios/pacientes` retorna `200`
- [ ] `GET /clinica/api/agenda` retorna `200`

## Bloco 4 - Fluxo visual da Clinica
- [ ] Tela de pacientes carrega sem regressao visual
- [ ] Lista de pacientes aparece corretamente

- [ ] Tela de agenda carrega sem regressao visual
- [ ] Eventos da agenda aparecem corretamente
- [ ] Nome do profissional aparece corretamente na agenda

- [ ] Tela de prontuarios carrega sem regressao visual
- [ ] Um prontuario existente abre corretamente
- [ ] Nome do profissional aparece corretamente no prontuario

## Bloco 5 - PDF do prontuario
- [ ] O PDF do prontuario abre ou e gerado corretamente
- [ ] A logo aparece corretamente
- [ ] O nome do paciente aparece corretamente
- [ ] O nome do profissional aparece corretamente
- [ ] A data do atendimento aparece corretamente

## Bloco 6 - Validacao publica do prontuario
- [ ] A pagina publica de validacao abre em janela anonima
- [ ] O documento e localizado com o codigo informado
- [ ] O paciente exibido esta correto
- [ ] O profissional exibido esta correto
- [ ] A data exibida esta correta

## Resultado por ambiente

### Localhost
- Status: [ ] Aprovado [ ] Aprovado com ressalvas [ ] Reprovado
- Observacoes:

### Producao
- Status: [ ] Aprovado [ ] Aprovado com ressalvas [ ] Reprovado
- Observacoes:

## Criterio final de aprovacao
- [ ] Todos os endpoints acima respondem sem erro inesperado
- [ ] O `/clinica` abre apenas para perfil autorizado
- [ ] Pacientes, agenda e prontuarios carregam sem regressao visual evidente
- [ ] PDF e validacao publica exibem o nome correto do profissional
