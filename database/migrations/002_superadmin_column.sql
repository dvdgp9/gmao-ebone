-- ============================================================
-- GMAO E-BONE — Migració: Superadmin column on usuaris
-- El superadmin no depèn de usuari_instalacio.
-- Té accés global a totes les instal·lacions.
-- ============================================================

ALTER TABLE `usuaris` ADD COLUMN `is_superadmin` TINYINT(1) NOT NULL DEFAULT 0 AFTER `actiu`;

-- Marcar l'usuari admin com a superadmin (canvia l'email si cal)
UPDATE `usuaris` SET `is_superadmin` = 1 WHERE `email` = 'it@ebone.es';
