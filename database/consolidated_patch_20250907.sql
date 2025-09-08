-- =================================================================
-- PARCHE CONSOLIDADO DE BASE DE DATOS
-- Fecha: 2025-09-07
-- Autor: Jules AI
-- Descripción: Este script contiene todos los cambios de base de datos
-- realizados durante la sesión de desarrollo, consolidados en un
-- único archivo para facilitar la ejecución.
-- =================================================================


-- =================================================================
-- SECCIÓN 1: Arreglos para Autenticación de Dos Factores (2FA)
-- =================================================================
-- Desc: Añade las columnas necesarias para la funcionalidad de 2FA.

DELIMITER $$
CREATE PROCEDURE `add_2fa_columns_to_usuarios`()
BEGIN
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'secret_2fa'
    ) THEN
        ALTER TABLE `usuarios` ADD COLUMN `secret_2fa` VARCHAR(255) NULL AFTER `telefono`;
    END IF;
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'is_2fa_enabled'
    ) THEN
        ALTER TABLE `usuarios` ADD COLUMN `is_2fa_enabled` BOOLEAN NOT NULL DEFAULT FALSE AFTER `secret_2fa`;
    END IF;
END$$
DELIMITER ;
CALL add_2fa_columns_to_usuarios();
DROP PROCEDURE add_2fa_columns_to_usuarios;


-- =================================================================
-- SECCIÓN 2: Creación de Tablas para Módulo de Documentos
-- =================================================================
-- Desc: Crea la tabla `documentos_detalle`.

CREATE TABLE IF NOT EXISTS `documentos_detalle` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_documento` INT NOT NULL,
  `item` INT NOT NULL,
  `cantidad` DECIMAL(15, 4) NOT NULL,
  `descripcion` VARCHAR(255) NOT NULL,
  `id_concepto` INT NOT NULL,
  `precio_unitario` DECIMAL(15, 4) NOT NULL,
  `precio_total` DECIMAL(15, 2) NOT NULL,
  FOREIGN KEY (`id_documento`) REFERENCES `documentos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_concepto`) REFERENCES `conceptos`(`id`),
  UNIQUE KEY `idx_documento_item` (`id_documento`, `item`)
);


-- =================================================================
-- SECCIÓN 3: Corrección de Esquema de Tabla `documentos`
-- =================================================================
-- Desc: Elimina la columna `id_concepto` de `documentos` que era un error de diseño.

DELIMITER $$
CREATE PROCEDURE `remove_id_concepto_from_documentos`()
BEGIN
    IF EXISTS (
        SELECT * FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documentos' AND COLUMN_NAME = 'id_concepto'
    ) THEN
        IF EXISTS (
            SELECT * FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documentos' AND COLUMN_NAME = 'id_concepto' AND REFERENCED_TABLE_NAME = 'conceptos'
        ) THEN
            ALTER TABLE `documentos` DROP FOREIGN KEY `documentos_ibfk_6`;
        END IF;
        ALTER TABLE `documentos` DROP COLUMN `id_concepto`;
    END IF;
END$$
DELIMITER ;
CALL remove_id_concepto_from_documentos();
DROP PROCEDURE remove_id_concepto_from_documentos;


-- =================================================================
-- SECCIÓN 4: Añadir Columna `cuenta_contable` a `conceptos`
-- =================================================================
-- Desc: Añade el nuevo campo solicitado al módulo de conceptos.

DELIMITER $$
CREATE PROCEDURE `add_cuenta_contable_to_conceptos`()
BEGIN
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'conceptos' AND COLUMN_NAME = 'cuenta_contable'
    )
    THEN
        ALTER TABLE `conceptos` ADD COLUMN `cuenta_contable` VARCHAR(20) NULL AFTER `tipo`;
    END IF;
END$$
DELIMITER ;
CALL add_cuenta_contable_to_conceptos();
DROP PROCEDURE add_cuenta_contable_to_conceptos;


-- =================================================================
-- SECCIÓN 5: Procedimientos Almacenados (SPs) Optimizados y Nuevos
-- =================================================================
-- Desc: Contiene todos los SPs nuevos y actualizados.

-- SPs de Conceptos (actualizados)
DROP PROCEDURE IF EXISTS sp_create_concepto;
DELIMITER $$
CREATE PROCEDURE `sp_create_concepto`(IN p_codigo VARCHAR(20), IN p_nombre VARCHAR(255), IN p_descripcion TEXT, IN p_tipo ENUM('INGRESO', 'GASTO'), IN p_cuenta_contable VARCHAR(20))
BEGIN
    INSERT INTO conceptos (codigo, nombre, descripcion, tipo, cuenta_contable) VALUES (p_codigo, p_nombre, p_descripcion, p_tipo, p_cuenta_contable);
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_update_concepto;
DELIMITER $$
CREATE PROCEDURE `sp_update_concepto`(IN p_id INT, IN p_nombre VARCHAR(255), IN p_descripcion TEXT, IN p_tipo ENUM('INGRESO', 'GASTO'), IN p_estado BOOLEAN, IN p_cuenta_contable VARCHAR(20))
BEGIN
    UPDATE conceptos SET nombre = p_nombre, descripcion = p_descripcion, tipo = p_tipo, estado = p_estado, cuenta_contable = p_cuenta_contable WHERE id = p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_all_conceptos;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_conceptos`(IN p_codigo VARCHAR(20), IN p_nombre VARCHAR(255), IN p_tipo VARCHAR(10))
