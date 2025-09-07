-- =================================================================
-- Patch para optimizar la carga de dropdowns
-- Fecha: 2025-09-07
-- =================================================================

-- Procedimiento optimizado para obtener proyectos para dropdowns.
-- Selecciona solo las columnas necesarias (id, nombre) y solo registros activos.
DROP PROCEDURE IF EXISTS sp_read_proyectos_for_dropdown;
DELIMITER $$
CREATE PROCEDURE `sp_read_proyectos_for_dropdown`()
BEGIN
    SELECT id, nombre FROM proyectos WHERE estado = TRUE ORDER BY nombre ASC;
END$$
DELIMITER ;

-- Procedimiento optimizado para obtener tipos de auxiliar para dropdowns.
-- Selecciona solo las columnas necesarias (id, nombre) y solo registros activos.
DROP PROCEDURE IF EXISTS sp_read_tipos_auxiliar_for_dropdown;
DELIMITER $$
CREATE PROCEDURE `sp_read_tipos_auxiliar_for_dropdown`()
BEGIN
    SELECT id, nombre FROM tipos_auxiliar WHERE estado = TRUE ORDER BY nombre ASC;
END$$
DELIMITER ;
