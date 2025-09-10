-- =================================================================
-- PARCHE CONSOLIDADO FINAL DE BASE DE DATOS
-- Fecha: 2025-09-09
-- Autor: Jules AI
-- Descripción: Este script único contiene todos los cambios de base de datos
-- realizados durante las sesiones de desarrollo. Ejecutar este script
-- debería dejar el esquema de la base de datos en el estado más reciente.
-- =================================================================


-- =================================================================
-- SECCIÓN 1: Modificaciones de Tablas
-- =================================================================

-- Desc: Añade columnas para 2FA a `usuarios`.
DELIMITER $$
CREATE PROCEDURE `patch_add_2fa_columns`()
BEGIN
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'secret_2fa') THEN
        ALTER TABLE `usuarios` ADD COLUMN `secret_2fa` VARCHAR(255) NULL AFTER `telefono`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'is_2fa_enabled') THEN
        ALTER TABLE `usuarios` ADD COLUMN `is_2fa_enabled` BOOLEAN NOT NULL DEFAULT FALSE AFTER `secret_2fa`;
    END IF;
END$$
DELIMITER ;
CALL patch_add_2fa_columns();
DROP PROCEDURE patch_add_2fa_columns;

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
  `total_soles` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `total_dolares` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (`id_documento`) REFERENCES `documentos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_concepto`) REFERENCES `conceptos`(`id`),
  UNIQUE KEY `idx_documento_item` (`id_documento`, `item`)
);

