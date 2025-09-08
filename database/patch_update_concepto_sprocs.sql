-- =================================================================
-- Patch para actualizar los SPs de Conceptos (Fase 2)
-- Fecha: 2025-09-07
-- =================================================================

-- 1. Actualizar sp_create_concepto para incluir cuenta_contable
DROP PROCEDURE IF EXISTS sp_create_concepto;
DELIMITER $$
CREATE PROCEDURE `sp_create_concepto`(
    IN p_codigo VARCHAR(20),
    IN p_nombre VARCHAR(255),
    IN p_descripcion TEXT,
    IN p_tipo ENUM('INGRESO', 'GASTO'),
    IN p_cuenta_contable VARCHAR(20)
)
BEGIN
    INSERT INTO conceptos (codigo, nombre, descripcion, tipo, cuenta_contable)
    VALUES (p_codigo, p_nombre, p_descripcion, p_tipo, p_cuenta_contable);
END$$
DELIMITER ;

-- 2. Actualizar sp_update_concepto para incluir cuenta_contable
DROP PROCEDURE IF EXISTS sp_update_concepto;
DELIMITER $$
CREATE PROCEDURE `sp_update_concepto`(
    IN p_id INT,
    IN p_nombre VARCHAR(255),
    IN p_descripcion TEXT,
    IN p_tipo ENUM('INGRESO', 'GASTO'),
    IN p_estado BOOLEAN,
    IN p_cuenta_contable VARCHAR(20)
)
BEGIN
    UPDATE conceptos
    SET
        nombre = p_nombre,
        descripcion = p_descripcion,
        tipo = p_tipo,
        estado = p_estado,
        cuenta_contable = p_cuenta_contable
    WHERE id = p_id;
END$$
DELIMITER ;

-- 3. Actualizar sp_read_all_conceptos para seleccionar cuenta_contable
DROP PROCEDURE IF EXISTS sp_read_all_conceptos;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_conceptos`(
    IN p_codigo VARCHAR(20),
    IN p_nombre VARCHAR(255),
    IN p_tipo VARCHAR(10)
)
BEGIN
    SELECT id, codigo, nombre, descripcion, tipo, cuenta_contable, estado FROM conceptos
    WHERE
        (p_codigo IS NULL OR p_codigo = '' OR codigo LIKE CONCAT('%', p_codigo, '%'))
        AND (p_nombre IS NULL OR p_nombre = '' OR nombre LIKE CONCAT('%', p_nombre, '%'))
        AND (p_tipo IS NULL OR p_tipo = '' OR tipo = p_tipo);
END$$
DELIMITER ;

-- 4. Actualizar sp_read_concepto_by_id para seleccionar cuenta_contable
DROP PROCEDURE IF EXISTS sp_read_concepto_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_concepto_by_id`(IN p_id INT)
BEGIN
    SELECT id, codigo, nombre, descripcion, tipo, estado, cuenta_contable FROM conceptos WHERE id = p_id;
END$$
DELIMITER ;
