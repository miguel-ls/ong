-- =================================================================
-- Patch para añadir la tabla de detalle de documentos
-- Fecha: 2025-09-07
-- =================================================================

-- Este script crea la tabla `documentos_detalle` para almacenar las líneas
-- de detalle de los comprobantes de ingreso.

CREATE TABLE IF NOT EXISTS `documentos_detalle` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_documento` INT NOT NULL,
  `item` INT NOT NULL,
  `cantidad` DECIMAL(15, 4) NOT NULL,
  `descripcion` VARCHAR(255) NOT NULL,
  `id_concepto` INT NOT NULL,
  `precio_unitario` DECIMAL(15, 4) NOT NULL,
  `precio_total` DECIMAL(15, 2) NOT NULL,
  FOREIGN KEY (`id_documento`) REFERENCES `documentos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_concepto`) REFERENCES `conceptos`(`id`),
  UNIQUE KEY `idx_documento_item` (`id_documento`, `item`)
);
