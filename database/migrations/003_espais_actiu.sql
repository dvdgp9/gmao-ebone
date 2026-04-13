-- ============================================================
-- GMAO E-BONE — Migració: estat actiu/inactiu dels espais
-- Permet tancar temporalment un espai sense eliminar-lo.
-- ============================================================

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'espais'
      AND COLUMN_NAME = 'actiu'
);

SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE `espais` ADD COLUMN `actiu` TINYINT(1) NOT NULL DEFAULT 1 AFTER `zona`',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
