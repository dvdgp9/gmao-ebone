-- ============================================================
-- GMAO E-BONE - Migracio: incidencies de tasques
-- Bandeja de avisos para cap de manteniment, admin y superadmin.
-- ============================================================

CREATE TABLE IF NOT EXISTS `incidencies_tasques` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `instalacio_id` INT UNSIGNED NOT NULL,
    `tasca_pla_id` INT UNSIGNED NOT NULL,
    `registre_tasca_id` INT UNSIGNED DEFAULT NULL,
    `usuari_id` INT UNSIGNED DEFAULT NULL,
    `tipus` ENUM('feta_amb_incidencia', 'no_feta_per_incidencia') NOT NULL,
    `data_programada` DATE NOT NULL,
    `comentari` TEXT NOT NULL,
    `vista` TINYINT(1) NOT NULL DEFAULT 0,
    `vista_per` INT UNSIGNED DEFAULT NULL,
    `vista_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_it_instalacio_vista` (`instalacio_id`, `vista`),
    KEY `idx_it_tasca_pla` (`tasca_pla_id`),
    KEY `idx_it_registre` (`registre_tasca_id`),
    KEY `idx_it_usuari` (`usuari_id`),
    KEY `idx_it_vista_per` (`vista_per`),
    CONSTRAINT `fk_it_instalacio` FOREIGN KEY (`instalacio_id`) REFERENCES `instalacions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_it_tasca_pla` FOREIGN KEY (`tasca_pla_id`) REFERENCES `tasques_pla` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_it_registre` FOREIGN KEY (`registre_tasca_id`) REFERENCES `registre_tasques` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_it_usuari` FOREIGN KEY (`usuari_id`) REFERENCES `usuaris` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_it_vista_per` FOREIGN KEY (`vista_per`) REFERENCES `usuaris` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
