-- Add TipoERP and CodigoERP columns to auxiliares table
ALTER TABLE `auxiliares`
ADD COLUMN `TipoERP` CHAR(1) NULL,
ADD COLUMN `CodigoERP` VARCHAR(5) NULL;
