-- =================================================================
-- FECHA: 2025-09-14
-- AUTOR: Jules AI
-- DESCRIPCIÓN: Añade el campo `año` a la tabla `conceptos` y actualiza
-- los procedimientos almacenados relacionados.
-- =================================================================

-- 1. Añadir la columna `año` a la tabla `conceptos`
-- =================================================================
DELIMITER $$
CREATE PROCEDURE `patch_add_anio_to_conceptos`()
BEGIN
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'conceptos' AND COLUMN_NAME = 'año') THEN
        ALTER TABLE `conceptos` ADD COLUMN `año` INT(4) NULL AFTER `tipo`;
    END IF;
END$$
DELIMITER ;
CALL patch_add_anio_to_conceptos();
DROP PROCEDURE patch_add_anio_to_conceptos;


-- 2. Actualizar procedimientos almacenados de `conceptos`
-- =================================================================

-- sp_create_concepto
DROP PROCEDURE IF EXISTS sp_create_concepto;
DELIMITER $$
CREATE PROCEDURE `sp_create_concepto`(
    IN p_codigo VARCHAR(20),
    IN p_nombre VARCHAR(255),
    IN p_descripcion TEXT,
    IN p_tipo ENUM('INGRESO', 'GASTO'),
    IN p_año INT,
    IN p_cuenta_contable VARCHAR(20)
)
BEGIN
    INSERT INTO conceptos (codigo, nombre, descripcion, tipo, año, cuenta_contable)
    VALUES (p_codigo, p_nombre, p_descripcion, p_tipo, p_año, p_cuenta_contable);
END$$
DELIMITER ;

-- sp_update_concepto
DROP PROCEDURE IF EXISTS sp_update_concepto;
DELIMITER $$
CREATE PROCEDURE `sp_update_concepto`(
    IN p_id INT,
    IN p_nombre VARCHAR(255),
    IN p_descripcion TEXT,
    IN p_tipo ENUM('INGRESO', 'GASTO'),
    IN p_año INT,
    IN p_estado BOOLEAN,
    IN p_cuenta_contable VARCHAR(20)
)
BEGIN
    UPDATE conceptos
    SET
        nombre = p_nombre,
        descripcion = p_descripcion,
        tipo = p_tipo,
        año = p_año,
        estado = p_estado,
        cuenta_contable = p_cuenta_contable
    WHERE id = p_id;
END$$
DELIMITER ;

-- sp_read_all_conceptos
DROP PROCEDURE IF EXISTS sp_read_all_conceptos;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_conceptos`(
    IN p_año INT,
    IN p_codigo VARCHAR(20),
    IN p_nombre VARCHAR(255),
    IN p_tipo VARCHAR(10)
)
BEGIN
    SELECT
        id,
        codigo,
        nombre,
        descripcion,
        tipo,
        año,
        cuenta_contable,
        estado
    FROM conceptos
    WHERE
        (p_año IS NULL OR p_año = '' OR año = p_año)
        AND (p_codigo IS NULL OR p_codigo = '' OR codigo LIKE CONCAT('%', p_codigo, '%'))
        AND (p_nombre IS NULL OR p_nombre = '' OR nombre LIKE CONCAT('%', p_nombre, '%'))
        AND (p_tipo IS NULL OR p_tipo = '' OR tipo = p_tipo);
END$$
DELIMITER ;

-- sp_read_concepto_by_id
DROP PROCEDURE IF EXISTS sp_read_concepto_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_concepto_by_id`(IN p_id INT)
BEGIN
    SELECT
        id,
        codigo,
        nombre,
        descripcion,
        tipo,
        año,
        estado,
        cuenta_contable
    FROM conceptos
    WHERE id = p_id;
END$$
DELIMITER ;


-- 3. Crear procedimiento para obtener los años de los conceptos
-- =================================================================
DROP PROCEDURE IF EXISTS sp_get_conceptos_years;
DELIMITER $$
CREATE PROCEDURE `sp_get_conceptos_years`()
BEGIN
    SELECT DISTINCT año
    FROM conceptos
    WHERE año IS NOT NULL
    ORDER BY año DESC;
END$$
DELIMITER ;
