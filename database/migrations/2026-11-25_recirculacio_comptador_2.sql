-- ===========================================================
-- Migració: segon comptador de recirculació a les mesures de piscina
-- Data: 2026-11-25
-- Algunes piscines tenen dos comptadors de recirculació. S'afegeix
-- mesures_piscina.recirculacio_2 (opcional, un cop al dia com el primer).
-- Cal aplicar-la ABANS de desplegar el codi.
-- Idempotent: es pot executar més d'una vegada sense duplicar res.
-- ===========================================================

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'mesures_piscina'
      AND COLUMN_NAME = 'recirculacio_2'
);

SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE `mesures_piscina` ADD COLUMN `recirculacio_2` DECIMAL(14,2) DEFAULT NULL COMMENT ''Segon comptador de recirculació (opcional)'' AFTER `recirculacio`',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
