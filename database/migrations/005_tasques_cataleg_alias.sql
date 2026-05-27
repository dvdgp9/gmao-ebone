-- GMAO E-BONE — Migració: aliases de tasques del catàleg
-- Permet recordar equivalències confirmades entre nomenclatures diferents.

CREATE TABLE IF NOT EXISTS `tasques_cataleg_alias` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tasca_cataleg_id` INT UNSIGNED NOT NULL,
    `alias` VARCHAR(500) NOT NULL,
    `alias_normalitzat` VARCHAR(500) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tca_alias_normalitzat` (`alias_normalitzat`),
    KEY `idx_tca_tasca` (`tasca_cataleg_id`),
    CONSTRAINT `fk_tca_tasca` FOREIGN KEY (`tasca_cataleg_id`) REFERENCES `tasques_cataleg` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
