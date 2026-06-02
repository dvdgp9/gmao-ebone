-- 006_tasques_cataleg_per_instalacio.sql
-- Convierte el catálogo de tareas de global a per-instalación.
-- Todo el catálogo existente se asigna a la instalación 2 (la que tiene datos).

ALTER TABLE `tasques_cataleg`
    ADD COLUMN `instalacio_id` INT UNSIGNED NULL AFTER `id`;

UPDATE `tasques_cataleg` SET `instalacio_id` = 2;

ALTER TABLE `tasques_cataleg`
    MODIFY COLUMN `instalacio_id` INT UNSIGNED NOT NULL;

ALTER TABLE `tasques_cataleg`
    ADD KEY `idx_tc_instalacio` (`instalacio_id`),
    ADD CONSTRAINT `fk_tc_instalacio`
        FOREIGN KEY (`instalacio_id`) REFERENCES `instalacions` (`id`)
        ON DELETE CASCADE;
