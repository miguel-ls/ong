-- =================================================================
-- Script para añadir el campo `observaciones` a la tabla `documentos`
-- y actualizar los SPs correspondientes.
-- =================================================================

-- 1. Añadir la nueva columna a la tabla de documentos
ALTER TABLE `documentos` ADD COLUMN `observaciones` TEXT NULL AFTER `glosa`;


-- 2. Actualizar SP para leer la cabecera del documento
DROP PROCEDURE IF EXISTS sp_read_documento_header_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_documento_header_by_id`(IN p_id INT)
BEGIN
    SELECT id, id_tipo_documento, id_proyecto, id_sub_proyecto, id_centro_costo, id_auxiliar, id_usuario_registro, serie_documento, numero_documento, fecha_emision, fecha_registro, moneda, tipo_cambio, subtotal, igv, total, glosa, observaciones, estado, total_soles, total_dolares
    FROM documentos
    WHERE id = p_id;
END$$
DELIMITER ;


-- 3. Actualizar SP para crear la cabecera del documento
DROP PROCEDURE IF EXISTS sp_create_documento_header;
DELIMITER $$
CREATE PROCEDURE `sp_create_documento_header`(
    IN p_id_tipo_documento INT,
    IN p_id_proyecto INT,
    IN p_id_sub_proyecto INT,
    IN p_id_centro_costo INT,
    IN p_id_auxiliar INT,
    IN p_id_usuario_registro INT,
    IN p_serie_documento VARCHAR(10),
    IN p_numero_documento VARCHAR(20),
    IN p_fecha_emision DATE,
    IN p_moneda ENUM('SOLES', 'DOLARES'),
    IN p_tipo_cambio DECIMAL(10, 4),
    IN p_subtotal DECIMAL(15, 2),
    IN p_igv DECIMAL(15, 2),
    IN p_total DECIMAL(15, 2),
    IN p_total_soles DECIMAL(15, 2),
    IN p_total_dolares DECIMAL(15, 2),
    IN p_glosa TEXT,
    IN p_observaciones TEXT,
    OUT p_new_id INT
)
BEGIN
    INSERT INTO documentos (id_tipo_documento, id_proyecto, id_sub_proyecto, id_centro_costo, id_auxiliar, id_usuario_registro, serie_documento, numero_documento, fecha_emision, moneda, tipo_cambio, subtotal, igv, total, total_soles, total_dolares, glosa, observaciones)
    VALUES (p_id_tipo_documento, p_id_proyecto, p_id_sub_proyecto, p_id_centro_costo, p_id_auxiliar, p_id_usuario_registro, p_serie_documento, p_numero_documento, p_fecha_emision, p_moneda, p_tipo_cambio, p_subtotal, p_igv, p_total, p_total_soles, p_total_dolares, p_glosa, p_observaciones);
    SET p_new_id = LAST_INSERT_ID();
END$$
DELIMITER ;


-- 4. Actualizar SP para modificar la cabecera del documento
DROP PROCEDURE IF EXISTS sp_update_documento_header;
DELIMITER $$
CREATE PROCEDURE `sp_update_documento_header`(
    IN p_id INT,
    IN p_id_tipo_documento INT,
    IN p_id_proyecto INT,
    IN p_id_sub_proyecto INT,
    IN p_id_centro_costo INT,
    IN p_id_auxiliar INT,
    IN p_serie_documento VARCHAR(10),
    IN p_numero_documento VARCHAR(20),
    IN p_fecha_emision DATE,
    IN p_moneda ENUM('SOLES', 'DOLARES'),
    IN p_tipo_cambio DECIMAL(10, 4),
    IN p_subtotal DECIMAL(15, 2),
    IN p_igv DECIMAL(15, 2),
    IN p_total DECIMAL(15, 2),
    IN p_total_soles DECIMAL(15, 2),
    IN p_total_dolares DECIMAL(15, 2),
    IN p_glosa TEXT,
    IN p_observaciones TEXT
)
BEGIN
    UPDATE documentos
    SET
        id_tipo_documento = p_id_tipo_documento,
        id_proyecto = p_id_proyecto,
        id_sub_proyecto = p_id_sub_proyecto,
        id_centro_costo = p_id_centro_costo,
        id_auxiliar = p_id_auxiliar,
        serie_documento = p_serie_documento,
        numero_documento = p_numero_documento,
        fecha_emision = p_fecha_emision,
        moneda = p_moneda,
        tipo_cambio = p_tipo_cambio,
        subtotal = p_subtotal,
        igv = p_igv,
        total = p_total,
        total_soles = p_total_soles,
        total_dolares = p_total_dolares,
        glosa = p_glosa,
        observaciones = p_observaciones
    WHERE id = p_id;
END$$
DELIMITER ;
