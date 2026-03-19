-- Migration 001: Adicionar profissional_saude em tbUser
-- App Clínica - ConectaOSC
-- Execute via: 001_run_alter_tbuser_profissional_saude.php

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Verificar se coluna já existe antes de adicionar
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tbUser'
    AND COLUMN_NAME = 'profissional_saude'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE tbUser ADD COLUMN profissional_saude TINYINT(1) NOT NULL DEFAULT 0 AFTER Email_confere',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Criar índice para profissionais de saúde
SET @idx_exists = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tbUser'
    AND INDEX_NAME = 'idx_profissional_saude'
);

SET @sql_idx = IF(@idx_exists = 0,
    'ALTER TABLE tbUser ADD INDEX idx_profissional_saude (profissional_saude)',
    'SELECT 1'
);

PREPARE stmt2 FROM @sql_idx;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

SET FOREIGN_KEY_CHECKS = 1;
