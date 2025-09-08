-- =================================================================
-- Patch para crear la tabla `tipos_documento_identidad`,
-- sus SPs, y para modificar la tabla `auxiliares`.
-- Fecha: 2025-09-07
-- =================================================================

-- 1. Crear la tabla `tipos_documento_identidad`
CREATE TABLE IF NOT EXISTS `tipos_documento_identidad` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(10) NOT NULL UNIQUE,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` TEXT,
  `longitud` INT,
  `estado` BOOLEAN NOT NULL DEFAULT TRUE
);

-- 2. Crear los procedimientos almacenados para la nueva tabla

-- sp_create_tipo_documento_identidad
DROP PROCEDURE IF EXISTS sp_create_tipo_documento_identidad;
DELIMITER $$
CREATE PROCEDURE `sp_create_tipo_documento_identidad`(
    IN p_codigo VARCHAR(10),
    IN p_nombre VARCHAR(100),
    IN p_descripcion TEXT,
    IN p_longitud INT
)
BEGIN
    INSERT INTO tipos_documento_identidad (codigo, nombre, descripcion, longitud)
    VALUES (p_codigo, p_nombre, p_descripcion, p_longitud);
END$$
DELIMITER ;

-- sp_read_all_tipos_documento_identidad
DROP PROCEDURE IF EXISTS sp_read_all_tipos_documento_identidad;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_tipos_documento_identidad`(
    IN p_codigo VARCHAR(10),
    IN p_nombre VARCHAR(100)
)
BEGIN
    SELECT id, codigo, nombre, descripcion, longitud, estado FROM tipos_documento_identidad
    WHERE
        (p_codigo IS NULL OR p_codigo = '' OR codigo LIKE CONCAT('%', p_codigo, '%'))
        AND (p_nombre IS NULL OR p_nombre = '' OR nombre LIKE CONCAT('%', p_nombre, '%'));
END$$
DELIMITER ;

-- sp_read_tipo_documento_identidad_by_id
DROP PROCEDURE IF EXISTS sp_read_tipo_documento_identidad_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_tipo_documento_identidad_by_id`(IN p_id INT)
BEGIN
    SELECT id, codigo, nombre, descripcion, longitud, estado FROM tipos_documento_identidad WHERE id = p_id;
END$$
DELIMITER ;

-- sp_update_tipo_documento_identidad
DROP PROCEDURE IF EXISTS sp_update_tipo_documento_identidad;
DELIMITER $$
CREATE PROCEDURE `sp_update_tipo_documento_identidad`(
    IN p_id INT,
    IN p_nombre VARCHAR(100),
    IN p_descripcion TEXT,
    IN p_longitud INT,
    IN p_estado BOOLEAN
)
BEGIN
    UPDATE tipos_documento_identidad
    SET
        nombre = p_nombre,
        descripcion = p_descripcion,
        longitud = p_longitud,
        estado = p_estado
    WHERE id = p_id;
END$$
DELIMITER ;

-- sp_delete_tipo_documento_identidad
DROP PROCEDURE IF EXISTS sp_delete_tipo_documento_identidad;
DELIMITER $$
CREATE PROCEDURE `sp_delete_tipo_documento_identidad`(IN p_id INT)
BEGIN
    UPDATE tipos_documento_identidad SET estado = FALSE WHERE id = p_id;
END$$
DELIMITER ;

-- 3. Modificar la tabla `auxiliares` para usar la nueva tabla de tipos de documento
-- Este procedimiento es idempotente y seguro de ejecutar.
DELIMITER $$
CREATE PROCEDURE `patch_modify_auxiliares_for_identity_docs`()
BEGIN
    -- Verificar si la columna nueva ya existe. Si no, la creamos.
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'auxiliares' AND COLUMN_NAME = 'id_tipo_documento_identidad') THEN
        -- Añadir la nueva columna que será la FK. La hacemos nulleable para la transición.
        ALTER TABLE `auxiliares` ADD COLUMN `id_tipo_documento_identidad` INT NULL AFTER `id_tipo_auxiliar`;

        -- Aquí se haría la migración de datos. Por ahora, se deja en NULL.
        -- UPDATE auxiliares a JOIN tipos_documento_identidad tdi ON a.tipo_doc_identidad = tdi.codigo SET a.id_tipo_documento_identidad = tdi.id;

        -- Eliminar la columna ENUM original
        ALTER TABLE `auxiliares` DROP COLUMN `tipo_doc_identidad`;

        -- Añadir la clave foránea
        ALTER TABLE `auxiliares` ADD CONSTRAINT `fk_auxiliares_tipos_documento_identidad`
        FOREIGN KEY (`id_tipo_documento_identidad`) REFERENCES `tipos_documento_identidad`(`id`);
    END IF;
END$$
DELIMITER ;

-- Es recomendable ejecutar este procedimiento manualmente después de poblar la tabla `tipos_documento_identidad`.
CALL patch_modify_auxiliares_for_identity_docs();
DROP PROCEDURE patch_modify_auxiliares_for_identity_docs;


-- 4. SP para leer los tipos de documento de identidad en un dropdown
DROP PROCEDURE IF EXISTS sp_read_tipos_documento_identidad_for_dropdown;
DELIMITER $$
CREATE PROCEDURE `sp_read_tipos_documento_identidad_for_dropdown`()
BEGIN
    SELECT id, nombre FROM tipos_documento_identidad WHERE estado = TRUE ORDER BY nombre ASC;
END$$
DELIMITER ;

-- 5. SP para leer la longitud de un tipo de documento de identidad
DROP PROCEDURE IF EXISTS sp_read_tipo_documento_identidad_longitud_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_tipo_documento_identidad_longitud_by_id`(IN p_id INT)
BEGIN
    SELECT longitud FROM tipos_documento_identidad WHERE id = p_id;
END$$
DELIMITER ;
