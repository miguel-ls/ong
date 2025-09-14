DELIMITER $$
CREATE PROCEDURE `sp_get_cuentas_contables`(IN `p_Emp_cCodigo` CHAR(3), IN `p_Pan_cAnio` CHAR(4))
BEGIN
    SELECT
        Pla_cCuentaContable AS value,
        CONCAT(Pla_cCuentaContable, ' - ', Pla_cNombreCuenta) AS text
    FROM
        cnm_plan_cta
    WHERE
        Emp_cCodigo = p_Emp_cCodigo AND
        Pan_cAnio = p_Pan_cAnio AND
        Pla_cEstado = '1' AND
        Pla_cDeleted = '0'
    ORDER BY
        Pla_cCuentaContable;
END$$
DELIMITER ;
