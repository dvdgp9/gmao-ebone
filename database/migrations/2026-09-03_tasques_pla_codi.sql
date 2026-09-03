-- ===========================================================
-- Migració: codi propi de cada fila del pla de manteniment
-- Data: 2026-09-03
-- Permet que una mateixa tasca tingui codis diferents segons torn/espai.
-- Idempotent: es pot executar més d'una vegada sense duplicar la columna.
-- ===========================================================

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tasques_pla'
      AND COLUMN_NAME = 'codi'
);

SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE `tasques_pla` ADD COLUMN `codi` VARCHAR(50) DEFAULT NULL AFTER `instalacio_id`',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
