-- =================================================================
-- Patch para la funcionalidad de Doble Moneda
-- Fecha: 2025-09-07
-- =================================================================

-- SECCIÓN 1: Alterar tablas para añadir columnas de totales en ambas monedas.

DELIMITER $$
CREATE PROCEDURE `add_currency_cols_to_detalle`()
BEGIN
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documentos_detalle' AND COLUMN_NAME = 'total_soles') THEN
        ALTER TABLE `documentos_detalle` ADD COLUMN `total_soles` DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER `precio_total`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documentos_detalle' AND COLUMN_NAME = 'total_dolares') THEN
        ALTER TABLE `documentos_detalle` ADD COLUMN `total_dolares` DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER `total_soles`;
    END IF;
END$$
DELIMITER ;
CALL add_currency_cols_to_detalle();
DROP PROCEDURE add_currency_cols_to_detalle;

DELIMITER $$
CREATE PROCEDURE `add_currency_cols_to_header`()
BEGIN
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documentos' AND COLUMN_NAME = 'total_soles') THEN
        ALTER TABLE `documentos` ADD COLUMN `total_soles` DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER `total`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documentos' AND COLUMN_NAME = 'total_dolares') THEN
        ALTER TABLE `documentos` ADD COLUMN `total_dolares` DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER `total_soles`;
    END IF;
END$$
DELIMITER ;
CALL add_currency_cols_to_header();
DROP PROCEDURE add_currency_cols_to_header;


-- SECCIÓN 2: Actualizar Procedimientos Almacenados.

-- Actualizar SP para crear detalle de documento
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
    IN p_total_dolares DECIMAL(15, 2)
)
BEGIN
    INSERT INTO documentos_detalle (id_documento, item, cantidad, descripcion, id_concepto, precio_unitario, precio_total, total_soles, total_dolares)
    VALUES (p_id_documento, p_item, p_cantidad, p_descripcion, p_id_concepto, p_precio_unitario, p_precio_total, p_total_soles, p_total_dolares);
END$$
DELIMITER ;

-- Actualizar SP para crear cabecera de documento
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
    OUT p_new_id INT
)
BEGIN
    INSERT INTO documentos (id_tipo_documento, id_proyecto, id_sub_proyecto, id_centro_costo, id_auxiliar, id_usuario_registro, serie_documento, numero_documento, fecha_emision, moneda, tipo_cambio, subtotal, igv, total, total_soles, total_dolares, glosa)
    VALUES (p_id_tipo_documento, p_id_proyecto, p_id_sub_proyecto, p_id_centro_costo, p_id_auxiliar, p_id_usuario_registro, p_serie_documento, p_numero_documento, p_fecha_emision, p_moneda, p_tipo_cambio, p_subtotal, p_igv, p_total, p_total_soles, p_total_dolares, p_glosa);
    SET p_new_id = LAST_INSERT_ID();
END$$
DELIMITER ;

-- Actualizar SP para leer todos los documentos
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
        d.total,
        d.total_soles,
        d.total_dolares
    FROM documentos d
    JOIN tipos_documento td ON d.id_tipo_documento = td.id
    JOIN auxiliares a ON d.id_auxiliar = a.id
    ORDER BY d.fecha_emision DESC, d.id DESC;
END$$
DELIMITER ;
