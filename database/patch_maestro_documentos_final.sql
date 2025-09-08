-- =================================================================
-- Script de Corrección y Feature Definitivo
-- Fecha: 2025-09-08
-- Autor: Jules AI
-- Descripción: Este script único y idempotente contiene todos los
-- cambios de base de datos para la funcionalidad de 'Tipos de
-- Documento de Identidad' y las correcciones asociadas.
-- Puede ejecutarse de forma segura en cualquier estado de la BD.
-- =================================================================

-- == PARTE 1: TABLA `tipos_documento_identidad` ===================

CREATE TABLE IF NOT EXISTS `tipos_documento_identidad` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(10) NOT NULL UNIQUE,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` TEXT,
  `longitud` INT,
  `estado` BOOLEAN NOT NULL DEFAULT TRUE
);

INSERT IGNORE INTO `tipos_documento_identidad` (`codigo`, `nombre`, `longitud`, `estado`) VALUES
('DNI', 'Documento Nacional de Identidad', 8, 1),
('RUC', 'Registro Único de Contribuyentes', 11, 1),
('CE', 'Carnet de Extranjería', 12, 1),
('PAS', 'Pasaporte', 12, 1),
('OTR', 'Otro', NULL, 1);

-- == PARTE 2: TABLA `auxiliares` ===================================

-- Añadir columna `ubigeo` si no existe
DELIMITER $$
CREATE PROCEDURE `patch_add_ubigeo_if_not_exists`()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'auxiliares' AND COLUMN_NAME = 'ubigeo') THEN
        ALTER TABLE `auxiliares` ADD COLUMN `ubigeo` VARCHAR(6) NULL AFTER `email`;
    END IF;
END$$
DELIMITER ;
CALL `patch_add_ubigeo_if_not_exists`();
DROP PROCEDURE `patch_add_ubigeo_if_not_exists`;

-- Alterar `auxiliares` para usar la FK, migrando datos si es posible.
DROP PROCEDURE IF EXISTS `patch_refactor_auxiliares_doc_type`;
DELIMITER $$
CREATE PROCEDURE `patch_refactor_auxiliares_doc_type`()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'auxiliares' AND COLUMN_NAME = 'id_tipo_documento_identidad') THEN
        ALTER TABLE `auxiliares` ADD COLUMN `id_tipo_documento_identidad` INT NULL AFTER `id_tipo_auxiliar`;
    END IF;

    IF EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'auxiliares' AND COLUMN_NAME = 'tipo_doc_identidad') THEN
        UPDATE `auxiliares` a
        LEFT JOIN `tipos_documento_identidad` tdi ON a.tipo_doc_identidad = tdi.codigo
        SET a.id_tipo_documento_identidad = tdi.id
        WHERE a.id_tipo_documento_identidad IS NULL;

        ALTER TABLE `auxiliares` DROP COLUMN `tipo_doc_identidad`;
    END IF;

    IF NOT EXISTS(SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'auxiliares' AND CONSTRAINT_NAME = 'fk_auxiliares_tipos_documento_identidad') THEN
        -- Se necesita asegurar que la columna no sea nula para añadir la FK si se desea.
        -- Por ahora la dejamos nulleable para evitar errores en datos existentes.
        ALTER TABLE `auxiliares` ADD CONSTRAINT `fk_auxiliares_tipos_documento_identidad`
        FOREIGN KEY (`id_tipo_documento_identidad`) REFERENCES `tipos_documento_identidad`(`id`);
    END IF;
END$$
DELIMITER ;
CALL `patch_refactor_auxiliares_doc_type`();
DROP PROCEDURE `patch_refactor_auxiliares_doc_type`;


-- == PARTE 3: PROCEDIMIENTOS ALMACENADOS ==========================

-- SPs para `tipos_documento_identidad`
DROP PROCEDURE IF EXISTS sp_create_tipo_documento_identidad;
DELIMITER $$
CREATE PROCEDURE `sp_create_tipo_documento_identidad`(IN p_codigo VARCHAR(10), IN p_nombre VARCHAR(100), IN p_descripcion TEXT, IN p_longitud INT)
BEGIN INSERT INTO tipos_documento_identidad (codigo, nombre, descripcion, longitud) VALUES (p_codigo, p_nombre, p_descripcion, p_longitud); END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_all_tipos_documento_identidad;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_tipos_documento_identidad`(IN p_codigo VARCHAR(10), IN p_nombre VARCHAR(100))
BEGIN SELECT * FROM tipos_documento_identidad WHERE (p_codigo IS NULL OR p_codigo = '' OR codigo LIKE CONCAT('%', p_codigo, '%')) AND (p_nombre IS NULL OR p_nombre = '' OR nombre LIKE CONCAT('%', p_nombre, '%')); END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_tipo_documento_identidad_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_tipo_documento_identidad_by_id`(IN p_id INT)
BEGIN SELECT * FROM tipos_documento_identidad WHERE id = p_id; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_update_tipo_documento_identidad;
DELIMITER $$
CREATE PROCEDURE `sp_update_tipo_documento_identidad`(IN p_id INT, IN p_nombre VARCHAR(100), IN p_descripcion TEXT, IN p_longitud INT, IN p_estado BOOLEAN)
BEGIN UPDATE tipos_documento_identidad SET nombre = p_nombre, descripcion = p_descripcion, longitud = p_longitud, estado = p_estado WHERE id = p_id; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_delete_tipo_documento_identidad;
DELIMITER $$
CREATE PROCEDURE `sp_delete_tipo_documento_identidad`(IN p_id INT)
BEGIN UPDATE tipos_documento_identidad SET estado = FALSE WHERE id = p_id; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_tipos_documento_identidad_for_dropdown;
DELIMITER $$
CREATE PROCEDURE `sp_read_tipos_documento_identidad_for_dropdown`()
BEGIN SELECT id, nombre FROM tipos_documento_identidad WHERE estado = TRUE ORDER BY nombre ASC; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_tipo_documento_identidad_longitud_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_tipo_documento_identidad_longitud_by_id`(IN p_id INT)
BEGIN SELECT longitud FROM tipos_documento_identidad WHERE id = p_id; END$$
DELIMITER ;

