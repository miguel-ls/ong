-- =================================================================
-- Patch para añadir el SP `sp_read_tipo_cambio_by_fecha`
-- Fecha: 2025-09-08
-- Autor: Jules AI
-- Descripción: Este script añade un nuevo procedimiento almacenado
-- para buscar un tipo de cambio por una fecha específica.
-- =================================================================

DROP PROCEDURE IF EXISTS sp_read_tipo_cambio_by_fecha;
DELIMITER $$
CREATE PROCEDURE `sp_read_tipo_cambio_by_fecha`(
    IN p_fecha DATE
)
BEGIN
    SELECT compra, venta
    FROM tipos_cambio
    WHERE fecha = p_fecha;
END$$
DELIMITER ;
