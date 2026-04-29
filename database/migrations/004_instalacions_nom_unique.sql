-- ============================================================
-- GMAO E-BONE — Migració: índex únic sobre instalacions.nom
-- Evita crear dues instal·lacions amb el mateix nom.
-- IMPORTANT: si ja existeixen duplicats, aquesta migració fallarà
-- en crear l'índex. En aquest cas, cal renombrar manualment els
-- duplicats abans d'executar-la.
-- ============================================================

SET @index_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'instalacions'
      AND INDEX_NAME = 'uniq_instalacions_nom'
);

SET @sql := IF(
    @index_exists = 0,
    'ALTER TABLE `instalacions` ADD UNIQUE KEY `uniq_instalacions_nom` (`nom`)',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
