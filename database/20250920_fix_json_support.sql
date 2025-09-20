-- =================================================================
-- PARCHE DE COMPATIBILIDAD DE MYSQL
-- Fecha: 2025-09-20
-- Autor: Jules AI
-- Descripción: Este parche corrige un problema de compatibilidad con
-- versiones antiguas de MySQL que no soportan la función JSON_ARRAYAGG.
-- Se modifica el SP `sp_read_documento_detalle_by_id` para usar
-- GROUP_CONCAT en su lugar.
-- =================================================================

-- Paso 1: Modificar el SP que lee los detalles del documento para usar GROUP_CONCAT.
-- Esto asegura la compatibilidad con MySQL 5.6 y versiones anteriores.
DROP PROCEDURE IF EXISTS sp_read_documento_detalle_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_documento_detalle_by_id`(IN p_id_documento INT)
BEGIN
    SELECT
        dd.id,
        dd.id_documento,
        dd.item,
        dd.cantidad,
        dd.descripcion,
        dd.id_concepto,
        dd.precio_unitario,
        dd.precio_total,
        dd.total_soles,
        dd.total_dolares,
        (
            SELECT
                -- Usamos GROUP_CONCAT para construir manualmente el array JSON
                -- y CASE para devolver NULL si no hay distribuciones, imitando el comportamiento de JSON_ARRAYAGG
                CASE
                    WHEN COUNT(ddd.id) > 0 THEN
                        CONCAT('[',
                            GROUP_CONCAT(
                                CONCAT(
                                    '{"id_centro_costo":"', ddd.id_centro_costo, '",',
                                    '"porcentaje":"', ddd.porcentaje, '"}'
                                )
                                SEPARATOR ','
                            ),
                        ']')
                    ELSE
                        NULL
                END
            FROM documento_detalle_distribucion ddd
            WHERE ddd.id_documento_detalle = dd.id
        ) AS distribucion
    FROM
        documentos_detalle dd
    WHERE
        dd.id_documento = p_id_documento
    ORDER BY
        dd.item ASC;
END$$
DELIMITER ;
