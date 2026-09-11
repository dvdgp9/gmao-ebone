-- ===========================================================
-- Migració: una tasca del pla pot estar assignada a diversos torns
-- Data: 2026-09-11
-- Manté tasques_pla.torn_id per compatibilitat amb importacions antigues.
-- Idempotent: es pot executar més d'una vegada.
-- ===========================================================

CREATE TABLE IF NOT EXISTS `tasca_pla_torn` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tasca_pla_id` INT UNSIGNED NOT NULL,
    `torn_id` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tasca_pla_torn` (`tasca_pla_id`, `torn_id`),
    KEY `idx_tpt_torn` (`torn_id`),
    CONSTRAINT `fk_tpt_tasca_pla` FOREIGN KEY (`tasca_pla_id`) REFERENCES `tasques_pla` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tpt_torn` FOREIGN KEY (`torn_id`) REFERENCES `torns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tasca_pla_torn` (`tasca_pla_id`, `torn_id`)
SELECT `id`, `torn_id`
FROM `tasques_pla`
WHERE `torn_id` IS NOT NULL;
