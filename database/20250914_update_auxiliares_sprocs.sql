-- =================================================================
-- ACTUALIZACIÓN DE PROCEDIMIENTOS ALMACENADOS PARA AUXILIARES
-- Fecha: 2025-09-14
-- Autor: Jules AI
-- Descripción: Este script actualiza los procedimientos almacenados
-- para el módulo de auxiliares para incluir los campos
-- TipoERP y CodigoERP, y para alinear la firma de los
-- procedimientos con el código de la aplicación.
-- =================================================================

-- sp_create_auxiliar
DROP PROCEDURE IF EXISTS sp_create_auxiliar;
DELIMITER $$
CREATE PROCEDURE `sp_create_auxiliar`(
    IN p_id_tipo_auxiliar INT,
    IN p_id_tipo_documento_identidad INT,
    IN p_num_doc_identidad VARCHAR(20),
    IN p_razon_social_nombres VARCHAR(255),
    IN p_direccion VARCHAR(255),
    IN p_telefono VARCHAR(50),
    IN p_email VARCHAR(100),
    IN p_ubigeo VARCHAR(6),
    IN p_TipoERP CHAR(1),
    IN p_CodigoERP VARCHAR(5)
)
BEGIN
    INSERT INTO auxiliares (id_tipo_auxiliar, id_tipo_documento_identidad, num_doc_identidad, razon_social_nombres, direccion, telefono, email, ubigeo, TipoERP, CodigoERP)
    VALUES (p_id_tipo_auxiliar, p_id_tipo_documento_identidad, p_num_doc_identidad, p_razon_social_nombres, p_direccion, p_telefono, p_email, p_ubigeo, p_TipoERP, p_CodigoERP);
END$$
DELIMITER ;

-- sp_read_all_auxiliares
DROP PROCEDURE IF EXISTS sp_read_all_auxiliares;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_auxiliares`(
    IN p_nombre VARCHAR(255),
    IN p_num_doc VARCHAR(20),
    IN p_tipo_aux INT,
    IN p_tipo_erp CHAR(1),
    IN p_codigo_erp VARCHAR(5)
)
BEGIN
    SELECT a.*, ta.nombre as nombre_tipo_auxiliar
    FROM auxiliares a
    JOIN tipos_auxiliar ta ON a.id_tipo_auxiliar = ta.id
    WHERE
        (p_nombre IS NULL OR p_nombre = '' OR a.razon_social_nombres LIKE CONCAT('%', p_nombre, '%'))
        AND (p_num_doc IS NULL OR p_num_doc = '' OR a.num_doc_identidad LIKE CONCAT('%', p_num_doc, '%'))
        AND (p_tipo_aux IS NULL OR p_tipo_aux = 0 OR a.id_tipo_auxiliar = p_tipo_aux)
        AND (p_tipo_erp IS NULL OR p_tipo_erp = '' OR a.TipoERP LIKE CONCAT('%', p_tipo_erp, '%'))
        AND (p_codigo_erp IS NULL OR p_codigo_erp = '' OR a.CodigoERP LIKE CONCAT('%', p_codigo_erp, '%'));
END$$
DELIMITER ;

-- sp_read_auxiliar_by_id
DROP PROCEDURE IF EXISTS sp_read_auxiliar_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_auxiliar_by_id`(IN p_id INT)
BEGIN
    SELECT * FROM auxiliares WHERE id = p_id;
END$$
DELIMITER ;

-- sp_update_auxiliar
DROP PROCEDURE IF EXISTS sp_update_auxiliar;
DELIMITER $$
CREATE PROCEDURE `sp_update_auxiliar`(
    IN p_id INT,
    IN p_id_tipo_auxiliar INT,
    IN p_id_tipo_documento_identidad INT,
    IN p_num_doc_identidad VARCHAR(20),
    IN p_razon_social_nombres VARCHAR(255),
    IN p_direccion VARCHAR(255),
    IN p_telefono VARCHAR(50),
    IN p_email VARCHAR(100),
    IN p_ubigeo VARCHAR(6),
    IN p_estado BOOLEAN,
    IN p_TipoERP CHAR(1),
    IN p_CodigoERP VARCHAR(5)
)
BEGIN
    UPDATE auxiliares
    SET
        id_tipo_auxiliar = p_id_tipo_auxiliar,
        id_tipo_documento_identidad = p_id_tipo_documento_identidad,
        num_doc_identidad = p_num_doc_identidad,
        razon_social_nombres = p_razon_social_nombres,
        direccion = p_direccion,
        telefono = p_telefono,
        email = p_email,
        ubigeo = p_ubigeo,
        estado = p_estado,
        TipoERP = p_TipoERP,
        CodigoERP = p_CodigoERP
    WHERE id = p_id;
END$$
DELIMITER ;
