-- =================================================================
-- SP para obtener los años con documentos
-- =================================================================
DROP PROCEDURE IF EXISTS sp_get_years_with_documents;
DELIMITER $$
CREATE PROCEDURE `sp_get_years_with_documents`()
BEGIN
    SELECT DISTINCT YEAR(fecha_emision) AS anio
    FROM documentos
    ORDER BY anio DESC;
END$$
DELIMITER ;
