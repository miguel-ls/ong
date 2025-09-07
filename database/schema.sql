-- Base de Datos para Sistema de Gestión Documental de ONG
-- Motor: MySQL 5.7+

-- Tabla de Usuarios
CREATE TABLE `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre_usuario` VARCHAR(50) NOT NULL UNIQUE,
  `contrasena` VARCHAR(255) NOT NULL, -- Se almacenará hasheada
  `rol` ENUM('administrador', 'usuario') NOT NULL,
  `nombres` VARCHAR(100) NOT NULL,
  `apellidos` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `telefono` VARCHAR(20),
  `secret_2fa` VARCHAR(255), -- Para la autenticación de 2 factores
  `is_2fa_enabled` BOOLEAN NOT NULL DEFAULT FALSE,
  `estado` BOOLEAN NOT NULL DEFAULT TRUE,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de Proyectos
CREATE TABLE `proyectos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(20) NOT NULL UNIQUE,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT,
  `fecha_inicio` DATE,
  `fecha_fin` DATE,
  `presupuesto` DECIMAL(15, 2),
  `estado` BOOLEAN NOT NULL DEFAULT TRUE
);

-- Tabla de Sub-Proyectos
CREATE TABLE `sub_proyectos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_proyecto` INT NOT NULL,
  `codigo` VARCHAR(20) NOT NULL UNIQUE,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT,
  `presupuesto` DECIMAL(15, 2),
  `estado` BOOLEAN NOT NULL DEFAULT TRUE,
  FOREIGN KEY (`id_proyecto`) REFERENCES `proyectos`(`id`) ON DELETE CASCADE
);

-- Tabla de Centros de Costos
CREATE TABLE `centros_costos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(20) NOT NULL UNIQUE,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT,
  `estado` BOOLEAN NOT NULL DEFAULT TRUE
);

-- Tabla de Tipos de Documento
CREATE TABLE `tipos_documento` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(10) NOT NULL UNIQUE,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` TEXT,
  `estado` BOOLEAN NOT NULL DEFAULT TRUE
);

-- Tabla de Tipos de Auxiliar
CREATE TABLE `tipos_auxiliar` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(10) NOT NULL UNIQUE,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` TEXT,
  `estado` BOOLEAN NOT NULL DEFAULT TRUE
);

-- Tabla de Auxiliares (Clientes, Proveedores, etc.)
CREATE TABLE `auxiliares` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_tipo_auxiliar` INT NOT NULL,
  `tipo_doc_identidad` ENUM('RUC', 'DNI', 'CE', 'PASAPORTE', 'OTRO') NOT NULL,
  `num_doc_identidad` VARCHAR(20) NOT NULL UNIQUE,
  `razon_social_nombres` VARCHAR(255) NOT NULL, -- Unificado para persona natural y jurídica
  `direccion` VARCHAR(255),
  `telefono` VARCHAR(50),
  `email` VARCHAR(100),
  `estado` BOOLEAN NOT NULL DEFAULT TRUE,
  FOREIGN KEY (`id_tipo_auxiliar`) REFERENCES `tipos_auxiliar`(`id`)
);

-- Tabla de Conceptos de Gasto o Ingreso
CREATE TABLE `conceptos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(20) NOT NULL UNIQUE,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT,
  `tipo` ENUM('INGRESO', 'GASTO') NOT NULL,
  `estado` BOOLEAN NOT NULL DEFAULT TRUE
);

-- Tabla de Tipos de Cambio
CREATE TABLE `tipos_cambio` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `fecha` DATE NOT NULL UNIQUE,
  `compra` DECIMAL(10, 4) NOT NULL,
  `venta` DECIMAL(10, 4) NOT NULL,
  `fecha_registro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Tabla Principal de Documentos
CREATE TABLE `documentos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_tipo_documento` INT NOT NULL,
  `id_proyecto` INT NOT NULL,
  `id_sub_proyecto` INT,
  `id_centro_costo` INT NOT NULL,
  `id_auxiliar` INT NOT NULL,
  `id_concepto` INT NOT NULL,
  `id_usuario_registro` INT NOT NULL,
  `serie_documento` VARCHAR(10),
  `numero_documento` VARCHAR(20) NOT NULL,
  `fecha_emision` DATE NOT NULL,
  `fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `moneda` ENUM('SOLES', 'DOLARES') NOT NULL,
  `tipo_cambio` DECIMAL(10, 4) NOT NULL DEFAULT 1.0000,
  `subtotal` DECIMAL(15, 2) NOT NULL,
  `igv` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(15, 2) NOT NULL,
  `glosa` TEXT,
  `estado` ENUM('REGISTRADO', 'APROBADO', 'ANULADO') NOT NULL DEFAULT 'REGISTRADO',
  FOREIGN KEY (`id_tipo_documento`) REFERENCES `tipos_documento`(`id`),
  FOREIGN KEY (`id_proyecto`) REFERENCES `proyectos`(`id`),
  FOREIGN KEY (`id_sub_proyecto`) REFERENCES `sub_proyectos`(`id`),
  FOREIGN KEY (`id_centro_costo`) REFERENCES `centros_costos`(`id`),
  FOREIGN KEY (`id_auxiliar`) REFERENCES `auxiliares`(`id`),
  FOREIGN KEY (`id_concepto`) REFERENCES `conceptos`(`id`),
  FOREIGN KEY (`id_usuario_registro`) REFERENCES `usuarios`(`id`)
);
