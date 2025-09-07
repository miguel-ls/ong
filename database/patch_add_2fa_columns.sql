-- =================================================================
-- Patch para añadir columnas de 2FA a la tabla de usuarios
-- Fecha: 2025-09-07
-- =================================================================

-- Este script añade las columnas para la funcionalidad de 2FA de forma segura.
-- Utiliza un procedimiento almacenado para verificar si las columnas ya existen antes de añadirlas.

DELIMITER $$
CREATE PROCEDURE `add_2fa_columns_to_usuarios`()
BEGIN
    -- Verificar y añadir la columna secret_2fa
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'secret_2fa'
    )
    THEN
        ALTER TABLE `usuarios` ADD COLUMN `secret_2fa` VARCHAR(255) NULL AFTER `telefono`;
    END IF;

    -- Verificar y añadir la columna is_2fa_enabled
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'is_2fa_enabled'
    )
    THEN
        ALTER TABLE `usuarios` ADD COLUMN `is_2fa_enabled` BOOLEAN NOT NULL DEFAULT FALSE AFTER `secret_2fa`;
    END IF;
END$$
DELIMITER ;

-- Llamar al procedimiento para ejecutar la lógica
CALL add_2fa_columns_to_usuarios();

-- Eliminar el procedimiento temporal
DROP PROCEDURE add_2fa_columns_to_usuarios;
