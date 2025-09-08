-- =================================================================
-- Patch para añadir SP para dropdown de Centros de Costos
-- Fecha: 2025-09-07
-- =================================================================

DROP PROCEDURE IF EXISTS sp_read_centros_costos_for_dropdown;
DELIMITER $$
CREATE PROCEDURE `sp_read_centros_costos_for_dropdown`()
BEGIN
    SELECT id, nombre FROM centros_costos WHERE estado = TRUE ORDER BY nombre ASC;
END$$
DELIMITER ;
