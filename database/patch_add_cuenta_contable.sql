-- =================================================================
-- Patch para añadir la columna cuenta_contable a conceptos
-- Fecha: 2025-09-07
-- =================================================================

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
