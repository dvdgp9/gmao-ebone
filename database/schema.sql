-- ============================================================
-- GMAO E-BONE — Schema MySQL
-- Gestió de Manteniment Assistit per Ordinador
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------
-- MULTI-TENANCY: Instal·lacions
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `instalacions`;
CREATE TABLE `instalacions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom` VARCHAR(255) NOT NULL,
    `adreca` VARCHAR(500) DEFAULT NULL,
    `telefon` VARCHAR(50) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `logo` VARCHAR(255) DEFAULT NULL,
    `activa` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- AUTENTICACIÓ I PERMISOS
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `rols`;
CREATE TABLE `rols` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom` VARCHAR(50) NOT NULL,
    `descripcio` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_rols_nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `usuaris`;
CREATE TABLE `usuaris` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom` VARCHAR(100) NOT NULL,
    `cognoms` VARCHAR(200) DEFAULT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `actiu` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_usuaris_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `usuari_instalacio`;
CREATE TABLE `usuari_instalacio` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuari_id` INT UNSIGNED NOT NULL,
    `instalacio_id` INT UNSIGNED NOT NULL,
    `rol_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_usuari_instalacio` (`usuari_id`, `instalacio_id`),
    KEY `idx_ui_instalacio` (`instalacio_id`),
    KEY `idx_ui_rol` (`rol_id`),
    CONSTRAINT `fk_ui_usuari` FOREIGN KEY (`usuari_id`) REFERENCES `usuaris` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ui_instalacio` FOREIGN KEY (`instalacio_id`) REFERENCES `instalacions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ui_rol` FOREIGN KEY (`rol_id`) REFERENCES `rols` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- CATÀLEGS GLOBALS (compartits entre instal·lacions)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `sistemes`;
