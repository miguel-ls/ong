DELIMITER //

-- Using CREATE OR REPLACE for modern MySQL/MariaDB versions.
-- This is safer as it's atomic. If this fails on an older MySQL
-- version, the user would need to run DROP PROCEDURE IF EXISTS
-- first, then CREATE PROCEDURE.

CREATE OR REPLACE PROCEDURE `sp_read_documento_detalle_by_id`(
    IN `p_id_documento` INT
)
BEGIN
    -- This procedure selects all details for a given document ID.
    -- We are ensuring that the 'descripcion' column is included in the select list.
    SELECT
        dd.id,
        dd.id_documento,
        dd.item,
        dd.cantidad,
        dd.id_concepto,
        dd.descripcion,
        dd.precio_unitario,
        dd.precio_total,
        dd.total_soles,
        dd.total_dolares
    FROM
        `documentos_detalle` dd
    WHERE
        dd.id_documento = `p_id_documento`
    ORDER BY
        dd.item;
END //

DELIMITER ;

-- End of script.
