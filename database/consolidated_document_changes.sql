-- =================================================================
-- PARCHE CONSOLIDADO PARA LA DISTRIBUCIÓN DE CENTROS DE COSTO
-- Fecha: 2025-09-20
-- Autor: Jules AI
-- Descripción: Este script único contiene todas las modificaciones de la
-- base de datos para implementar la distribución de centros de costo,
-- incluyendo las correcciones de compatibilidad y de la vista de lista.
-- Ejecutar este único script aplica todos los cambios necesarios.
-- =================================================================

-- =================================================================
-- Paso 1: Modificaciones de Esquema
-- =================================================================

-- Crear la nueva tabla para la distribución de centros de costo.
CREATE TABLE IF NOT EXISTS `documento_detalle_distribucion` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_documento_detalle` INT NOT NULL,
  `id_centro_costo` INT NOT NULL,
  `porcentaje` DECIMAL(5, 2) NOT NULL,
  CONSTRAINT `fk_distribucion_detalle` FOREIGN KEY (`id_documento_detalle`) REFERENCES `documentos_detalle`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_distribucion_centro_costo` FOREIGN KEY (`id_centro_costo`) REFERENCES `centros_costos`(`id`),
  UNIQUE KEY `idx_distribucion_unica` (`id_documento_detalle`, `id_centro_costo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Eliminar la columna `id_centro_costo` de `documentos_detalle`.
DELIMITER $$
CREATE PROCEDURE `patch_remove_cc_from_detalle`()
BEGIN
    IF EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documentos_detalle' AND COLUMN_NAME = 'id_centro_costo') THEN
        IF EXISTS (SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documentos_detalle' AND CONSTRAINT_NAME = 'fk_detalle_centro_costo') THEN
            ALTER TABLE `documentos_detalle` DROP FOREIGN KEY `fk_detalle_centro_costo`;
        END IF;
        ALTER TABLE `documentos_detalle` DROP COLUMN `id_centro_costo`;
    END IF;
END$$
DELIMITER ;
CALL patch_remove_cc_from_detalle();
DROP PROCEDURE patch_remove_cc_from_detalle;


-- =================================================================
-- Paso 2: Actualización de Procedimientos Almacenados
-- =================================================================

-- SP para crear un detalle de documento (modificado para no incluir CC y devolver el nuevo ID).
DROP PROCEDURE IF EXISTS sp_create_documento_detalle;
DELIMITER $$
CREATE PROCEDURE `sp_create_documento_detalle`(
    IN p_id_documento INT,
    IN p_item INT,
    IN p_cantidad DECIMAL(15, 4),
    IN p_descripcion VARCHAR(255),
    IN p_id_concepto INT,
    IN p_precio_unitario DECIMAL(15, 4),
    IN p_precio_total DECIMAL(15, 2),
    IN p_total_soles DECIMAL(15, 2),
    IN p_total_dolares DECIMAL(15, 2),
    OUT p_new_detalle_id INT
)
BEGIN
    INSERT INTO documentos_detalle (
        id_documento, item, cantidad, descripcion, id_concepto,
        precio_unitario, precio_total, total_soles, total_dolares
    ) VALUES (
        p_id_documento, p_item, p_cantidad, p_descripcion, p_id_concepto,
        p_precio_unitario, p_precio_total, p_total_soles, p_total_dolares
    );
    SET p_new_detalle_id = LAST_INSERT_ID();
END$$
DELIMITER ;

-- Nuevo SP para insertar en la tabla de distribución.
DROP PROCEDURE IF EXISTS sp_create_documento_detalle_distribucion;
DELIMITER $$
CREATE PROCEDURE `sp_create_documento_detalle_distribucion`(
    IN p_id_documento_detalle INT,
    IN p_id_centro_costo INT,
    IN p_porcentaje DECIMAL(5, 2)
)
BEGIN
    INSERT INTO documento_detalle_distribucion (
        id_documento_detalle, id_centro_costo, porcentaje
    ) VALUES (
        p_id_documento_detalle, p_id_centro_costo, p_porcentaje
    );
END$$
DELIMITER ;

-- SP para leer los detalles de un documento (versión final con GROUP_CONCAT para compatibilidad).
DROP PROCEDURE IF EXISTS sp_read_documento_detalle_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_documento_detalle_by_id`(IN p_id_documento INT)
BEGIN
    SELECT
        dd.id, dd.id_documento, dd.item, dd.cantidad, dd.descripcion, dd.id_concepto,
        dd.precio_unitario, dd.precio_total, dd.total_soles, dd.total_dolares,
        (
            SELECT
                CASE
                    WHEN COUNT(ddd.id) > 0 THEN
                        CONCAT('[',
                            GROUP_CONCAT(
                                CONCAT('{"id_centro_costo":"', ddd.id_centro_costo, '",', '"porcentaje":"', ddd.porcentaje, '"}')
                                SEPARATOR ','
                            ),
                        ']')
                    ELSE NULL
                END
            FROM documento_detalle_distribucion ddd
            WHERE ddd.id_documento_detalle = dd.id
        ) AS distribucion
    FROM documentos_detalle dd
    WHERE dd.id_documento = p_id_documento
    ORDER BY dd.item ASC;
END$$
DELIMITER ;

-- SP para leer la lista de todos los documentos (versión final corregida).
DROP PROCEDURE IF EXISTS sp_read_all_documentos;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_documentos`(
    IN p_anio INT, IN p_fecha_desde DATE, IN p_fecha_hasta DATE, IN p_id_tipo_documento INT,
    IN p_serie_numero VARCHAR(31), IN p_auxiliar VARCHAR(255), IN p_id_centro_costo INT,
    IN p_moneda VARCHAR(10), IN p_page_size INT, IN p_page_number INT
)
BEGIN
    SET @where_clause = 'WHERE 1=1';
    IF p_anio IS NOT NULL AND p_anio != '' THEN SET @where_clause = CONCAT(@where_clause, ' AND YEAR(d.fecha_emision) = ?'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;
    IF p_fecha_desde IS NOT NULL THEN SET @where_clause = CONCAT(@where_clause, ' AND d.fecha_emision >= ?'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;
    IF p_fecha_hasta IS NOT NULL THEN SET @where_clause = CONCAT(@where_clause, ' AND d.fecha_emision <= ?'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;
    IF p_id_tipo_documento IS NOT NULL AND p_id_tipo_documento != '' THEN SET @where_clause = CONCAT(@where_clause, ' AND d.id_tipo_documento = ?'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;
    IF p_serie_numero IS NOT NULL AND p_serie_numero != '' THEN SET @where_clause = CONCAT(@where_clause, ' AND CONCAT(d.serie_documento, ''-'', d.numero_documento) LIKE ?'); SET p_serie_numero = CONCAT('%', p_serie_numero, '%'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;
    IF p_auxiliar IS NOT NULL AND p_auxiliar != '' THEN SET @where_clause = CONCAT(@where_clause, ' AND a.razon_social_nombres LIKE ?'); SET p_auxiliar = CONCAT('%', p_auxiliar, '%'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;

    IF p_id_centro_costo IS NOT NULL AND p_id_centro_costo != '' THEN
        SET @join_for_filter = ' JOIN documentos_detalle dd_filter ON d.id = dd_filter.id_documento JOIN documento_detalle_distribucion ddd_filter ON dd_filter.id = ddd_filter.id_documento_detalle ';
        SET @where_clause = CONCAT(@where_clause, ' AND ddd_filter.id_centro_costo = ? ');
    ELSE
        SET @join_for_filter = '';
        SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL');
    END IF;

    IF p_moneda IS NOT NULL AND p_moneda != '' THEN SET @where_clause = CONCAT(@where_clause, ' AND d.moneda = ?'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;

    SET @count_sql = CONCAT('SELECT COUNT(DISTINCT d.id) FROM documentos d JOIN tipos_documento td ON d.id_tipo_documento = td.id JOIN auxiliares a ON d.id_auxiliar = a.id ', @join_for_filter, @where_clause);
    PREPARE count_stmt FROM @count_sql;
    EXECUTE count_stmt USING p_anio, p_fecha_desde, p_fecha_hasta, p_id_tipo_documento, p_serie_numero, p_auxiliar, p_id_centro_costo, p_moneda;
    DEALLOCATE PREPARE count_stmt;

    SET @data_sql = CONCAT(
        'SELECT DISTINCT d.id, d.fecha_emision, td.nombre as tipo_documento, d.serie_documento, d.numero_documento, a.razon_social_nombres as auxiliar, d.moneda, d.total, d.total_soles, d.total_dolares, ',
        '(SELECT GROUP_CONCAT(DISTINCT cc.nombre SEPARATOR '', '') FROM documentos_detalle dd JOIN documento_detalle_distribucion ddd ON dd.id = ddd.id_documento_detalle JOIN centros_costos cc ON ddd.id_centro_costo = cc.id WHERE dd.id_documento = d.id) as centro_costo, ',
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
