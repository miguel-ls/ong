-- =================================================================
-- Script para actualizar el SP sp_read_all_documentos
-- Añade un parámetro de año para filtrar los resultados.
-- =================================================================

DROP PROCEDURE IF EXISTS sp_read_all_documentos;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_documentos`(
    IN p_anio INT,
    IN p_fecha_desde DATE,
    IN p_fecha_hasta DATE,
    IN p_id_tipo_documento INT,
    IN p_serie_numero VARCHAR(31),
    IN p_auxiliar VARCHAR(255),
    IN p_id_centro_costo INT,
    IN p_moneda VARCHAR(10),
    IN p_page_size INT,
    IN p_page_number INT
)
BEGIN
    SET @where_clause = 'WHERE 1=1';

    IF p_anio IS NOT NULL AND p_anio != '' THEN
        SET @where_clause = CONCAT(@where_clause, ' AND YEAR(d.fecha_emision) = ?');
    ELSE
        IF p_fecha_desde IS NOT NULL THEN
            SET @where_clause = CONCAT(@where_clause, ' AND d.fecha_emision >= ?');
        ELSE
            SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL');
        END IF;

        IF p_fecha_hasta IS NOT NULL THEN
            SET @where_clause = CONCAT(@where_clause, ' AND d.fecha_emision <= ?');
        ELSE
            SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL');
        END IF;
    END IF;

    IF p_id_tipo_documento IS NOT NULL AND p_id_tipo_documento != '' THEN SET @where_clause = CONCAT(@where_clause, ' AND d.id_tipo_documento = ?'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;
    IF p_serie_numero IS NOT NULL AND p_serie_numero != '' THEN SET @where_clause = CONCAT(@where_clause, ' AND CONCAT(d.serie_documento, ''-'', d.numero_documento) LIKE ?'); SET p_serie_numero = CONCAT('%', p_serie_numero, '%'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;
    IF p_auxiliar IS NOT NULL AND p_auxiliar != '' THEN SET @where_clause = CONCAT(@where_clause, ' AND a.razon_social_nombres LIKE ?'); SET p_auxiliar = CONCAT('%', p_auxiliar, '%'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;
    IF p_id_centro_costo IS NOT NULL AND p_id_centro_costo != '' THEN SET @where_clause = CONCAT(@where_clause, ' AND d.id IN (SELECT DISTINCT id_documento FROM documentos_detalle WHERE id_centro_costo = ?)'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;
    IF p_moneda IS NOT NULL AND p_moneda != '' THEN SET @where_clause = CONCAT(@where_clause, ' AND d.moneda = ?'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;

    -- Query para obtener el conteo total de registros filtrados
    SET @count_sql = CONCAT('SELECT COUNT(d.id) FROM documentos d JOIN tipos_documento td ON d.id_tipo_documento = td.id JOIN auxiliares a ON d.id_auxiliar = a.id ', @where_clause);
    PREPARE count_stmt FROM @count_sql;
    IF p_anio IS NOT NULL AND p_anio != '' THEN
        EXECUTE count_stmt USING p_anio, p_id_tipo_documento, p_serie_numero, p_auxiliar, p_id_centro_costo, p_moneda;
    ELSE
        EXECUTE count_stmt USING p_fecha_desde, p_fecha_hasta, p_id_tipo_documento, p_serie_numero, p_auxiliar, p_id_centro_costo, p_moneda;
    END IF;
    DEALLOCATE PREPARE count_stmt;

    -- Query para obtener los datos paginados
    SET @data_sql = CONCAT(
        'SELECT d.id, d.fecha_emision, td.nombre as tipo_documento, d.serie_documento, d.numero_documento, a.razon_social_nombres as auxiliar, cc.nombre as centro_costo, d.moneda, d.total, d.total_soles, d.total_dolares, COUNT(da.id) > 0 AS tiene_adjuntos ',
        'FROM documentos d ',
        'JOIN tipos_documento td ON d.id_tipo_documento = td.id ',
        'JOIN auxiliares a ON d.id_auxiliar = a.id ',
        'LEFT JOIN centros_costos cc ON d.id_centro_costo = cc.id ',
        'LEFT JOIN documento_adjuntos da ON d.id = da.id_documento ',
        @where_clause,
        ' GROUP BY d.id, d.fecha_emision, td.nombre, d.serie_documento, d.numero_documento, a.razon_social_nombres, cc.nombre, d.moneda, d.total, d.total_soles, d.total_dolares',
        ' ORDER BY d.fecha_emision DESC, d.id DESC LIMIT ? OFFSET ?'
    );
    PREPARE data_stmt FROM @data_sql;
    IF p_anio IS NOT NULL AND p_anio != '' THEN
        EXECUTE data_stmt USING p_anio, p_id_tipo_documento, p_serie_numero, p_auxiliar, p_id_centro_costo, p_moneda, p_page_size, ((p_page_number - 1) * p_page_size);
    ELSE
        EXECUTE data_stmt USING p_fecha_desde, p_fecha_hasta, p_id_tipo_documento, p_serie_numero, p_auxiliar, p_id_centro_costo, p_moneda, p_page_size, ((p_page_number - 1) * p_page_size);
    END IF;
    DEALLOCATE PREPARE data_stmt;
END$$
DELIMITER ;
