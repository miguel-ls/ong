-- =================================================================
-- PARCHE PARA AÑADIR CENTRO DE COSTO AL DETALLE DE DOCUMENTOS
-- Fecha: 2025-09-09
-- Autor: Jules AI
-- Descripción: Este script añade la columna id_centro_costo a la
-- tabla documentos_detalle y actualiza los SPs correspondientes.
-- =================================================================

-- =================================================================
-- SECCIÓN 1: Modificaciones de Tablas
-- =================================================================

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

-- SP para crear detalle de documento
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

-- SP para leer detalle de documento por ID de documento
DROP PROCEDURE IF EXISTS sp_read_documento_detalle_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_documento_detalle_by_id`(IN p_id_documento INT)
BEGIN
    SELECT
        dd.*,
        cc.nombre AS centro_costo_nombre -- Opcional: unirse para obtener el nombre para la UI si es necesario
    FROM
        documentos_detalle dd
    LEFT JOIN
        centros_costos cc ON dd.id_centro_costo = cc.id
    WHERE
        dd.id_documento = p_id_documento
    ORDER BY
        dd.item ASC;
END$$
DELIMITER ;

-- SP para leer todos los documentos (actualizar la vista principal)
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
        cc.nombre as centro_costo, -- Añadido para la vista principal
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
        centros_costos cc ON d.id_centro_costo = cc.id -- LEFT JOIN por si no tiene CC
    ORDER BY
        d.fecha_emision DESC, d.id DESC;
END$$
DELIMITER ;
