-- =================================================================
-- PARCHE PARA CORREGIR LA VISTA DE LISTA DE DOCUMENTOS
-- Fecha: 2025-09-20
-- Autor: Jules AI
-- Descripción: Este parche corrige el SP `sp_read_all_documentos` que
-- se rompió después de los cambios en la distribución de centros de costo.
-- Ahora muestra una lista de CCs y arregla la paginación y el filtrado.
-- También añade el campo `tiene_adjuntos` que faltaba.
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
    -- Construcción de la cláusula WHERE dinámica
    SET @where_clause = 'WHERE 1=1';
    IF p_anio IS NOT NULL AND p_anio != '' THEN SET @where_clause = CONCAT(@where_clause, ' AND YEAR(d.fecha_emision) = ?'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;
    IF p_fecha_desde IS NOT NULL THEN SET @where_clause = CONCAT(@where_clause, ' AND d.fecha_emision >= ?'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;
    IF p_fecha_hasta IS NOT NULL THEN SET @where_clause = CONCAT(@where_clause, ' AND d.fecha_emision <= ?'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;
    IF p_id_tipo_documento IS NOT NULL AND p_id_tipo_documento != '' THEN SET @where_clause = CONCAT(@where_clause, ' AND d.id_tipo_documento = ?'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;
    IF p_serie_numero IS NOT NULL AND p_serie_numero != '' THEN SET @where_clause = CONCAT(@where_clause, ' AND CONCAT(d.serie_documento, ''-'', d.numero_documento) LIKE ?'); SET p_serie_numero = CONCAT('%', p_serie_numero, '%'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;
    IF p_auxiliar IS NOT NULL AND p_auxiliar != '' THEN SET @where_clause = CONCAT(@where_clause, ' AND a.razon_social_nombres LIKE ?'); SET p_auxiliar = CONCAT('%', p_auxiliar, '%'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;

    -- Modificación clave: El filtro por centro de costo ahora debe unirse a la tabla de distribución.
    IF p_id_centro_costo IS NOT NULL AND p_id_centro_costo != '' THEN
        SET @join_for_filter = ' JOIN documentos_detalle dd_filter ON d.id = dd_filter.id_documento JOIN documento_detalle_distribucion ddd_filter ON dd_filter.id = ddd_filter.id_documento_detalle ';
        SET @where_clause = CONCAT(@where_clause, ' AND ddd_filter.id_centro_costo = ? ');
    ELSE
        SET @join_for_filter = '';
        SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL');
    END IF;

    IF p_moneda IS NOT NULL AND p_moneda != '' THEN SET @where_clause = CONCAT(@where_clause, ' AND d.moneda = ?'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;

    -- Query para obtener el conteo total de registros filtrados (corregido para incluir el JOIN del filtro)
    SET @count_sql = CONCAT('SELECT COUNT(DISTINCT d.id) FROM documentos d JOIN tipos_documento td ON d.id_tipo_documento = td.id JOIN auxiliares a ON d.id_auxiliar = a.id ', @join_for_filter, @where_clause);
    PREPARE count_stmt FROM @count_sql;
    EXECUTE count_stmt USING p_anio, p_fecha_desde, p_fecha_hasta, p_id_tipo_documento, p_serie_numero, p_auxiliar, p_id_centro_costo, p_moneda;
    DEALLOCATE PREPARE count_stmt;

    -- Query para obtener los datos paginados
    SET @data_sql = CONCAT(
        'SELECT DISTINCT d.id, d.fecha_emision, td.nombre as tipo_documento, d.serie_documento, d.numero_documento, a.razon_social_nombres as auxiliar, d.moneda, d.total, d.total_soles, d.total_dolares, ',
        -- Subconsulta para obtener la lista de centros de costo
        '(SELECT GROUP_CONCAT(DISTINCT cc.nombre SEPARATOR '', '') FROM documentos_detalle dd JOIN documento_detalle_distribucion ddd ON dd.id = ddd.id_documento_detalle JOIN centros_costos cc ON ddd.id_centro_costo = cc.id WHERE dd.id_documento = d.id) as centro_costo, ',
        -- Subconsulta para verificar si hay adjuntos
        '(EXISTS(SELECT 1 FROM documento_adjuntos WHERE id_documento = d.id)) as tiene_adjuntos ',
        'FROM documentos d JOIN tipos_documento td ON d.id_tipo_documento = td.id JOIN auxiliares a ON d.id_auxiliar = a.id ',
        @join_for_filter,
        @where_clause,
        ' ORDER BY d.fecha_emision DESC, d.id DESC LIMIT ? OFFSET ?'
    );
    PREPARE data_stmt FROM @data_sql;
    SET @offset = (p_page_number - 1) * p_page_size;
    EXECUTE data_stmt USING p_anio, p_fecha_desde, p_fecha_hasta, p_id_tipo_documento, p_serie_numero, p_auxiliar, p_id_centro_costo, p_moneda, p_page_size, @offset;
    DEALLOCATE PREPARE data_stmt;
END$$
DELIMITER ;
