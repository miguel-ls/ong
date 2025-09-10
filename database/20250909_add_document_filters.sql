-- =================================================================
-- PARCHE PARA AÑADIR FILTROS Y PAGINACIÓN A LA VISTA DE DOCUMENTOS
-- Fecha: 2025-09-09
-- Autor: Jules AI
-- Descripción: Este script modifica el SP `sp_read_all_documentos`
-- para que acepte parámetros de filtrado y paginación.
-- =================================================================

DROP PROCEDURE IF EXISTS sp_read_all_documentos;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_documentos`(
    IN p_fecha_desde DATE,
    IN p_fecha_hasta DATE,
    IN p_id_tipo_documento INT,
    IN p_serie_numero VARCHAR(31),
    IN p_auxiliar VARCHAR(255),
    IN p_moneda VARCHAR(10),
    IN p_page_size INT,
    IN p_page_number INT
)
BEGIN
    -- Construir la cláusula WHERE dinámicamente para reutilizarla
    SET @where_clause = 'WHERE 1=1';
    IF p_fecha_desde IS NOT NULL THEN SET @where_clause = CONCAT(@where_clause, ' AND d.fecha_emision >= ?'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;
    IF p_fecha_hasta IS NOT NULL THEN SET @where_clause = CONCAT(@where_clause, ' AND d.fecha_emision <= ?'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;
    IF p_id_tipo_documento IS NOT NULL AND p_id_tipo_documento != '' THEN SET @where_clause = CONCAT(@where_clause, ' AND d.id_tipo_documento = ?'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;
    IF p_serie_numero IS NOT NULL AND p_serie_numero != '' THEN SET @where_clause = CONCAT(@where_clause, ' AND CONCAT(d.serie_documento, ''-'', d.numero_documento) LIKE ?'); SET p_serie_numero = CONCAT('%', p_serie_numero, '%'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;
    IF p_auxiliar IS NOT NULL AND p_auxiliar != '' THEN SET @where_clause = CONCAT(@where_clause, ' AND a.razon_social_nombres LIKE ?'); SET p_auxiliar = CONCAT('%', p_auxiliar, '%'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;
    IF p_moneda IS NOT NULL AND p_moneda != '' THEN SET @where_clause = CONCAT(@where_clause, ' AND d.moneda = ?'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;

    -- Query para obtener el conteo total de registros filtrados
    SET @count_sql = CONCAT('SELECT COUNT(d.id) FROM documentos d JOIN tipos_documento td ON d.id_tipo_documento = td.id JOIN auxiliares a ON d.id_auxiliar = a.id ', @where_clause);
    PREPARE count_stmt FROM @count_sql;
    EXECUTE count_stmt USING p_fecha_desde, p_fecha_hasta, p_id_tipo_documento, p_serie_numero, p_auxiliar, p_moneda;
    DEALLOCATE PREPARE count_stmt;

    -- Query para obtener los datos paginados
    SET @data_sql = CONCAT(
        'SELECT d.id, d.fecha_emision, td.nombre as tipo_documento, d.serie_documento, d.numero_documento, a.razon_social_nombres as auxiliar, d.moneda, d.total, d.total_soles, d.total_dolares ',
        'FROM documentos d JOIN tipos_documento td ON d.id_tipo_documento = td.id JOIN auxiliares a ON d.id_auxiliar = a.id ',
        @where_clause,
        ' ORDER BY d.fecha_emision DESC, d.id DESC LIMIT ? OFFSET ?'
    );
    PREPARE data_stmt FROM @data_sql;
    EXECUTE data_stmt USING p_fecha_desde, p_fecha_hasta, p_id_tipo_documento, p_serie_numero, p_auxiliar, p_moneda, p_page_size, ((p_page_number - 1) * p_page_size);
    DEALLOCATE PREPARE data_stmt;
END$$
DELIMITER ;