-- Desc: Elimina `id_concepto` de `documentos` (error de diseño).
DELIMITER $$
CREATE PROCEDURE `patch_remove_id_concepto_from_documentos`()
BEGIN
    IF EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documentos' AND COLUMN_NAME = 'id_concepto') THEN
        IF EXISTS (SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documentos' AND COLUMN_NAME = 'id_concepto') THEN
            SET @fk_name = (SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documentos' AND COLUMN_NAME = 'id_concepto' AND REFERENCED_TABLE_NAME = 'conceptos');
            IF @fk_name IS NOT NULL THEN
                SET @sql = CONCAT('ALTER TABLE `documentos` DROP FOREIGN KEY `', @fk_name, '`');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;
        END IF;
        ALTER TABLE `documentos` DROP COLUMN `id_concepto`;
    END IF;
END$$
DELIMITER ;
CALL patch_remove_id_concepto_from_documentos();
DROP PROCEDURE patch_remove_id_concepto_from_documentos;

-- Desc: Añade `cuenta_contable` a `conceptos`.
DELIMITER $$
CREATE PROCEDURE `patch_add_cuenta_contable_to_conceptos`()
BEGIN
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'conceptos' AND COLUMN_NAME = 'cuenta_contable') THEN
        ALTER TABLE `conceptos` ADD COLUMN `cuenta_contable` VARCHAR(20) NULL AFTER `tipo`;
    END IF;
END$$
DELIMITER ;
CALL patch_add_cuenta_contable_to_conceptos();
DROP PROCEDURE patch_add_cuenta_contable_to_conceptos;

-- Desc: Añade columnas de doble moneda a `documentos`.
DELIMITER $$
CREATE PROCEDURE `patch_add_currency_cols_to_header`()
BEGIN
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documentos' AND COLUMN_NAME = 'total_soles') THEN
        ALTER TABLE `documentos` ADD COLUMN `total_soles` DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER `total`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documentos' AND COLUMN_NAME = 'total_dolares') THEN
        ALTER TABLE `documentos` ADD COLUMN `total_dolares` DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER `total_soles`;
    END IF;
END$$
DELIMITER ;
CALL patch_add_currency_cols_to_header();
DROP PROCEDURE patch_add_currency_cols_to_header;

-- Desc: Añade la columna `id_centro_costo` a `documentos_detalle`.
DELIMITER $$
CREATE PROCEDURE `patch_add_cc_to_detalle`()
BEGIN
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documentos_detalle' AND COLUMN_NAME = 'id_centro_costo') THEN
        ALTER TABLE `documentos_detalle`
        ADD COLUMN `id_centro_costo` INT NULL AFTER `id_concepto`,
        ADD CONSTRAINT `fk_detalle_centro_costo` FOREIGN KEY (`id_centro_costo`) REFERENCES `centros_costos`(`id`);
    END IF;
END$$
DELIMITER ;
CALL patch_add_cc_to_detalle();
DROP PROCEDURE patch_add_cc_to_detalle;


-- =================================================================
-- SECCIÓN 2: Procedimientos Almacenados (SPs)
-- =================================================================
-- Desc: Versiones finales de todos los SPs necesarios.

-- SPs de Conceptos
DROP PROCEDURE IF EXISTS sp_create_concepto;
DELIMITER $$
CREATE PROCEDURE `sp_create_concepto`(IN p_codigo VARCHAR(20), IN p_nombre VARCHAR(255), IN p_descripcion TEXT, IN p_tipo ENUM('INGRESO', 'GASTO'), IN p_cuenta_contable VARCHAR(20))
BEGIN INSERT INTO conceptos (codigo, nombre, descripcion, tipo, cuenta_contable) VALUES (p_codigo, p_nombre, p_descripcion, p_tipo, p_cuenta_contable); END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_update_concepto;
DELIMITER $$
CREATE PROCEDURE `sp_update_concepto`(IN p_id INT, IN p_nombre VARCHAR(255), IN p_descripcion TEXT, IN p_tipo ENUM('INGRESO', 'GASTO'), IN p_estado BOOLEAN, IN p_cuenta_contable VARCHAR(20))
BEGIN UPDATE conceptos SET nombre = p_nombre, descripcion = p_descripcion, tipo = p_tipo, estado = p_estado, cuenta_contable = p_cuenta_contable WHERE id = p_id; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_all_conceptos;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_conceptos`(IN p_codigo VARCHAR(20), IN p_nombre VARCHAR(255), IN p_tipo VARCHAR(10))
BEGIN SELECT id, codigo, nombre, descripcion, tipo, cuenta_contable, estado FROM conceptos WHERE (p_codigo IS NULL OR p_codigo = '' OR codigo LIKE CONCAT('%', p_codigo, '%')) AND (p_nombre IS NULL OR p_nombre = '' OR nombre LIKE CONCAT('%', p_nombre, '%')) AND (p_tipo IS NULL OR p_tipo = '' OR tipo = p_tipo); END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_concepto_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_concepto_by_id`(IN p_id INT)
BEGIN SELECT id, codigo, nombre, descripcion, tipo, estado, cuenta_contable FROM conceptos WHERE id = p_id; END$$
DELIMITER ;

-- SPs para Dropdowns
DROP PROCEDURE IF EXISTS sp_read_proyectos_for_dropdown;
DELIMITER $$
CREATE PROCEDURE `sp_read_proyectos_for_dropdown`()
BEGIN SELECT id, nombre FROM proyectos WHERE estado = TRUE ORDER BY nombre ASC; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_tipos_auxiliar_for_dropdown;
DELIMITER $$
CREATE PROCEDURE `sp_read_tipos_auxiliar_for_dropdown`()
BEGIN SELECT id, nombre FROM tipos_auxiliar WHERE estado = TRUE ORDER BY nombre ASC; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_tipos_documento_for_dropdown;
DELIMITER $$
CREATE PROCEDURE `sp_read_tipos_documento_for_dropdown`()
BEGIN SELECT id, nombre FROM tipos_documento WHERE estado = TRUE ORDER BY nombre ASC; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_auxiliares_for_dropdown;
DELIMITER $$
CREATE PROCEDURE `sp_read_auxiliares_for_dropdown`()
BEGIN SELECT id, razon_social_nombres as nombre FROM auxiliares WHERE estado = TRUE ORDER BY razon_social_nombres ASC; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_conceptos_for_dropdown;
DELIMITER $$
CREATE PROCEDURE `sp_read_conceptos_for_dropdown`()
BEGIN SELECT id, nombre, tipo FROM conceptos WHERE estado = TRUE ORDER BY nombre ASC; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_centros_costos_for_dropdown;
DELIMITER $$
CREATE PROCEDURE `sp_read_centros_costos_for_dropdown`()
BEGIN SELECT id, nombre FROM centros_costos WHERE estado = TRUE ORDER BY nombre ASC; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_sub_proyectos_by_proyecto_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_sub_proyectos_by_proyecto_id`(IN p_id_proyecto INT)
BEGIN SELECT id, nombre FROM sub_proyectos WHERE estado = TRUE AND id_proyecto = p_id_proyecto ORDER BY nombre ASC; END$$
DELIMITER ;

-- SPs para Módulo de Documentos (CRUD Completo)
DROP PROCEDURE IF EXISTS sp_read_all_documentos;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_documentos`(
    IN p_fecha_desde DATE,
    IN p_fecha_hasta DATE,
    IN p_id_tipo_documento INT,
    IN p_serie_numero VARCHAR(31),
    IN p_auxiliar VARCHAR(255),
    IN p_id_centro_costo INT,
    IN p_moneda VARCHAR(10)
)
BEGIN
    SELECT
        d.id,
        d.fecha_emision,
        td.nombre as tipo_documento,
        d.serie_documento,
        d.numero_documento,
        a.razon_social_nombres as auxiliar,
        cc.nombre as centro_costo, -- CC de la cabecera, se mantiene para visualización
        d.moneda,
        d.total,
        d.total_soles,
        d.total_dolares
    FROM
        documentos d
    JOIN
        tipos_documento td ON d.id_tipo_documento = td.id
    JOIN
        auxiliares a ON d.id_auxiliar = a.id
    LEFT JOIN
        centros_costos cc ON d.id_centro_costo = cc.id -- CC de la cabecera
    WHERE
        (p_fecha_desde IS NULL OR d.fecha_emision >= p_fecha_desde)
        AND (p_fecha_hasta IS NULL OR d.fecha_emision <= p_fecha_hasta)
        AND (p_id_tipo_documento IS NULL OR p_id_tipo_documento = '' OR d.id_tipo_documento = p_id_tipo_documento)
        AND (p_serie_numero IS NULL OR p_serie_numero = '' OR CONCAT(d.serie_documento, '-', d.numero_documento) LIKE CONCAT('%', p_serie_numero, '%'))
        AND (p_auxiliar IS NULL OR p_auxiliar = '' OR a.razon_social_nombres LIKE CONCAT('%', p_auxiliar, '%'))
        AND (p_id_centro_costo IS NULL OR p_id_centro_costo = '' OR d.id IN (SELECT DISTINCT id_documento FROM documentos_detalle WHERE id_centro_costo = p_id_centro_costo))
        AND (p_moneda IS NULL OR p_moneda = '' OR d.moneda = p_moneda)
    ORDER BY
        d.fecha_emision DESC, d.id DESC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_create_documento_header;
DELIMITER $$
CREATE PROCEDURE `sp_create_documento_header`(IN p_id_tipo_documento INT, IN p_id_proyecto INT, IN p_id_sub_proyecto INT, IN p_id_centro_costo INT, IN p_id_auxiliar INT, IN p_id_usuario_registro INT, IN p_serie_documento VARCHAR(10), IN p_numero_documento VARCHAR(20), IN p_fecha_emision DATE, IN p_moneda ENUM('SOLES', 'DOLARES'), IN p_tipo_cambio DECIMAL(10, 4), IN p_subtotal DECIMAL(15, 2), IN p_igv DECIMAL(15, 2), IN p_total DECIMAL(15, 2), IN p_total_soles DECIMAL(15, 2), IN p_total_dolares DECIMAL(15, 2), IN p_glosa TEXT, OUT p_new_id INT)
BEGIN INSERT INTO documentos (id_tipo_documento, id_proyecto, id_sub_proyecto, id_centro_costo, id_auxiliar, id_usuario_registro, serie_documento, numero_documento, fecha_emision, moneda, tipo_cambio, subtotal, igv, total, total_soles, total_dolares, glosa) VALUES (p_id_tipo_documento, p_id_proyecto, p_id_sub_proyecto, p_id_centro_costo, p_id_auxiliar, p_id_usuario_registro, p_serie_documento, p_numero_documento, p_fecha_emision, p_moneda, p_tipo_cambio, p_subtotal, p_igv, p_total, p_total_soles, p_total_dolares, p_glosa); SET p_new_id = LAST_INSERT_ID(); END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_create_documento_detalle;
DELIMITER $$
CREATE PROCEDURE `sp_create_documento_detalle`(
    IN p_id_documento INT,
    IN p_item INT,
    IN p_cantidad DECIMAL(15, 4),
    IN p_descripcion VARCHAR(255),
    IN p_id_concepto INT,
    IN p_id_centro_costo INT, -- Nueva columna
    IN p_precio_unitario DECIMAL(15, 4),
    IN p_precio_total DECIMAL(15, 2),
    IN p_total_soles DECIMAL(15, 2),
    IN p_total_dolares DECIMAL(15, 2)
)
BEGIN
    INSERT INTO documentos_detalle (
        id_documento,
        item,
        cantidad,
        descripcion,
        id_concepto,
        id_centro_costo, -- Nueva columna
        precio_unitario,
        precio_total,
        total_soles,
        total_dolares
    ) VALUES (
        p_id_documento,
        p_item,
        p_cantidad,
        p_descripcion,
        p_id_concepto,
        p_id_centro_costo, -- Nueva columna
        p_precio_unitario,
        p_precio_total,
        p_total_soles,
        p_total_dolares
    );
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_documento_header_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_documento_header_by_id`(IN p_id INT)
BEGIN SELECT * FROM documentos WHERE id = p_id; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_documento_detalle_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_documento_detalle_by_id`(IN p_id_documento INT)
BEGIN
    SELECT
        id,
        id_documento,
        item,
        cantidad,
        descripcion,
        id_concepto,
        id_centro_costo, -- Seleccionado explícitamente
        precio_unitario,
        precio_total,
        total_soles,
        total_dolares
    FROM
        documentos_detalle
    WHERE
        id_documento = p_id_documento
    ORDER BY
        item ASC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_delete_documento_detalle_by_id_documento;
DELIMITER $$
CREATE PROCEDURE `sp_delete_documento_detalle_by_id_documento`(IN p_id_documento INT)
BEGIN DELETE FROM documentos_detalle WHERE id_documento = p_id_documento; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_update_documento_header;
DELIMITER $$
CREATE PROCEDURE `sp_update_documento_header`(IN p_id INT, IN p_id_tipo_documento INT, IN p_id_proyecto INT, IN p_id_sub_proyecto INT, IN p_id_centro_costo INT, IN p_id_auxiliar INT, IN p_serie_documento VARCHAR(10), IN p_numero_documento VARCHAR(20), IN p_fecha_emision DATE, IN p_moneda ENUM('SOLES', 'DOLARES'), IN p_tipo_cambio DECIMAL(10, 4), IN p_subtotal DECIMAL(15, 2), IN p_igv DECIMAL(15, 2), IN p_total DECIMAL(15, 2), IN p_total_soles DECIMAL(15, 2), IN p_total_dolares DECIMAL(15, 2), IN p_glosa TEXT)
BEGIN UPDATE documentos SET id_tipo_documento = p_id_tipo_documento, id_proyecto = p_id_proyecto, id_sub_proyecto = p_id_sub_proyecto, id_centro_costo = p_id_centro_costo, id_auxiliar = p_id_auxiliar, serie_documento = p_serie_documento, numero_documento = p_numero_documento, fecha_emision = p_fecha_emision, moneda = p_moneda, tipo_cambio = p_tipo_cambio, subtotal = p_subtotal, igv = p_igv, total = p_total, total_soles = p_total_soles, total_dolares = p_total_dolares, glosa = p_glosa WHERE id = p_id; END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_delete_documento;
DELIMITER $$
CREATE PROCEDURE `sp_delete_documento`(IN p_id INT)
BEGIN
    -- La eliminación en cascada se encargará del detalle
    DELETE FROM documentos WHERE id = p_id;
END$$
DELIMITER ;

-- SPs para Auxiliares
DROP PROCEDURE IF EXISTS sp_check_auxiliar_duplicado;
DELIMITER $$
CREATE PROCEDURE `sp_check_auxiliar_duplicado`(
    IN p_id_tipo_auxiliar INT,
    IN p_num_doc_identidad VARCHAR(20),
    IN p_id INT
)
BEGIN
    SELECT id
    FROM auxiliares
    WHERE id_tipo_auxiliar = p_id_tipo_auxiliar
      AND num_doc_identidad = p_num_doc_identidad
      AND (p_id IS NULL OR id != p_id);
END$$
DELIMITER ;

-- SP para validación de documentos duplicados
DROP PROCEDURE IF EXISTS sp_check_documento_duplicado;
DELIMITER $$
CREATE PROCEDURE `sp_check_documento_duplicado`(
    IN p_id_tipo_documento INT,
    IN p_serie_documento VARCHAR(10),
    IN p_numero_documento VARCHAR(20),
    IN p_id_auxiliar INT,
    IN p_id_documento INT -- Opcional: para excluir el documento actual en una actualización
)
BEGIN
    SELECT
        id,
        serie_documento,
        numero_documento
    FROM
        documentos
    WHERE
        id_tipo_documento = p_id_tipo_documento
        AND serie_documento = p_serie_documento
        AND numero_documento = p_numero_documento
        AND id_auxiliar = p_id_auxiliar
        AND (p_id_documento IS NULL OR id != p_id_documento)
    LIMIT 1;
END$$
DELIMITER ;
