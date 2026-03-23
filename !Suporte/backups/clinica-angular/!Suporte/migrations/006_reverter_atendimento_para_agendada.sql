-- Migration 006: Reverter consultas em_atendimento para agendada
-- Use para corrigir consultas que ficaram travadas em "em_atendimento"
-- Execute via: 006_run_reverter_atendimento.php

-- Reverter APENAS consultas do dia 13/02/2026:
UPDATE tb_consulta SET status = 'agendada' WHERE data_consulta = '2026-02-13' AND status = 'em_atendimento';
UPDATE tb_agenda_clinica a
  JOIN tb_consulta c ON c.id = a.consulta_id
  SET a.status = 'agendada'
  WHERE c.data_consulta = '2026-02-13' AND a.status = 'em_atendimento';
