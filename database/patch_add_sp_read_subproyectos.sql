-- =================================================================
-- Patch para añadir SP para leer sub-proyectos por proyecto
-- Fecha: 2025-09-07
-- =================================================================

-- Crear SP para leer sub-proyectos filtrados por proyecto_id
-- Utilizado para los dropdowns en cascada en el formulario de documentos.

DROP PROCEDURE IF EXISTS sp_read_sub_proyectos_by_proyecto_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_sub_proyectos_by_proyecto_id`(
    IN p_id_proyecto INT
)
BEGIN
    SELECT id, nombre
    FROM sub_proyectos
    WHERE
        estado = TRUE
        AND id_proyecto = p_id_proyecto
    ORDER BY nombre ASC;
END$$
DELIMITER ;
