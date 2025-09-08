-- =================================================================
-- Patch para añadir el SP `sp_check_documento_duplicado`
-- Fecha: 2025-09-08
-- Autor: Jules AI
-- Descripción: Este script añade un nuevo procedimiento almacenado
-- para verificar si un documento ya existe.
-- =================================================================

DROP PROCEDURE IF EXISTS sp_check_documento_duplicado;
DELIMITER $$
CREATE PROCEDURE `sp_check_documento_duplicado`(
    IN p_id_tipo_documento INT,
    IN p_serie_documento VARCHAR(10),
    IN p_numero_documento VARCHAR(20),
    IN p_id_auxiliar INT
)
BEGIN
    SELECT COUNT(*) as duplicate_count
    FROM documentos
    WHERE
        id_tipo_documento = p_id_tipo_documento AND
        serie_documento = p_serie_documento AND
        numero_documento = p_numero_documento AND
        id_auxiliar = p_id_auxiliar;
END$$
DELIMITER ;
