-- =================================================================
-- PARCHE PARA AÑADIR FILTROS A LA VISTA DE DOCUMENTOS
-- Fecha: 2025-09-09
-- Autor: Jules AI
-- Descripción: Este script modifica el SP `sp_read_all_documentos`
-- para que acepte parámetros de filtrado.
-- =================================================================

DROP PROCEDURE IF EXISTS sp_read_all_documentos;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_documentos`(
    IN p_fecha_desde DATE,
    IN p_fecha_hasta DATE,
    IN p_id_tipo_documento INT,
    IN p_serie_numero VARCHAR(31),
    IN p_auxiliar VARCHAR(255),
    IN p_id_centro_costo INT,
    IN p_moneda VARCHAR(10)
)
BEGIN
    SELECT
        d.id,
        d.fecha_emision,
        td.nombre as tipo_documento,
        d.serie_documento,
        d.numero_documento,
        a.razon_social_nombres as auxiliar,
        cc.nombre as centro_costo,
        d.moneda,
        d.total,
        d.total_soles,
        d.total_dolares
    FROM
        documentos d
    JOIN
        tipos_documento td ON d.id_tipo_documento = td.id
    JOIN
        auxiliares a ON d.id_auxiliar = a.id
    LEFT JOIN
        centros_costos cc ON d.id_centro_costo = cc.id
    WHERE
        (p_fecha_desde IS NULL OR d.fecha_emision >= p_fecha_desde)
        AND (p_fecha_hasta IS NULL OR d.fecha_emision <= p_fecha_hasta)
        AND (p_id_tipo_documento IS NULL OR p_id_tipo_documento = '' OR d.id_tipo_documento = p_id_tipo_documento)
        AND (p_serie_numero IS NULL OR p_serie_numero = '' OR CONCAT(d.serie_documento, '-', d.numero_documento) LIKE CONCAT('%', p_serie_numero, '%'))
        AND (p_auxiliar IS NULL OR p_auxiliar = '' OR a.razon_social_nombres LIKE CONCAT('%', p_auxiliar, '%'))
        AND (p_id_centro_costo IS NULL OR p_id_centro_costo = '' OR d.id_centro_costo = p_id_centro_costo)
        AND (p_moneda IS NULL OR p_moneda = '' OR d.moneda = p_moneda)
    ORDER BY
        d.fecha_emision DESC, d.id DESC;
END$$
DELIMITER ;
