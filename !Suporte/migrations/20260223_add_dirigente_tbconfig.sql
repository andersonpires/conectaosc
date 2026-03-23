SET @schema_name = DATABASE();

SET @col_id_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tbConfig'
      AND COLUMN_NAME = 'IdColaboradorDirigente'
);
SET @sql_col_id = IF(
    @col_id_exists = 0,
    'ALTER TABLE tbConfig ADD COLUMN IdColaboradorDirigente INT NULL AFTER MetaAuthor',
    'SELECT 1'
);
PREPARE stmt FROM @sql_col_id;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_cargo_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tbConfig'
      AND COLUMN_NAME = 'CargoDirigente'
);
SET @sql_col_cargo = IF(
    @col_cargo_exists = 0,
    'ALTER TABLE tbConfig ADD COLUMN CargoDirigente VARCHAR(255) NULL AFTER IdColaboradorDirigente',
    'SELECT 1'
);
PREPARE stmt FROM @sql_col_cargo;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tbConfig'
      AND INDEX_NAME = 'idx_tbconfig_dirigente'
);
SET @sql_idx = IF(
    @idx_exists = 0,
    'CREATE INDEX idx_tbconfig_dirigente ON tbConfig (IdColaboradorDirigente)',
    'SELECT 1'
);
PREPARE stmt FROM @sql_idx;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tbConfig'
      AND CONSTRAINT_NAME = 'fk_tbconfig_dirigente'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);
SET @sql_fk = IF(
    @fk_exists = 0,
    'ALTER TABLE tbConfig ADD CONSTRAINT fk_tbconfig_dirigente FOREIGN KEY (IdColaboradorDirigente) REFERENCES tbUser(IdColaborador) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
