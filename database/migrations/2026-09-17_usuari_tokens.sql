-- ===========================================================
-- Migració: enllaços d'accés d'un sol ús (activació i canvi de contrasenya)
-- Data: 2026-09-17
-- Executar al servidor MySQL de producció.
-- No afecta dades existents. Només es guarda el SHA-256 del token, mai el token.
-- ===========================================================

CREATE TABLE IF NOT EXISTS `usuari_tokens` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuari_id` INT UNSIGNED NOT NULL,
    `token_hash` CHAR(64) NOT NULL,
    `tipus` ENUM('activacio', 'reset') NOT NULL DEFAULT 'activacio',
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME DEFAULT NULL,
    `created_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_usuari_tokens_hash` (`token_hash`),
    KEY `idx_usuari_tokens_usuari` (`usuari_id`),
    CONSTRAINT `fk_usuari_tokens_usuari` FOREIGN KEY (`usuari_id`) REFERENCES `usuaris` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_usuari_tokens_creador` FOREIGN KEY (`created_by`) REFERENCES `usuaris` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
