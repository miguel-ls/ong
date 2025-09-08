-- =================================================================
-- Patch para añadir el SP para leer todos los documentos
-- Fecha: 2025-09-07
-- =================================================================

-- Este script crea el procedimiento almacenado `sp_read_all_documentos`
-- para listar los documentos en la página principal de ingreso de documentos.

DROP PROCEDURE IF EXISTS sp_read_all_documentos;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_documentos`()
BEGIN
    SELECT
        d.id,
        d.fecha_emision,
        td.nombre as tipo_documento,
        d.serie_documento,
        d.numero_documento,
        a.razon_social_nombres as auxiliar,
        d.moneda,
        d.total
    FROM documentos d
    JOIN tipos_documento td ON d.id_tipo_documento = td.id
    JOIN auxiliares a ON d.id_auxiliar = a.id
    ORDER BY d.fecha_emision DESC, d.id DESC;
END$$
DELIMITER ;
