-- =================================================================
-- FECHA: 2025-09-15
-- AUTOR: Jules AI
-- DESCRIPCIÓN: Corrige el SP sp_read_centros_costos_for_dropdown
-- para que use la columna correcta `anio` en lugar de `año`.
-- =================================================================

DROP PROCEDURE IF EXISTS sp_read_centros_costos_for_dropdown;
DELIMITER $$
CREATE PROCEDURE `sp_read_centros_costos_for_dropdown`(IN p_anio INT)
BEGIN
    SELECT id, nombre
    FROM centros_costos
    WHERE estado = TRUE AND (anio = p_anio OR anio IS NULL)
    ORDER BY nombre ASC;
END$$
DELIMITER ;
