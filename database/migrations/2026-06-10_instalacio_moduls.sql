-- ===========================================================
-- Migració: columna moduls a instalacions (onboarding modular)
-- Data: 2026-06-10
-- NULL = tots els mòduls actius (compatibilitat amb instal·lacions existents)
-- JSON array = només els mòduls llistats, p.ex. ["espais","torns"]
-- El pla de tasques sempre està actiu i no apareix a la llista.
-- ===========================================================

ALTER TABLE `instalacions`
    ADD COLUMN `moduls` JSON DEFAULT NULL COMMENT 'NULL = tots; ["espais","torns","equips"]' AFTER `activa`;
