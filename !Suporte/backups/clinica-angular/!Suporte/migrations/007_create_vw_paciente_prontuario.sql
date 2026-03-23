-- Migration 007: Criar view vw_paciente_prontuario
-- Execute via: 007_run_create_vw_paciente_prontuario.php
-- Pacientes que possuem ao menos um prontuário (para filtro na página Prontuários)

CREATE OR REPLACE VIEW vw_paciente_prontuario AS
SELECT DISTINCT al.IdUsuario AS aluno_id, al.Nome AS paciente_nome
FROM tbAluno al
INNER JOIN tb_prontuario p ON p.aluno_id = al.IdUsuario
WHERE al.Habilitado = 1;
