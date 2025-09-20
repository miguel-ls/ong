-- =================================================================
-- PARCHE PARA HACER EL CC DEL HEADER NULABLE Y OPCIONAL
-- Fecha: 2025-09-20
-- Autor: Jules AI
-- Descripción: Este parche soluciona un error de guardado que ocurría
-- al eliminar el CC del header del formulario. Se modifica la tabla
-- `documentos` para permitir que `id_centro_costo` sea NULL y se
-- actualizan los SPs de creación y edición para eliminar el parámetro.
-- =================================================================

-- Paso 1: Modificar la tabla `documentos` para permitir NULL en `id_centro_costo`
-- Esto es necesario porque el campo ya no se envía desde el formulario.
ALTER TABLE `documentos` MODIFY COLUMN `id_centro_costo` INT NULL;


-- Paso 2: Redefinir SP para crear encabezado de documento sin el parámetro de CC
DROP PROCEDURE IF EXISTS sp_create_documento_header;
DELIMITER $$
CREATE PROCEDURE `sp_create_documento_header`(
    IN p_id_tipo_documento INT, IN p_id_proyecto INT, IN p_id_sub_proyecto INT,
    IN p_id_auxiliar INT, IN p_id_usuario_registro INT, IN p_serie_documento VARCHAR(10),
    IN p_numero_documento VARCHAR(20), IN p_fecha_emision DATE, IN p_moneda ENUM('SOLES', 'DOLARES'),
    IN p_tipo_cambio DECIMAL(10, 4), IN p_subtotal DECIMAL(15, 2), IN p_igv DECIMAL(15, 2),
    IN p_total DECIMAL(15, 2), IN p_total_soles DECIMAL(15, 2), IN p_total_dolares DECIMAL(15, 2),
    IN p_glosa TEXT, IN p_observaciones TEXT, OUT p_new_id INT
)
BEGIN
    INSERT INTO documentos (
        id_tipo_documento, id_proyecto, id_sub_proyecto, id_auxiliar, id_usuario_registro,
        serie_documento, numero_documento, fecha_emision, moneda, tipo_cambio,
        subtotal, igv, total, total_soles, total_dolares, glosa, observaciones
    ) VALUES (
        p_id_tipo_documento, p_id_proyecto, p_id_sub_proyecto, p_id_auxiliar, p_id_usuario_registro,
        p_serie_documento, p_numero_documento, p_fecha_emision, p_moneda, p_tipo_cambio,
        p_subtotal, p_igv, p_total, p_total_soles, p_total_dolares, p_glosa, p_observaciones
    );
    SET p_new_id = LAST_INSERT_ID();
END$$
DELIMITER ;


-- Paso 3: Redefinir SP para actualizar encabezado de documento sin el parámetro de CC
DROP PROCEDURE IF EXISTS sp_update_documento_header;
DELIMITER $$
CREATE PROCEDURE `sp_update_documento_header`(
    IN p_id INT, IN p_id_tipo_documento INT, IN p_id_proyecto INT, IN p_id_sub_proyecto INT,
    IN p_id_auxiliar INT, IN p_serie_documento VARCHAR(10), IN p_numero_documento VARCHAR(20),
    IN p_fecha_emision DATE, IN p_moneda ENUM('SOLES', 'DOLARES'), IN p_tipo_cambio DECIMAL(10, 4),
    IN p_subtotal DECIMAL(15, 2), IN p_igv DECIMAL(15, 2), IN p_total DECIMAL(15, 2),
    IN p_total_soles DECIMAL(15, 2), IN p_total_dolares DECIMAL(15, 2), IN p_glosa TEXT, IN p_observaciones TEXT
)
BEGIN
    UPDATE documentos SET
        id_tipo_documento = p_id_tipo_documento, id_proyecto = p_id_proyecto,
        id_sub_proyecto = p_id_sub_proyecto, id_auxiliar = p_id_auxiliar,
        serie_documento = p_serie_documento, numero_documento = p_numero_documento,
        fecha_emision = p_fecha_emision, moneda = p_moneda, tipo_cambio = p_tipo_cambio,
        subtotal = p_subtotal, igv = p_igv, total = p_total, total_soles = p_total_soles,
        total_dolares = p_total_dolares, glosa = p_glosa, observaciones = p_observaciones
    WHERE id = p_id;
END$$
DELIMITER ;
