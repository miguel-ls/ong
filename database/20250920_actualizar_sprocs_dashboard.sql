-- =================================================================
-- PARCHE PARA ACTUALIZAR PROCEDIMIENTOS DEL DASHBOARD
-- Fecha: 2025-09-20
-- Autor: Jules AI
-- Descripción: Este script actualiza los procedimientos almacenados
-- que alimentan el dashboard para que los cálculos de costos se basen
-- en la nueva tabla de distribución por porcentajes.
-- =================================================================

-- SP para el gráfico de barras: Totales por mes y centro de costo
DROP PROCEDURE IF EXISTS sp_reporte_total_soles_por_mes_y_centro_costo;
DELIMITER $$
CREATE PROCEDURE `sp_reporte_total_soles_por_mes_y_centro_costo`(IN p_anio INT)
BEGIN
    SELECT
        MONTH(d.fecha_emision) AS mes,
        cc.nombre AS nombre_centro_costo,
        SUM(dd.total_soles * (ddd.porcentaje / 100)) AS total_soles
    FROM documentos_detalle dd
    JOIN documentos d ON dd.id_documento = d.id
    JOIN documento_detalle_distribucion ddd ON dd.id = ddd.id_documento_detalle
    JOIN centros_costos cc ON ddd.id_centro_costo = cc.id
    WHERE YEAR(d.fecha_emision) = p_anio
    GROUP BY
        MONTH(d.fecha_emision),
        cc.nombre
    ORDER BY
        mes,
        nombre_centro_costo;
END$$
DELIMITER ;

-- SP para el primer gráfico circular: Totales por mes vs centro de costo
DROP PROCEDURE IF EXISTS sp_reporte_total_soles_por_mes_vs_centro_costo;
DELIMITER $$
CREATE PROCEDURE `sp_reporte_total_soles_por_mes_vs_centro_costo`(IN p_anio INT, IN p_mes INT)
BEGIN
    SELECT
        cc.nombre AS nombre_centro_costo,
        SUM(dd.total_soles * (ddd.porcentaje / 100)) AS total_soles
    FROM documentos_detalle dd
    JOIN documentos d ON dd.id_documento = d.id
    JOIN documento_detalle_distribucion ddd ON dd.id = ddd.id_documento_detalle
    JOIN centros_costos cc ON ddd.id_centro_costo = cc.id
    WHERE
        YEAR(d.fecha_emision) = p_anio
        AND MONTH(d.fecha_emision) = p_mes
    GROUP BY
        cc.nombre
    ORDER BY
        total_soles DESC;
END$$
DELIMITER ;

-- SP para el segundo gráfico circular: Totales por tipo de documento y centro de costo
DROP PROCEDURE IF EXISTS sp_reporte_total_soles_por_tipo_documento_y_centro_costo;
DELIMITER $$
CREATE PROCEDURE `sp_reporte_total_soles_por_tipo_documento_y_centro_costo`(IN p_anio INT, IN p_mes INT, IN p_id_centro_costo INT)
BEGIN
    SELECT
        td.nombre AS nombre_tipo_documento,
        SUM(dd.total_soles * (ddd.porcentaje / 100)) AS total_soles
    FROM documentos_detalle dd
    JOIN documentos d ON dd.id_documento = d.id
    JOIN documento_detalle_distribucion ddd ON dd.id = ddd.id_documento_detalle
    JOIN tipos_documento td ON d.id_tipo_documento = td.id
    WHERE
        YEAR(d.fecha_emision) = p_anio
        AND MONTH(d.fecha_emision) = p_mes
        AND ddd.id_centro_costo = p_id_centro_costo
    GROUP BY
        td.nombre
    ORDER BY
        total_soles DESC;
END$$
DELIMITER ;
