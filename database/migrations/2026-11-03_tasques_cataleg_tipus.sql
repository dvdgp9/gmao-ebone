-- ===========================================================
-- Migració: tipus de tasca per ordenar la jornada de l'operari
-- Data: 2026-11-03
-- La Vista Diària separa les tasques en blocs: obertura, normatives,
-- preventiu i tancament. El tipus es posa sempre a mà: NULL = sense tipus
-- (la tasca surt a «Sense bloc»). Els blocs només surten a les instal·lacions
-- on alguna tasca del pla té tipus; la resta veu la llista de sempre.
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
    'ALTER TABLE `tasques_cataleg` ADD COLUMN `tipus` ENUM(''obertura'', ''normativa'', ''preventiu'', ''tancament'') DEFAULT NULL COMMENT ''NULL = sense tipus (surt a «Sense bloc»)'' AFTER `normativa_id`',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
