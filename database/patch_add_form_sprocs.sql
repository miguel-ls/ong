-- =================================================================
-- Patch para añadir SPs para los dropdowns del formulario de documentos
-- Fecha: 2025-09-07
-- =================================================================

-- SP para obtener tipos de documento activos para un dropdown
DROP PROCEDURE IF EXISTS sp_read_tipos_documento_for_dropdown;
DELIMITER $$
CREATE PROCEDURE `sp_read_tipos_documento_for_dropdown`()
BEGIN
    SELECT id, nombre FROM tipos_documento WHERE estado = TRUE ORDER BY nombre ASC;
END$$
DELIMITER ;

-- SP para obtener auxiliares activos para un dropdown
DROP PROCEDURE IF EXISTS sp_read_auxiliares_for_dropdown;
DELIMITER $$
CREATE PROCEDURE `sp_read_auxiliares_for_dropdown`()
BEGIN
    SELECT id, razon_social_nombres as nombre FROM auxiliares WHERE estado = TRUE ORDER BY razon_social_nombres ASC;
END$$
DELIMITER ;

-- SP para obtener conceptos activos para un dropdown
DROP PROCEDURE IF EXISTS sp_read_conceptos_for_dropdown;
DELIMITER $$
CREATE PROCEDURE `sp_read_conceptos_for_dropdown`()
BEGIN
    SELECT id, nombre, tipo FROM conceptos WHERE estado = TRUE ORDER BY nombre ASC;
END$$
DELIMITER ;
