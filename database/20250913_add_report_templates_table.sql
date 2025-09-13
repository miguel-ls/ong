-- Tabla para guardar plantillas de reportes dinámicos
CREATE TABLE `reporte_plantillas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_plantilla` varchar(255) NOT NULL,
  `columnas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`columnas`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre_plantilla` (`nombre_plantilla`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
