-- =================================================================
-- PARCHE PARA DISTRIBUCIÓN DE CENTROS DE COSTO
-- Fecha: 2025-09-20
-- Autor: Jules AI
-- Descripción: Modifica la estructura de la base de datos para permitir
-- que un único detalle de documento se distribuya entre múltiples
-- centros de costo con porcentajes específicos.
-- =================================================================

-- Paso 1: Crear la nueva tabla para la distribución de centros de costo.
-- Esta tabla almacenará la relación N-a-N entre el detalle del documento y los centros de costo.
CREATE TABLE IF NOT EXISTS `documento_detalle_distribucion` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_documento_detalle` INT NOT NULL,
  `id_centro_costo` INT NOT NULL,
  `porcentaje` DECIMAL(5, 2) NOT NULL,
  CONSTRAINT `fk_distribucion_detalle` FOREIGN KEY (`id_documento_detalle`) REFERENCES `documentos_detalle`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_distribucion_centro_costo` FOREIGN KEY (`id_centro_costo`) REFERENCES `centros_costos`(`id`),
  UNIQUE KEY `idx_distribucion_unica` (`id_documento_detalle`, `id_centro_costo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Paso 2: Eliminar la columna `id_centro_costo` de `documentos_detalle`.
-- Primero, verificamos si la columna existe antes de intentar eliminarla.
-- También eliminamos la clave foránea asociada si existe.
DELIMITER $$
CREATE PROCEDURE `patch_remove_cc_from_detalle`()
BEGIN
    IF EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documentos_detalle' AND COLUMN_NAME = 'id_centro_costo') THEN
        -- Verificar si existe la clave foránea antes de eliminarla
        IF EXISTS (SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documentos_detalle' AND CONSTRAINT_NAME = 'fk_detalle_centro_costo') THEN
            ALTER TABLE `documentos_detalle` DROP FOREIGN KEY `fk_detalle_centro_costo`;
        END IF;
        ALTER TABLE `documentos_detalle` DROP COLUMN `id_centro_costo`;
    END IF;
END$$
DELIMITER ;
CALL patch_remove_cc_from_detalle();
DROP PROCEDURE patch_remove_cc_from_detalle;


-- Paso 3: Modificar el SP para crear detalles de documento.
-- Se elimina el parámetro `p_id_centro_costo` ya que ahora se manejará en una tabla separada.
-- El ID del detalle insertado (`LAST_INSERT_ID()`) se usará en el backend para insertar en la tabla de distribución.
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

-- Paso 4: Crear un nuevo SP para insertar en la tabla de distribución.
-- Este SP se llamará en un bucle desde el backend para cada centro de costo asociado a un detalle.
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

-- Paso 5: Modificar el SP que lee los detalles del documento para incluir la distribución.
-- Se utiliza GROUP_CONCAT y JSON_ARRAYAGG para agregar la información de distribución
-- en un formato que el frontend pueda interpretar fácilmente.
DROP PROCEDURE IF EXISTS sp_read_documento_detalle_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_documento_detalle_by_id`(IN p_id_documento INT)
BEGIN
    SELECT
        dd.id,
        dd.id_documento,
        dd.item,
        dd.cantidad,
        dd.descripcion,
        dd.id_concepto,
        dd.precio_unitario,
        dd.precio_total,
        dd.total_soles,
        dd.total_dolares,
        (
            SELECT JSON_ARRAYAGG(
                JSON_OBJECT(
                    'id_centro_costo', ddd.id_centro_costo,
                    'porcentaje', ddd.porcentaje
                )
            )
            FROM documento_detalle_distribucion ddd
            WHERE ddd.id_documento_detalle = dd.id
        ) AS distribucion
    FROM
        documentos_detalle dd
    WHERE
        dd.id_documento = p_id_documento
    ORDER BY
        dd.item ASC;
END$$
DELIMITER ;

-- Paso 6: Modificar el SP `sp_delete_documento_detalle_by_id_documento`
-- Aunque ON DELETE CASCADE debería manejarlo, es buena práctica ser explícito
-- o asegurarse de que la FK está correctamente configurada.
-- En este caso, confiaremos en ON DELETE CASCADE definido en el Paso 1.
-- No se requiere ninguna acción aquí si la FK está bien definida.

-- Paso 7: Modificar el SP `sp_read_all_documentos` para que el filtro de centro de costo
-- ahora busque en la nueva tabla de distribución.
DROP PROCEDURE IF EXISTS sp_read_all_documentos;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_documentos`(
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
    IF p_fecha_desde IS NOT NULL THEN SET @where_clause = CONCAT(@where_clause, ' AND d.fecha_emision >= ?'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;
    IF p_fecha_hasta IS NOT NULL THEN SET @where_clause = CONCAT(@where_clause, ' AND d.fecha_emision <= ?'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;
    IF p_id_tipo_documento IS NOT NULL AND p_id_tipo_documento != '' THEN SET @where_clause = CONCAT(@where_clause, ' AND d.id_tipo_documento = ?'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;
    IF p_serie_numero IS NOT NULL AND p_serie_numero != '' THEN SET @where_clause = CONCAT(@where_clause, ' AND CONCAT(d.serie_documento, ''-'', d.numero_documento) LIKE ?'); SET p_serie_numero = CONCAT('%', p_serie_numero, '%'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;
    IF p_auxiliar IS NOT NULL AND p_auxiliar != '' THEN SET @where_clause = CONCAT(@where_clause, ' AND a.razon_social_nombres LIKE ?'); SET p_auxiliar = CONCAT('%', p_auxiliar, '%'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;

    -- Modificación clave: El filtro por centro de costo ahora debe unirse a la tabla de distribución.
    IF p_id_centro_costo IS NOT NULL AND p_id_centro_costo != '' THEN
        SET @where_clause = CONCAT(@where_clause, ' AND d.id IN (SELECT DISTINCT dd.id_documento FROM documentos_detalle dd JOIN documento_detalle_distribucion ddd ON dd.id = ddd.id_documento_detalle WHERE ddd.id_centro_costo = ?)');
    ELSE
        SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL');
    END IF;

    IF p_moneda IS NOT NULL AND p_moneda != '' THEN SET @where_clause = CONCAT(@where_clause, ' AND d.moneda = ?'); ELSE SET @where_clause = CONCAT(@where_clause, ' AND ? IS NULL'); END IF;

    -- Query para obtener el conteo total de registros filtrados
    SET @count_sql = CONCAT('SELECT COUNT(d.id) FROM documentos d JOIN tipos_documento td ON d.id_tipo_documento = td.id JOIN auxiliares a ON d.id_auxiliar = a.id ', @where_clause);
    PREPARE count_stmt FROM @count_sql;
    EXECUTE count_stmt USING p_fecha_desde, p_fecha_hasta, p_id_tipo_documento, p_serie_numero, p_auxiliar, p_id_centro_costo, p_moneda;
    DEALLOCATE PREPARE count_stmt;

    -- Query para obtener los datos paginados. Se mantiene el LEFT JOIN a centros_costos en el header por si se quiere mostrar el CC principal.
    -- O se puede eliminar si ya no tiene sentido. Lo mantendré por ahora.
    SET @data_sql = CONCAT(
        'SELECT d.id, d.fecha_emision, td.nombre as tipo_documento, d.serie_documento, d.numero_documento, a.razon_social_nombres as auxiliar, cc.nombre as centro_costo, d.moneda, d.total, d.total_soles, d.total_dolares ',
        'FROM documentos d JOIN tipos_documento td ON d.id_tipo_documento = td.id JOIN auxiliares a ON d.id_auxiliar = a.id LEFT JOIN centros_costos cc ON d.id_centro_costo = cc.id ',
        @where_clause,
        ' ORDER BY d.fecha_emision DESC, d.id DESC LIMIT ? OFFSET ?'
    );
    PREPARE data_stmt FROM @data_sql;
    EXECUTE data_stmt USING p_fecha_desde, p_fecha_hasta, p_id_tipo_documento, p_serie_numero, p_auxiliar, p_id_centro_costo, p_moneda, p_page_size, ((p_page_number - 1) * p_page_size);
    DEALLOCATE PREPARE data_stmt;
END$$
DELIMITER ;
