# Anamnese Psicológica (tb_anamnese_psi)

## Migração

1. Execute a migration para criar a tabela:
   - Via navegador: `/conectaosc/clinica/!Suporte/migrations/005_run_create_tb_anamnese_psi.php`
   - Faça login no ConectaOSC antes de executar.

## Estrutura

A tabela `tb_anamnese_psi` armazena a anamnese psicológica vinculada a uma consulta (`consulta_id`), aluno (`aluno_id`) e profissional (`profissional_id`).

### Seções do formulário

- **3** – Contexto psicossocial e rede de apoio
- **4** – Aspectos clínicos e saúde mental
- **5** – Questões de saúde física e funcionalidade
- **6** – Funcionalidade cognitiva e executiva (respostas: Respondeu adequadamente / Não respondeu adequadamente)
- **7** – Risco e proteção psicossocial
- **8** – Aspectos emocionais e de enfrentamento
- **9** – Memória, cotidiano e expectativas (dificuldades de memória em dropdown)
- **10** – Observações e impressões gerais do profissional

### Itens removidos (não incluídos)

- Ponto de Referência
- Auxílios ou Benefícios
- Tem filhos? Quantos?
- Renda per capita
- Atenção – Bater palma na palavra "flor"

## API

- `GET /clinica/api/anamnese?consulta_id=X` – anamnese(s) da consulta
- `GET /clinica/api/anamnese?aluno_id=X` – anamneses do aluno
- `GET /clinica/api/anamnese/{id}` – detalhe
- `POST /clinica/api/anamnese` – criar (exige profissional_saude)
- `PUT /clinica/api/anamnese/{id}` – atualizar (autor ou superadmin)

## Uso na página Atendimento

1. Selecione uma consulta na lista.
2. Aba **Anamnese**: preencha o formulário e clique em "Salvar anamnese".
3. Aba **Prontuário IA**: preencha dados clínicos, gere com IA e salve o prontuário.
4. Os dados do paciente vêm da `tbAluno` e são exibidos ao selecionar o paciente.
