-- =================================================================================
-- Add 'anio' column to 'centros_costos' table and update stored procedure
-- =================================================================================

-- Add the 'anio' column to store the year for each cost center.
-- It is placed after the 'id' for logical grouping.
ALTER TABLE `centros_costos`
ADD COLUMN `anio` SMALLINT(4) NOT NULL COMMENT 'Año del centro de costos' AFTER `id`;


-- Drop the existing procedure if it exists
DROP PROCEDURE IF EXISTS `sp_read_all_centros_costos`;


-- Create the updated stored procedure to include filtering by year.
DELIMITER $$
CREATE PROCEDURE `sp_read_all_centros_costos`(
    IN p_anio SMALLINT,
    IN p_codigo VARCHAR(20),
    IN p_nombre VARCHAR(255)
)
BEGIN
    SELECT
        id,
        anio,
        codigo,
        nombre,
        descripcion,
        estado
    FROM
        centros_costos
    WHERE
        -- Filter by year. If p_anio is NULL or 0, return all years.
        (p_anio IS NULL OR p_anio = 0 OR anio = p_anio)
        -- Filter by code. If p_codigo is NULL or empty, ignore this filter.
    AND (p_codigo IS NULL OR p_codigo = '' OR codigo LIKE CONCAT('%', p_codigo, '%'))
        -- Filter by name. If p_nombre is NULL or empty, ignore this filter.
    AND (p_nombre IS NULL OR p_nombre = '' OR nombre LIKE CONCAT('%', p_nombre, '%'))
    ORDER BY
        anio DESC, codigo ASC;
END$$
DELIMITER ;
