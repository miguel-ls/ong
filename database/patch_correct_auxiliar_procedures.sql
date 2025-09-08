-- =================================================================
-- Script de Corrección Final para los SPs de Auxiliares
-- Fecha: 2025-09-08
-- Autor: Jules AI
-- Descripción: Este script contiene las versiones finales y correctas
-- de los procedimientos para crear y actualizar auxiliares.
-- Ejecute este script para resolver los problemas de guardado.
-- =================================================================

-- Corregir sp_create_auxiliar (8 parámetros)
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
    IN p_ubigeo VARCHAR(6)
)
BEGIN
    INSERT INTO auxiliares (id_tipo_auxiliar, id_tipo_documento_identidad, num_doc_identidad, razon_social_nombres, direccion, telefono, email, ubigeo)
    VALUES (p_id_tipo_auxiliar, p_id_tipo_documento_identidad, p_num_doc_identidad, p_razon_social_nombres, p_direccion, p_telefono, p_email, p_ubigeo);
END$$
DELIMITER ;

-- Corregir sp_update_auxiliar (10 parámetros)
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
    IN p_estado BOOLEAN
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
        estado = p_estado
    WHERE id = p_id;
END$$
DELIMITER ;
