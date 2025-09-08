-- =================================================================
-- Patch para añadir la columna `longitud` a `tipos_documento`
-- y actualizar los procedimientos almacenados correspondientes.
-- Fecha: 2025-09-07
-- =================================================================

-- 1. Añadir la columna `longitud` a la tabla `tipos_documento`
ALTER TABLE `tipos_documento` ADD COLUMN `longitud` INT NULL AFTER `descripcion`;

-- 2. Actualizar los procedimientos almacenados

-- sp_create_tipo_documento
DROP PROCEDURE IF EXISTS sp_create_tipo_documento;
DELIMITER $$
CREATE PROCEDURE `sp_create_tipo_documento`(
    IN p_codigo VARCHAR(10),
    IN p_nombre VARCHAR(100),
    IN p_descripcion TEXT,
    IN p_longitud INT
)
BEGIN
    INSERT INTO tipos_documento (codigo, nombre, descripcion, longitud)
    VALUES (p_codigo, p_nombre, p_descripcion, p_longitud);
END$$
DELIMITER ;

-- sp_update_tipo_documento
DROP PROCEDURE IF EXISTS sp_update_tipo_documento;
DELIMITER $$
CREATE PROCEDURE `sp_update_tipo_documento`(
    IN p_id INT,
    IN p_nombre VARCHAR(100),
    IN p_descripcion TEXT,
    IN p_longitud INT,
    IN p_estado BOOLEAN
)
BEGIN
    UPDATE tipos_documento
    SET
        nombre = p_nombre,
        descripcion = p_descripcion,
        longitud = p_longitud,
        estado = p_estado
    WHERE id = p_id;
END$$
DELIMITER ;

-- sp_read_all_tipos_documento
DROP PROCEDURE IF EXISTS sp_read_all_tipos_documento;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_tipos_documento`(
    IN p_codigo VARCHAR(10),
    IN p_nombre VARCHAR(100)
)
BEGIN
    SELECT id, codigo, nombre, descripcion, longitud, estado FROM tipos_documento
    WHERE
        (p_codigo IS NULL OR p_codigo = '' OR codigo LIKE CONCAT('%', p_codigo, '%'))
        AND (p_nombre IS NULL OR p_nombre = '' OR nombre LIKE CONCAT('%', p_nombre, '%'));
END$$
DELIMITER ;

-- sp_read_tipo_documento_by_id
DROP PROCEDURE IF EXISTS sp_read_tipo_documento_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_tipo_documento_by_id`(IN p_id INT)
BEGIN
    SELECT id, codigo, nombre, descripcion, longitud, estado FROM tipos_documento WHERE id = p_id;
END$$
DELIMITER ;

-- sp_read_tipo_documento_longitud_by_id
DROP PROCEDURE IF EXISTS sp_read_tipo_documento_longitud_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_tipo_documento_longitud_by_id`(IN p_id INT)
BEGIN
    SELECT longitud FROM tipos_documento WHERE id = p_id;
END$$
DELIMITER ;
