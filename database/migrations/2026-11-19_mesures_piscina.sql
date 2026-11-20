-- ===========================================================
-- Migració: paràmetres de l'aigua de piscina i tasques amb l'espai tancat
-- Data: 2026-11-19
-- 1. tasques_cataleg.mesura_piscina: la tasca és una mesura de paràmetres
--    de l'aigua. A la Vista Diària surt un botó que obre un formulari
--    (clor, pH, CO2, temperatures...) en lloc del «Fet». Es marca a mà.
-- 2. tasques_pla.fer_amb_espai_tancat: la tasca continua sortint encara
--    que el seu espai estigui inactiu (p. ex. cuidar la piscina exterior a
--    l'hivern).
-- 3. mesures_piscina: una fila per tasca (vas), dia i moment (inici de
--    jornada, 14 h, final), com el full de paràmetres. El clor i el pH es
--    mesuren a cada moment; la resta, un cop al dia.
-- Cal aplicar-la ABANS de desplegar el codi.
-- Idempotent: es pot executar més d'una vegada sense duplicar res.
-- ===========================================================

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tasques_cataleg'
      AND COLUMN_NAME = 'mesura_piscina'
);

SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE `tasques_cataleg` ADD COLUMN `mesura_piscina` TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''1 = mesura de paràmetres de l''''aigua (formulari en lloc del Fet)'' AFTER `tipus`',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tasques_pla'
      AND COLUMN_NAME = 'fer_amb_espai_tancat'
);

SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE `tasques_pla` ADD COLUMN `fer_amb_espai_tancat` TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''1 = surt encara que l''''espai estigui inactiu'' AFTER `en_curs`',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `mesures_piscina` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `instalacio_id` INT UNSIGNED NOT NULL,
    `tasca_pla_id` INT UNSIGNED NOT NULL,
    `data` DATE NOT NULL,
    `moment` ENUM('inici','migdia','final') NOT NULL COMMENT 'Inici jornada, manteniment 14 h o final de jornada',
    `usuari_id` INT UNSIGNED DEFAULT NULL,
    `clor_lliure` DECIMAL(6,2) DEFAULT NULL,
    `clor_total` DECIMAL(6,2) DEFAULT NULL,
    `clor_combinat` DECIMAL(6,2) DEFAULT NULL,
    `ph` DECIMAL(4,2) DEFAULT NULL,
    `co2_interior` DECIMAL(8,2) DEFAULT NULL,
    `co2_exterior` DECIMAL(8,2) DEFAULT NULL,
    `temp_aigua` DECIMAL(5,2) DEFAULT NULL,
    `temp_ambient` DECIMAL(5,2) DEFAULT NULL,
    `humitat` DECIMAL(5,2) DEFAULT NULL,
    `terbolesa` DECIMAL(6,2) DEFAULT NULL,
    `recirculacio` DECIMAL(14,2) DEFAULT NULL,
    `aigua_nova` DECIMAL(14,2) DEFAULT NULL,
    `observacions` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_mp_tasca_data_moment` (`tasca_pla_id`, `data`, `moment`),
    KEY `idx_mp_instalacio_data` (`instalacio_id`, `data`),
    KEY `idx_mp_usuari` (`usuari_id`),
    CONSTRAINT `fk_mp_instalacio` FOREIGN KEY (`instalacio_id`) REFERENCES `instalacions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_mp_tasca_pla` FOREIGN KEY (`tasca_pla_id`) REFERENCES `tasques_pla` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_mp_usuari` FOREIGN KEY (`usuari_id`) REFERENCES `usuaris` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
