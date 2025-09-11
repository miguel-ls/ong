-- =================================================================
-- Script para añadir la funcionalidad de adjuntos a documentos
-- =================================================================

-- 1. Creación de la tabla para almacenar los archivos adjuntos
CREATE TABLE IF NOT EXISTS `documento_adjuntos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_documento` INT NOT NULL,
  `nombre_original` VARCHAR(255) NOT NULL,
  `nombre_almacenado` VARCHAR(255) NOT NULL,
  `ruta_almacenamiento` VARCHAR(512) NOT NULL,
  `tipo_mime` VARCHAR(100) NOT NULL,
  `tamaño_bytes` INT NOT NULL,
  `fecha_subida` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_documento`) REFERENCES `documentos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =================================================================
-- Creación de Procedimientos Almacenados para Adjuntos
-- =================================================================

-- 2. Procedimiento para crear un nuevo adjunto
DROP PROCEDURE IF EXISTS sp_create_documento_adjunto;
DELIMITER $$
CREATE PROCEDURE `sp_create_documento_adjunto`(
    IN p_id_documento INT,
    IN p_nombre_original VARCHAR(255),
    IN p_nombre_almacenado VARCHAR(255),
    IN p_ruta_almacenamiento VARCHAR(512),
    IN p_tipo_mime VARCHAR(100),
    IN p_tamaño_bytes INT
)
BEGIN
    INSERT INTO documento_adjuntos (id_documento, nombre_original, nombre_almacenado, ruta_almacenamiento, tipo_mime, tamaño_bytes)
    VALUES (p_id_documento, p_nombre_original, p_nombre_almacenado, p_ruta_almacenamiento, p_tipo_mime, p_tamaño_bytes);
END$$
DELIMITER ;

-- 3. Procedimiento para leer los adjuntos de un documento
DROP PROCEDURE IF EXISTS sp_read_adjuntos_by_documento_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_adjuntos_by_documento_id`(
    IN p_id_documento INT
)
BEGIN
    SELECT id, id_documento, nombre_original, ruta_almacenamiento, tipo_mime, tamaño_bytes, fecha_subida
    FROM documento_adjuntos
    WHERE id_documento = p_id_documento
    ORDER BY fecha_subida ASC;
END$$
DELIMITER ;

-- 4. Procedimiento para eliminar un adjunto por su ID
DROP PROCEDURE IF EXISTS sp_delete_adjunto_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_delete_adjunto_by_id`(
    IN p_id_adjunto INT
)
BEGIN
    -- Se necesita seleccionar la ruta para poder eliminar el archivo físico desde PHP
    SELECT ruta_almacenamiento, nombre_almacenado FROM documento_adjuntos WHERE id = p_id_adjunto;
    -- Eliminar el registro de la base de datos
    DELETE FROM documento_adjuntos WHERE id = p_id_adjunto;
END$$
DELIMITER ;
