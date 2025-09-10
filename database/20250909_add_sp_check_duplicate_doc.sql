-- =================================================================
-- PARCHE PARA AÑADIR SP DE VALIDACIÓN DE DOCUMENTOS DUPLICADOS
-- Fecha: 2025-09-09
-- Autor: Jules AI
-- Descripción: Este script añade el procedimiento almacenado
-- `sp_check_documento_duplicado` para verificar la existencia de
-- un documento antes de su creación o actualización.
-- =================================================================

DROP PROCEDURE IF EXISTS sp_check_documento_duplicado;
DELIMITER $$
CREATE PROCEDURE `sp_check_documento_duplicado`(
    IN p_id_tipo_documento INT,
    IN p_serie_documento VARCHAR(10),
    IN p_numero_documento VARCHAR(20),
    IN p_id_auxiliar INT,
    IN p_id_documento INT -- Opcional: para excluir el documento actual en una actualización
)
BEGIN
    SELECT
        id,
        serie_documento,
        numero_documento
    FROM
        documentos
    WHERE
        id_tipo_documento = p_id_tipo_documento
        AND serie_documento = p_serie_documento
        AND numero_documento = p_numero_documento
        AND id_auxiliar = p_id_auxiliar
        AND (p_id_documento IS NULL OR id != p_id_documento)
    LIMIT 1;
END$$
DELIMITER ;