BEGIN
    SELECT id, codigo, nombre, descripcion, tipo, cuenta_contable, estado FROM conceptos WHERE (p_codigo IS NULL OR p_codigo = '' OR codigo LIKE CONCAT('%', p_codigo, '%')) AND (p_nombre IS NULL OR p_nombre = '' OR nombre LIKE CONCAT('%', p_nombre, '%')) AND (p_tipo IS NULL OR p_tipo = '' OR tipo = p_tipo);
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_concepto_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_concepto_by_id`(IN p_id INT)
BEGIN
    SELECT id, codigo, nombre, descripcion, tipo, estado, cuenta_contable FROM conceptos WHERE id = p_id;
END$$
DELIMITER ;


-- SPs para Dropdowns
DROP PROCEDURE IF EXISTS sp_read_proyectos_for_dropdown;
DELIMITER $$
CREATE PROCEDURE `sp_read_proyectos_for_dropdown`()
BEGIN
    SELECT id, nombre FROM proyectos WHERE estado = TRUE ORDER BY nombre ASC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_tipos_auxiliar_for_dropdown;
DELIMITER $$
CREATE PROCEDURE `sp_read_tipos_auxiliar_for_dropdown`()
BEGIN
    SELECT id, nombre FROM tipos_auxiliar WHERE estado = TRUE ORDER BY nombre ASC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_tipos_documento_for_dropdown;
DELIMITER $$
CREATE PROCEDURE `sp_read_tipos_documento_for_dropdown`()
BEGIN
    SELECT id, nombre FROM tipos_documento WHERE estado = TRUE ORDER BY nombre ASC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_auxiliares_for_dropdown;
DELIMITER $$
CREATE PROCEDURE `sp_read_auxiliares_for_dropdown`()
BEGIN
    SELECT id, razon_social_nombres as nombre FROM auxiliares WHERE estado = TRUE ORDER BY razon_social_nombres ASC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_conceptos_for_dropdown;
DELIMITER $$
CREATE PROCEDURE `sp_read_conceptos_for_dropdown`()
BEGIN
    SELECT id, nombre, tipo FROM conceptos WHERE estado = TRUE ORDER BY nombre ASC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_centros_costos_for_dropdown;
DELIMITER $$
CREATE PROCEDURE `sp_read_centros_costos_for_dropdown`()
BEGIN
    SELECT id, nombre FROM centros_costos WHERE estado = TRUE ORDER BY nombre ASC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_sub_proyectos_by_proyecto_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_sub_proyectos_by_proyecto_id`(IN p_id_proyecto INT)
BEGIN
    SELECT id, nombre FROM sub_proyectos WHERE estado = TRUE AND id_proyecto = p_id_proyecto ORDER BY nombre ASC;
END$$
DELIMITER ;


-- SPs para Módulo de Documentos
DROP PROCEDURE IF EXISTS sp_read_all_documentos;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_documentos`()
BEGIN
    SELECT d.id, d.fecha_emision, td.nombre as tipo_documento, d.serie_documento, d.numero_documento, a.razon_social_nombres as auxiliar, d.moneda, d.total FROM documentos d JOIN tipos_documento td ON d.id_tipo_documento = td.id JOIN auxiliares a ON d.id_auxiliar = a.id ORDER BY d.fecha_emision DESC, d.id DESC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_create_documento_header;
DELIMITER $$
CREATE PROCEDURE `sp_create_documento_header`(IN p_id_tipo_documento INT, IN p_id_proyecto INT, IN p_id_sub_proyecto INT, IN p_id_centro_costo INT, IN p_id_auxiliar INT, IN p_id_usuario_registro INT, IN p_serie_documento VARCHAR(10), IN p_numero_documento VARCHAR(20), IN p_fecha_emision DATE, IN p_moneda ENUM('SOLES', 'DOLARES'), IN p_tipo_cambio DECIMAL(10, 4), IN p_subtotal DECIMAL(15, 2), IN p_igv DECIMAL(15, 2), IN p_total DECIMAL(15, 2), IN p_glosa TEXT, OUT p_new_id INT)
BEGIN
    INSERT INTO documentos (id_tipo_documento, id_proyecto, id_sub_proyecto, id_centro_costo, id_auxiliar, id_usuario_registro, serie_documento, numero_documento, fecha_emision, moneda, tipo_cambio, subtotal, igv, total, glosa) VALUES (p_id_tipo_documento, p_id_proyecto, p_id_sub_proyecto, p_id_centro_costo, p_id_auxiliar, p_id_usuario_registro, p_serie_documento, p_numero_documento, p_fecha_emision, p_moneda, p_tipo_cambio, p_subtotal, p_igv, p_total, p_glosa);
    SET p_new_id = LAST_INSERT_ID();
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_create_documento_detalle;
DELIMITER $$
CREATE PROCEDURE `sp_create_documento_detalle`(IN p_id_documento INT, IN p_item INT, IN p_cantidad DECIMAL(15, 4), IN p_descripcion VARCHAR(255), IN p_id_concepto INT, IN p_precio_unitario DECIMAL(15, 4), IN p_precio_total DECIMAL(15, 2))
BEGIN
    INSERT INTO documentos_detalle (id_documento, item, cantidad, descripcion, id_concepto, precio_unitario, precio_total) VALUES (p_id_documento, p_item, p_cantidad, p_descripcion, p_id_concepto, p_precio_unitario, p_precio_total);
END$$
DELIMITER ;
