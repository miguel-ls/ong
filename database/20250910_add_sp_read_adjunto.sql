-- =================================================================
-- Script para añadir el SP para leer un adjunto por su ID
-- =================================================================

DROP PROCEDURE IF EXISTS sp_read_adjunto_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_adjunto_by_id`(
    IN p_id_adjunto INT
)
BEGIN
    SELECT
        id,
        id_documento,
        nombre_original,
        nombre_almacenado,
        ruta_almacenamiento,
        tipo_mime,
        tamaño_bytes,
        fecha_subida
    FROM documento_adjuntos
    WHERE id = p_id_adjunto;
END$$
DELIMITER ;
