-- =================================================================
-- FECHA: 2025-09-15
-- AUTOR: Jules AI
-- DESCRIPCIÓN: Actualiza el SP sp_get_cuentas_contables para que el
-- parámetro del año sea más genérico.
-- =================================================================

DROP PROCEDURE IF EXISTS sp_get_cuentas_contables;
DELIMITER $$
CREATE PROCEDURE `sp_get_cuentas_contables`(IN `p_Emp_cCodigo` CHAR(3), IN `p_año` CHAR(4))
BEGIN
    SELECT
        Pla_cCuentaContable AS value,
        CONCAT(Pla_cCuentaContable, ' - ', Pla_cNombreCuenta) AS text
    FROM
        cnm_plan_cta
    WHERE
        Emp_cCodigo = p_Emp_cCodigo AND
        Pan_cAnio = p_año AND
        Pla_cEstado = 'A'  AND Pla_cTitulo='N'
    ORDER BY
        Pla_cCuentaContable;
END$$
DELIMITER ;
