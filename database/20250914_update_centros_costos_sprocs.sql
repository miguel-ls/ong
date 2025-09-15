-- =================================================================================
-- Update CRUD stored procedures for 'centros_costos' to include 'anio'
-- =================================================================================

-- Update sp_create_centro_costo to include 'anio'
DROP PROCEDURE IF EXISTS `sp_create_centro_costo`;
DELIMITER $$
CREATE PROCEDURE `sp_create_centro_costo`(
    IN p_anio SMALLINT,
    IN p_codigo VARCHAR(20),
    IN p_nombre VARCHAR(255),
    IN p_descripcion TEXT
)
BEGIN
    INSERT INTO centros_costos (anio, codigo, nombre, descripcion, estado)
    VALUES (p_anio, p_codigo, p_nombre, p_descripcion, 1); -- Default estado to 1 (Activo)
END$$
DELIMITER ;

-- Update sp_update_centro_costo to include 'anio'
DROP PROCEDURE IF EXISTS `sp_update_centro_costo`;
DELIMITER $$
CREATE PROCEDURE `sp_update_centro_costo`(
    IN p_id INT,
    IN p_anio SMALLINT,
    IN p_nombre VARCHAR(255),
    IN p_descripcion TEXT,
    IN p_estado TINYINT
)
BEGIN
    UPDATE centros_costos
    SET
        anio = p_anio,
        nombre = p_nombre,
        descripcion = p_descripcion,
        estado = p_estado
    WHERE
        id = p_id;
END$$
DELIMITER ;

-- Update sp_read_centro_costo_by_id to ensure it returns the new 'anio' column
-- (SELECT * will automatically include the new column after the ALTER TABLE script is run)
DROP PROCEDURE IF EXISTS `sp_read_centro_costo_by_id`;
DELIMITER $$
CREATE PROCEDURE `sp_read_centro_costo_by_id`(
    IN p_id INT
)
BEGIN
    SELECT * FROM centros_costos WHERE id = p_id;
END$$
DELIMITER ;
