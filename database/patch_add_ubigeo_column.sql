-- =================================================================
-- Script de Corrección Final para añadir la columna `ubigeo`.
-- Fecha: 2025-09-07
-- Autor: Jules AI
-- Descripción: Este script añade la columna `ubigeo` a la tabla
-- `auxiliares` si no existe.
-- =================================================================

DELIMITER $$
CREATE PROCEDURE `patch_add_ubigeo_to_auxiliares`()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'auxiliares' AND COLUMN_NAME = 'ubigeo') THEN
        ALTER TABLE `auxiliares` ADD COLUMN `ubigeo` VARCHAR(6) NULL AFTER `email`;
    END IF;
END$$
DELIMITER ;

-- Ejecutar el procedimiento y luego eliminarlo.
CALL `patch_add_ubigeo_to_auxiliares`();
DROP PROCEDURE `patch_add_ubigeo_to_auxiliares`;