-- SPs para `auxiliares`
DROP PROCEDURE IF EXISTS sp_create_auxiliar;
DELIMITER $$
CREATE PROCEDURE `sp_create_auxiliar`(IN p_id_tipo_auxiliar INT, IN p_id_tipo_documento_identidad INT, IN p_num_doc_identidad VARCHAR(20), IN p_razon_social_nombres VARCHAR(255), IN p_direccion VARCHAR(255), IN p_telefono VARCHAR(50), IN p_email VARCHAR(100), IN p_ubigeo VARCHAR(6))
BEGIN INSERT INTO auxiliares (id_tipo_auxiliar, id_tipo_documento_identidad, num_doc_identidad, razon_social_nombres, direccion, telefono, email, ubigeo) VALUES (p_id_tipo_auxiliar, p_id_tipo_documento_identidad, p_num_doc_identidad, p_razon_social_nombres, p_direccion, p_telefono, p_email, p_ubigeo); END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_update_auxiliar;
DELIMITER $$
CREATE PROCEDURE `sp_update_auxiliar`(IN p_id INT, IN p_id_tipo_auxiliar INT, IN p_id_tipo_documento_identidad INT, IN p_num_doc_identidad VARCHAR(20), IN p_razon_social_nombres VARCHAR(255), IN p_direccion VARCHAR(255), IN p_telefono VARCHAR(50), IN p_email VARCHAR(100), IN p_ubigeo VARCHAR(6), IN p_estado BOOLEAN)
BEGIN UPDATE auxiliares SET id_tipo_auxiliar = p_id_tipo_auxiliar, id_tipo_documento_identidad = p_id_tipo_documento_identidad, num_doc_identidad = p_num_doc_identidad, razon_social_nombres = p_razon_social_nombres, direccion = p_direccion, telefono = p_telefono, email = p_email, ubigeo = p_ubigeo, estado = p_estado WHERE id = p_id; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_auxiliar_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_auxiliar_by_id`(IN p_id INT)
BEGIN SELECT * FROM auxiliares WHERE id = p_id; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_all_auxiliares;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_auxiliares`(IN p_nombre VARCHAR(255), IN p_num_doc VARCHAR(20), IN p_tipo_aux INT)
BEGIN SELECT a.id, a.razon_social_nombres, a.num_doc_identidad, a.direccion, a.telefono, a.email, a.estado, ta.nombre as nombre_tipo_auxiliar, tdi.nombre as tipo_doc_identidad FROM auxiliares a JOIN tipos_auxiliar ta ON a.id_tipo_auxiliar = ta.id LEFT JOIN tipos_documento_identidad tdi ON a.id_tipo_documento_identidad = tdi.id WHERE (p_nombre IS NULL OR p_nombre = '' OR a.razon_social_nombres LIKE CONCAT('%', p_nombre, '%')) AND (p_num_doc IS NULL OR p_num_doc = '' OR a.num_doc_identidad LIKE CONCAT('%', p_num_doc, '%')) AND (p_tipo_aux IS NULL OR p_tipo_aux = 0 OR a.id_tipo_auxiliar = p_tipo_aux); END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_delete_auxiliar;
DELIMITER $$
CREATE PROCEDURE `sp_delete_auxiliar`(IN p_id INT)
BEGIN
    DECLARE doc_count INT;
    SELECT COUNT(*) INTO doc_count FROM documentos WHERE id_auxiliar = p_id;
    IF doc_count > 0 THEN
        SELECT 'HAS_DOCS' AS status;
    ELSE
        DELETE FROM auxiliares WHERE id = p_id;
        SELECT 'DELETED' AS status;
    END IF;
END$$
DELIMITER ;

-- SPs para `documentos`
DROP PROCEDURE IF EXISTS sp_check_documento_duplicado;
DELIMITER $$
CREATE PROCEDURE `sp_check_documento_duplicado`(IN p_id_tipo_documento INT, IN p_serie_documento VARCHAR(10), IN p_numero_documento VARCHAR(20), IN p_id_auxiliar INT)
BEGIN SELECT COUNT(*) as duplicate_count FROM documentos WHERE id_tipo_documento = p_id_tipo_documento AND serie_documento = p_serie_documento AND numero_documento = p_numero_documento AND id_auxiliar = p_id_auxiliar; END$$
DELIMITER ;

-- SPs para `tipos_cambio`
DROP PROCEDURE IF EXISTS sp_read_tipo_cambio_by_fecha;
DELIMITER $$
CREATE PROCEDURE `sp_read_tipo_cambio_by_fecha`(IN p_fecha DATE)
BEGIN SELECT compra, venta FROM tipos_cambio WHERE fecha = p_fecha; END$$
DELIMITER ;
