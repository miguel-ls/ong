-- =================================================================
-- Patch para añadir SPs para guardar documentos
-- Fecha: 2025-09-07
-- =================================================================

-- SP para crear la cabecera de un documento y devolver el nuevo ID
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
    IN p_glosa TEXT,
    OUT p_new_id INT
)
BEGIN
    INSERT INTO documentos (id_tipo_documento, id_proyecto, id_sub_proyecto, id_centro_costo, id_auxiliar, id_usuario_registro, serie_documento, numero_documento, fecha_emision, moneda, tipo_cambio, subtotal, igv, total, glosa)
    VALUES (p_id_tipo_documento, p_id_proyecto, p_id_sub_proyecto, p_id_centro_costo, p_id_auxiliar, p_id_usuario_registro, p_serie_documento, p_numero_documento, p_fecha_emision, p_moneda, p_tipo_cambio, p_subtotal, p_igv, p_total, p_glosa);
    SET p_new_id = LAST_INSERT_ID();
END$$
DELIMITER ;


-- SP para crear una línea de detalle de un documento
DROP PROCEDURE IF EXISTS sp_create_documento_detalle;
DELIMITER $$
CREATE PROCEDURE `sp_create_documento_detalle`(
    IN p_id_documento INT,
    IN p_item INT,
    IN p_cantidad DECIMAL(15, 4),
    IN p_descripcion VARCHAR(255),
    IN p_id_concepto INT,
    IN p_precio_unitario DECIMAL(15, 4),
    IN p_precio_total DECIMAL(15, 2)
)
BEGIN
    INSERT INTO documentos_detalle (id_documento, item, cantidad, descripcion, id_concepto, precio_unitario, precio_total)
    VALUES (p_id_documento, p_item, p_cantidad, p_descripcion, p_id_concepto, p_precio_unitario, p_precio_total);
END$$
DELIMITER ;
