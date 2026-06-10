-- ===========================================================
-- Migració: taula pivote usuari_torn (assignació usuaris ↔ torns)
-- Data: 2026-06-10
-- Executar al servidor MySQL de producció.
-- No afecta dades existents.
-- ===========================================================

CREATE TABLE IF NOT EXISTS `usuari_torn` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuari_id` INT UNSIGNED NOT NULL,
    `torn_id` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_usuari_torn` (`usuari_id`, `torn_id`),
    KEY `idx_ut_usuari` (`usuari_id`),
    KEY `idx_ut_torn` (`torn_id`),
    CONSTRAINT `fk_ut_usuari` FOREIGN KEY (`usuari_id`) REFERENCES `usuaris` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ut_torn` FOREIGN KEY (`torn_id`) REFERENCES `torns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
