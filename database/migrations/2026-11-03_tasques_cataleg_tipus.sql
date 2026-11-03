-- ===========================================================
-- Migració: tipus de tasca per ordenar la jornada de l'operari
-- Data: 2026-11-03
-- La Vista Diària separa les tasques en blocs: obertura, normatives,
-- preventiu i tancament. NULL = automàtic (normativa si la tasca té
-- normativa associada i és diària; si no, preventiu), així les dades existents i
-- les importacions sense aquesta columna es classifiquen soles.
-- Idempotent: es pot executar més d'una vegada sense duplicar la columna.
-- ===========================================================

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tasques_cataleg'
      AND COLUMN_NAME = 'tipus'
);

SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE `tasques_cataleg` ADD COLUMN `tipus` ENUM(''obertura'', ''normativa'', ''preventiu'', ''tancament'') DEFAULT NULL COMMENT ''NULL = automàtic segons normativa'' AFTER `normativa_id`',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