CREATE TABLE `sistemes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `codi` VARCHAR(20) NOT NULL,
    `nom` VARCHAR(255) NOT NULL,
    `descripcio` TEXT DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_sistemes_codi` (`codi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tipus_equip`;
CREATE TABLE `tipus_equip` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `codi` VARCHAR(20) NOT NULL,
    `nom` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tipus_equip_codi` (`codi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `normatives`;
CREATE TABLE `normatives` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `codi` VARCHAR(100) NOT NULL,
    `nom` VARCHAR(500) NOT NULL,
    `descripcio` TEXT DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_normatives_codi` (`codi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `periodicitats`;
CREATE TABLE `periodicitats` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom` VARCHAR(50) NOT NULL,
    `dies_interval` INT NOT NULL COMMENT 'Interval en dies entre execucions',
    `vegades_any` DECIMAL(10,4) DEFAULT NULL COMMENT 'Vegades per any',
    `ordre` INT NOT NULL DEFAULT 0 COMMENT 'Per ordenar de menys a més freqüent',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_periodicitats_nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `estats_equip`;
CREATE TABLE `estats_equip` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom` VARCHAR(50) NOT NULL,
    `descripcio` VARCHAR(255) DEFAULT NULL,
    `ordre` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_estats_nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- CATÀLEG DE TASQUES (global, reutilitzable)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `tasques_cataleg`;
CREATE TABLE `tasques_cataleg` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `codi` VARCHAR(50) DEFAULT NULL,
    `sistema_id` INT UNSIGNED DEFAULT NULL,
    `tipus_equip_id` INT UNSIGNED DEFAULT NULL,
    `nom` VARCHAR(500) NOT NULL,
    `descripcio` TEXT DEFAULT NULL,
    `periodicitat_normativa_id` INT UNSIGNED DEFAULT NULL,
    `normativa_id` INT UNSIGNED DEFAULT NULL,
    `empresa_responsable` VARCHAR(255) DEFAULT NULL,
    `activa` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tc_sistema` (`sistema_id`),
    KEY `idx_tc_tipus` (`tipus_equip_id`),
    KEY `idx_tc_periodicitat` (`periodicitat_normativa_id`),
    KEY `idx_tc_normativa` (`normativa_id`),
    CONSTRAINT `fk_tc_sistema` FOREIGN KEY (`sistema_id`) REFERENCES `sistemes` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_tc_tipus` FOREIGN KEY (`tipus_equip_id`) REFERENCES `tipus_equip` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_tc_periodicitat` FOREIGN KEY (`periodicitat_normativa_id`) REFERENCES `periodicitats` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_tc_normativa` FOREIGN KEY (`normativa_id`) REFERENCES `normatives` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- PER INSTAL·LACIÓ: Espais, Torns, Equips
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `espais`;
CREATE TABLE `espais` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `instalacio_id` INT UNSIGNED NOT NULL,
    `codi` VARCHAR(20) DEFAULT NULL,
    `nom` VARCHAR(255) NOT NULL,
    `planta` VARCHAR(50) DEFAULT NULL,
    `zona` VARCHAR(255) DEFAULT NULL,
    `actiu` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_espais_instalacio` (`instalacio_id`),
    CONSTRAINT `fk_espais_instalacio` FOREIGN KEY (`instalacio_id`) REFERENCES `instalacions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `torns`;
CREATE TABLE `torns` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `instalacio_id` INT UNSIGNED NOT NULL,
    `nom` VARCHAR(100) NOT NULL,
    `descripcio` VARCHAR(255) DEFAULT NULL,
    `dies_setmana` JSON DEFAULT NULL COMMENT '["dll","dm","dx","dj","dv","ds","dg"]',
    `actiu` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_torns_instalacio` (`instalacio_id`),
    CONSTRAINT `fk_torns_instalacio` FOREIGN KEY (`instalacio_id`) REFERENCES `instalacions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `equips`;
CREATE TABLE `equips` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `instalacio_id` INT UNSIGNED NOT NULL,
    `sistema_id` INT UNSIGNED DEFAULT NULL,
    `tipus_equip_id` INT UNSIGNED DEFAULT NULL,
    `numero` INT DEFAULT NULL,
    `nom_mn` VARCHAR(100) DEFAULT NULL COMMENT 'Nom identificador únic (ex: ACS-CAL-1)',
    `nom_equip` VARCHAR(255) NOT NULL,
    `notes` TEXT DEFAULT NULL,
    `model` VARCHAR(255) DEFAULT NULL,
    `dona_servei_a` VARCHAR(255) DEFAULT NULL,
    `equipament` VARCHAR(255) DEFAULT NULL,
    `espai_id` INT UNSIGNED DEFAULT NULL,
    `planta` VARCHAR(50) DEFAULT NULL,
    `empresa_mantenedora` VARCHAR(255) DEFAULT NULL,
    `data_installacio` DATE DEFAULT NULL,
    `fi_garantia` DATE DEFAULT NULL,
    `estat_id` INT UNSIGNED DEFAULT NULL,
    `actiu` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_equips_instalacio` (`instalacio_id`),
    KEY `idx_equips_sistema` (`sistema_id`),
    KEY `idx_equips_tipus` (`tipus_equip_id`),
    KEY `idx_equips_espai` (`espai_id`),
    KEY `idx_equips_estat` (`estat_id`),
    CONSTRAINT `fk_equips_instalacio` FOREIGN KEY (`instalacio_id`) REFERENCES `instalacions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_equips_sistema` FOREIGN KEY (`sistema_id`) REFERENCES `sistemes` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_equips_tipus` FOREIGN KEY (`tipus_equip_id`) REFERENCES `tipus_equip` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_equips_espai` FOREIGN KEY (`espai_id`) REFERENCES `espais` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_equips_estat` FOREIGN KEY (`estat_id`) REFERENCES `estats_equip` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- PLA DE MANTENIMENT (per instal·lació)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `tasques_pla`;
CREATE TABLE `tasques_pla` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `instalacio_id` INT UNSIGNED NOT NULL,
    `tasca_cataleg_id` INT UNSIGNED NOT NULL,
    `equip_id` INT UNSIGNED DEFAULT NULL,
    `espai_id` INT UNSIGNED DEFAULT NULL,
    `torn_id` INT UNSIGNED DEFAULT NULL,
    `periodicitat_id` INT UNSIGNED DEFAULT NULL,
    `periodicitat_normativa_id` INT UNSIGNED DEFAULT NULL,
    `normativa_id` INT UNSIGNED DEFAULT NULL,
    `observacions` TEXT DEFAULT NULL,
    `data_darrera_realitzacio` DATE DEFAULT NULL,
    `data_propera_realitzacio` DATE DEFAULT NULL,
    `data_darrera_no_realitzacio` DATE DEFAULT NULL,
    `en_curs` TINYINT(1) NOT NULL DEFAULT 1,
    `comentaris` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tp_instalacio` (`instalacio_id`),
    KEY `idx_tp_cataleg` (`tasca_cataleg_id`),
    KEY `idx_tp_equip` (`equip_id`),
    KEY `idx_tp_espai` (`espai_id`),
    KEY `idx_tp_torn` (`torn_id`),
    KEY `idx_tp_periodicitat` (`periodicitat_id`),
    KEY `idx_tp_propera` (`data_propera_realitzacio`),
    CONSTRAINT `fk_tp_instalacio` FOREIGN KEY (`instalacio_id`) REFERENCES `instalacions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tp_cataleg` FOREIGN KEY (`tasca_cataleg_id`) REFERENCES `tasques_cataleg` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_tp_equip` FOREIGN KEY (`equip_id`) REFERENCES `equips` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_tp_espai` FOREIGN KEY (`espai_id`) REFERENCES `espais` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_tp_torn` FOREIGN KEY (`torn_id`) REFERENCES `torns` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_tp_periodicitat` FOREIGN KEY (`periodicitat_id`) REFERENCES `periodicitats` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_tp_periodicitat_norm` FOREIGN KEY (`periodicitat_normativa_id`) REFERENCES `periodicitats` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_tp_normativa` FOREIGN KEY (`normativa_id`) REFERENCES `normatives` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- REGISTRE D'EXECUCIONS
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `registre_tasques`;
CREATE TABLE `registre_tasques` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `instalacio_id` INT UNSIGNED NOT NULL,
    `tasca_pla_id` INT UNSIGNED NOT NULL,
    `usuari_id` INT UNSIGNED DEFAULT NULL,
    `data_execucio` DATE NOT NULL,
    `realitzada` TINYINT(1) NOT NULL DEFAULT 1,
    `comentaris` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_rt_instalacio` (`instalacio_id`),
    KEY `idx_rt_tasca_pla` (`tasca_pla_id`),
    KEY `idx_rt_usuari` (`usuari_id`),
    KEY `idx_rt_data` (`data_execucio`),
    CONSTRAINT `fk_rt_instalacio` FOREIGN KEY (`instalacio_id`) REFERENCES `instalacions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rt_tasca_pla` FOREIGN KEY (`tasca_pla_id`) REFERENCES `tasques_pla` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rt_usuari` FOREIGN KEY (`usuari_id`) REFERENCES `usuaris` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- LOG D'AUDITORIA
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `audit_log`;
CREATE TABLE `audit_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuari_id` INT UNSIGNED DEFAULT NULL,
    `instalacio_id` INT UNSIGNED DEFAULT NULL,
    `accio` VARCHAR(50) NOT NULL COMMENT 'CREATE, UPDATE, DELETE, LOGIN, LOGOUT',
    `taula` VARCHAR(100) DEFAULT NULL,
    `registre_id` INT UNSIGNED DEFAULT NULL,
    `dades_anteriors` JSON DEFAULT NULL,
    `dades_noves` JSON DEFAULT NULL,
    `ip` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_usuari` (`usuari_id`),
    KEY `idx_audit_instalacio` (`instalacio_id`),
    KEY `idx_audit_data` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
