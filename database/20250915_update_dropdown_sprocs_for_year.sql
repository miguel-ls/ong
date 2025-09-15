-- =================================================================
-- FECHA: 2025-09-15
-- AUTOR: Jules AI
-- DESCRIPCIÓN: Actualiza los SPs para los desplegables de conceptos
-- y centros de costo para que acepten un filtro de año.
-- =================================================================

-- 1. Actualizar sp_read_conceptos_for_dropdown
-- =================================================================
DROP PROCEDURE IF EXISTS sp_read_conceptos_for_dropdown;
DELIMITER $$
CREATE PROCEDURE `sp_read_conceptos_for_dropdown`(IN p_año INT)
BEGIN
    SELECT id, nombre, tipo
    FROM conceptos
    WHERE estado = TRUE AND (año = p_año OR año IS NULL)
    ORDER BY nombre ASC;
END$$
DELIMITER ;

-- 2. Actualizar sp_read_centros_costos_for_dropdown
-- =================================================================
DROP PROCEDURE IF EXISTS sp_read_centros_costos_for_dropdown;
DELIMITER $$
CREATE PROCEDURE `sp_read_centros_costos_for_dropdown`(IN p_año INT)
BEGIN
    SELECT id, nombre
    FROM centros_costos
    WHERE estado = TRUE AND (año = p_año OR año IS NULL)
    ORDER BY nombre ASC;
END$$
DELIMITER ;
