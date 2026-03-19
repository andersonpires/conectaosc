-- Migration 004: Adicionar especialidade_id em tbUser (para profissionais de saúde)
-- Execute via: 004_run_alter_tbuser_especialidade.php

-- Adicionar coluna especialidade_id (nullable - apenas para profissionais)
ALTER TABLE tbUser ADD COLUMN especialidade_id INT(11) NULL DEFAULT NULL AFTER profissional_saude;
ALTER TABLE tbUser ADD CONSTRAINT fk_user_especialidade FOREIGN KEY (especialidade_id) REFERENCES tb_especialidade(id) ON DELETE SET NULL;
CREATE INDEX idx_user_especialidade ON tbUser(especialidade_id);
