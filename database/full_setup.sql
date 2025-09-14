-- =================================================================
-- Sistema de Gestión Documental de ONG
-- Script de Configuración Completo de Base de Datos
-- Motor: MySQL 5.7+
-- =================================================================

-- =================================================================
-- Creación de Tablas
-- =================================================================

-- Tabla de Usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre_usuario` VARCHAR(50) NOT NULL UNIQUE,
  `contrasena` VARCHAR(255) NOT NULL,
  `rol` ENUM('administrador', 'usuario') NOT NULL,
  `nombres` VARCHAR(100) NOT NULL,
  `apellidos` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `telefono` VARCHAR(20),
  `secret_2fa` VARCHAR(255),
  `is_2fa_enabled` BOOLEAN NOT NULL DEFAULT FALSE,
  `estado` BOOLEAN NOT NULL DEFAULT TRUE,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de Proyectos
CREATE TABLE IF NOT EXISTS `proyectos` (
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
CREATE TABLE IF NOT EXISTS `sub_proyectos` (
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
CREATE TABLE IF NOT EXISTS `centros_costos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(20) NOT NULL UNIQUE,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT,
  `estado` BOOLEAN NOT NULL DEFAULT TRUE
);

-- Tabla de Tipos de Documento
CREATE TABLE IF NOT EXISTS `tipos_documento` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(10) NOT NULL UNIQUE,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` TEXT,
  `estado` BOOLEAN NOT NULL DEFAULT TRUE
);

-- Tabla de Tipos de Auxiliar
CREATE TABLE IF NOT EXISTS `tipos_auxiliar` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(10) NOT NULL UNIQUE,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` TEXT,
  `estado` BOOLEAN NOT NULL DEFAULT TRUE
);

-- Tabla de Auxiliares
CREATE TABLE IF NOT EXISTS `auxiliares` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_tipo_auxiliar` INT NOT NULL,
  `id_tipo_documento_identidad` INT NOT NULL,
  `num_doc_identidad` VARCHAR(20) NOT NULL,
  `razon_social_nombres` VARCHAR(255) NOT NULL,
  `direccion` VARCHAR(255),
  `telefono` VARCHAR(50),
  `email` VARCHAR(100),
  `ubigeo` VARCHAR(6) DEFAULT NULL,
  `estado` BOOLEAN NOT NULL DEFAULT TRUE,
  `TipoERP` CHAR(1) NULL,
  `CodigoERP` VARCHAR(5) NULL,
  FOREIGN KEY (`id_tipo_auxiliar`) REFERENCES `tipos_auxiliar`(`id`)
);

-- Tabla de Conceptos de Gasto o Ingreso
CREATE TABLE IF NOT EXISTS `conceptos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(20) NOT NULL UNIQUE,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT,
  `tipo` ENUM('INGRESO', 'GASTO') NOT NULL,
  `estado` BOOLEAN NOT NULL DEFAULT TRUE
);

-- Tabla de Tipos de Cambio
CREATE TABLE IF NOT EXISTS `tipos_cambio` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `fecha` DATE NOT NULL UNIQUE,
  `compra` DECIMAL(10, 4) NOT NULL,
  `venta` DECIMAL(10, 4) NOT NULL,
  `fecha_registro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Tabla Principal de Documentos
CREATE TABLE IF NOT EXISTS `documentos` (
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

-- =================================================================
-- Creación de Procedimientos Almacenados
-- =================================================================

-- Usuarios
DROP PROCEDURE IF EXISTS sp_create_usuario;
DELIMITER $$
CREATE PROCEDURE `sp_create_usuario`(
    IN p_nombre_usuario VARCHAR(50),
    IN p_contrasena VARCHAR(255),
    IN p_rol ENUM('administrador', 'usuario'),
    IN p_nombres VARCHAR(100),
    IN p_apellidos VARCHAR(100),
    IN p_email VARCHAR(100),
    IN p_telefono VARCHAR(20)
)
BEGIN
    INSERT INTO usuarios (nombre_usuario, contrasena, rol, nombres, apellidos, email, telefono)
    VALUES (p_nombre_usuario, p_contrasena, p_rol, p_nombres, p_apellidos, p_email, p_telefono);
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_all_usuarios;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_usuarios`(
    IN p_nombre VARCHAR(100),
    IN p_email VARCHAR(100),
    IN p_rol VARCHAR(20)
)
BEGIN
    SELECT id, nombre_usuario, rol, nombres, apellidos, email, telefono, estado, is_2fa_enabled
    FROM usuarios
    WHERE
        (p_nombre IS NULL OR p_nombre = '' OR nombres LIKE CONCAT('%', p_nombre, '%') OR apellidos LIKE CONCAT('%', p_nombre, '%'))
        AND (p_email IS NULL OR p_email = '' OR email LIKE CONCAT('%', p_email, '%'))
        AND (p_rol IS NULL OR p_rol = '' OR rol = p_rol);
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_usuario_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_usuario_by_id`(IN p_id INT)
BEGIN
    SELECT id, nombre_usuario, rol, nombres, apellidos, email, telefono, estado, secret_2fa, is_2fa_enabled FROM usuarios WHERE id = p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_usuario_by_username;
DELIMITER $$
CREATE PROCEDURE `sp_read_usuario_by_username`(IN p_nombre_usuario VARCHAR(50))
BEGIN
    SELECT * FROM usuarios WHERE nombre_usuario = p_nombre_usuario;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_update_usuario;
DELIMITER $$
CREATE PROCEDURE `sp_update_usuario`(
    IN p_id INT,
    IN p_rol ENUM('administrador', 'usuario'),
    IN p_nombres VARCHAR(100),
    IN p_apellidos VARCHAR(100),
    IN p_email VARCHAR(100),
    IN p_telefono VARCHAR(20),
    IN p_estado BOOLEAN
)
BEGIN
    UPDATE usuarios
    SET
        rol = p_rol,
        nombres = p_nombres,
        apellidos = p_apellidos,
        email = p_email,
        telefono = p_telefono,
        estado = p_estado
    WHERE id = p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_delete_usuario;
DELIMITER $$
CREATE PROCEDURE `sp_delete_usuario`(IN p_id INT)
BEGIN
    UPDATE usuarios SET estado = FALSE WHERE id = p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_update_usuario_2fa;
DELIMITER $$
CREATE PROCEDURE `sp_update_usuario_2fa`(
    IN p_id INT,
    IN p_secret_2fa VARCHAR(255),
    IN p_is_2fa_enabled BOOLEAN
)
BEGIN
    UPDATE usuarios
    SET
        secret_2fa = p_secret_2fa,
        is_2fa_enabled = p_is_2fa_enabled
    WHERE id = p_id;
END$$
DELIMITER ;

-- Proyectos
DROP PROCEDURE IF EXISTS sp_create_proyecto;
DELIMITER $$
CREATE PROCEDURE `sp_create_proyecto`(
    IN p_codigo VARCHAR(20),
    IN p_nombre VARCHAR(255),
    IN p_descripcion TEXT,
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE,
    IN p_presupuesto DECIMAL(15, 2)
)
BEGIN
    INSERT INTO proyectos (codigo, nombre, descripcion, fecha_inicio, fecha_fin, presupuesto)
    VALUES (p_codigo, p_nombre, p_descripcion, p_fecha_inicio, p_fecha_fin, p_presupuesto);
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_all_proyectos;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_proyectos`(
    IN p_codigo VARCHAR(20),
    IN p_nombre VARCHAR(255)
)
BEGIN
    SELECT * FROM proyectos
    WHERE
        (p_codigo IS NULL OR p_codigo = '' OR codigo LIKE CONCAT('%', p_codigo, '%'))
        AND (p_nombre IS NULL OR p_nombre = '' OR nombre LIKE CONCAT('%', p_nombre, '%'));
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_proyecto_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_proyecto_by_id`(IN p_id INT)
BEGIN
    SELECT * FROM proyectos WHERE id = p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_update_proyecto;
DELIMITER $$
CREATE PROCEDURE `sp_update_proyecto`(
    IN p_id INT,
    IN p_nombre VARCHAR(255),
    IN p_descripcion TEXT,
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE,
    IN p_presupuesto DECIMAL(15, 2),
    IN p_estado BOOLEAN
)
BEGIN
    UPDATE proyectos
    SET
        nombre = p_nombre,
        descripcion = p_descripcion,
        fecha_inicio = p_fecha_inicio,
        fecha_fin = p_fecha_fin,
        presupuesto = p_presupuesto,
        estado = p_estado
    WHERE id = p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_delete_proyecto;
DELIMITER $$
CREATE PROCEDURE `sp_delete_proyecto`(IN p_id INT)
BEGIN
    UPDATE proyectos SET estado = FALSE WHERE id = p_id;
END$$
DELIMITER ;

-- Tipos de Documento
DROP PROCEDURE IF EXISTS sp_create_tipo_documento;
DELIMITER $$
CREATE PROCEDURE `sp_create_tipo_documento`(
    IN p_codigo VARCHAR(10),
    IN p_nombre VARCHAR(100),
    IN p_descripcion TEXT
)
BEGIN
    INSERT INTO tipos_documento (codigo, nombre, descripcion)
    VALUES (p_codigo, p_nombre, p_descripcion);
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_all_tipos_documento;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_tipos_documento`(
    IN p_codigo VARCHAR(10),
    IN p_nombre VARCHAR(100)
)
BEGIN
    SELECT * FROM tipos_documento
    WHERE
        (p_codigo IS NULL OR p_codigo = '' OR codigo LIKE CONCAT('%', p_codigo, '%'))
        AND (p_nombre IS NULL OR p_nombre = '' OR nombre LIKE CONCAT('%', p_nombre, '%'));
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_tipo_documento_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_tipo_documento_by_id`(IN p_id INT)
BEGIN
    SELECT * FROM tipos_documento WHERE id = p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_update_tipo_documento;
DELIMITER $$
CREATE PROCEDURE `sp_update_tipo_documento`(
    IN p_id INT,
    IN p_nombre VARCHAR(100),
    IN p_descripcion TEXT,
    IN p_estado BOOLEAN
)
BEGIN
    UPDATE tipos_documento
    SET
        nombre = p_nombre,
        descripcion = p_descripcion,
        estado = p_estado
    WHERE id = p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_delete_tipo_documento;
DELIMITER $$
CREATE PROCEDURE `sp_delete_tipo_documento`(IN p_id INT)
BEGIN
    UPDATE tipos_documento SET estado = FALSE WHERE id = p_id;
END$$
DELIMITER ;

-- Sub Proyectos
DROP PROCEDURE IF EXISTS sp_create_sub_proyecto;
DELIMITER $$
CREATE PROCEDURE `sp_create_sub_proyecto`(
    IN p_id_proyecto INT,
    IN p_codigo VARCHAR(20),
    IN p_nombre VARCHAR(255),
    IN p_descripcion TEXT,
    IN p_presupuesto DECIMAL(15, 2)
)
BEGIN
    INSERT INTO sub_proyectos (id_proyecto, codigo, nombre, descripcion, presupuesto)
    VALUES (p_id_proyecto, p_codigo, p_nombre, p_descripcion, p_presupuesto);
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_all_sub_proyectos;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_sub_proyectos`(
    IN p_codigo VARCHAR(20),
    IN p_nombre VARCHAR(255),
    IN p_id_proyecto INT
)
BEGIN
    SELECT sp.*, p.nombre as nombre_proyecto
    FROM sub_proyectos sp
    JOIN proyectos p ON sp.id_proyecto = p.id
    WHERE
        (p_codigo IS NULL OR p_codigo = '' OR sp.codigo LIKE CONCAT('%', p_codigo, '%'))
        AND (p_nombre IS NULL OR p_nombre = '' OR sp.nombre LIKE CONCAT('%', p_nombre, '%'))
        AND (p_id_proyecto IS NULL OR p_id_proyecto = 0 OR sp.id_proyecto = p_id_proyecto);
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_sub_proyecto_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_sub_proyecto_by_id`(IN p_id INT)
BEGIN
    SELECT * FROM sub_proyectos WHERE id = p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_update_sub_proyecto;
DELIMITER $$
CREATE PROCEDURE `sp_update_sub_proyecto`(
    IN p_id INT,
    IN p_id_proyecto INT,
    IN p_nombre VARCHAR(255),
    IN p_descripcion TEXT,
    IN p_presupuesto DECIMAL(15, 2),
    IN p_estado BOOLEAN
)
BEGIN
    UPDATE sub_proyectos
    SET
        id_proyecto = p_id_proyecto,
        nombre = p_nombre,
        descripcion = p_descripcion,
        presupuesto = p_presupuesto,
        estado = p_estado
    WHERE id = p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_delete_sub_proyecto;
DELIMITER $$
CREATE PROCEDURE `sp_delete_sub_proyecto`(IN p_id INT)
BEGIN
    UPDATE sub_proyectos SET estado = FALSE WHERE id = p_id;
END$$
DELIMITER ;

-- Centros de Costos
DROP PROCEDURE IF EXISTS sp_create_centro_costo;
DELIMITER $$
CREATE PROCEDURE `sp_create_centro_costo`(
    IN p_codigo VARCHAR(20),
    IN p_nombre VARCHAR(255),
    IN p_descripcion TEXT
)
BEGIN
    INSERT INTO centros_costos (codigo, nombre, descripcion)
    VALUES (p_codigo, p_nombre, p_descripcion);
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_all_centros_costos;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_centros_costos`(
    IN p_codigo VARCHAR(20),
    IN p_nombre VARCHAR(255)
)
BEGIN
    SELECT * FROM centros_costos
    WHERE
        (p_codigo IS NULL OR p_codigo = '' OR codigo LIKE CONCAT('%', p_codigo, '%'))
        AND (p_nombre IS NULL OR p_nombre = '' OR nombre LIKE CONCAT('%', p_nombre, '%'));
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_centro_costo_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_centro_costo_by_id`(IN p_id INT)
BEGIN
    SELECT * FROM centros_costos WHERE id = p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_update_centro_costo;
DELIMITER $$
CREATE PROCEDURE `sp_update_centro_costo`(
    IN p_id INT,
    IN p_nombre VARCHAR(255),
    IN p_descripcion TEXT,
    IN p_estado BOOLEAN
)
BEGIN
    UPDATE centros_costos
    SET
        nombre = p_nombre,
        descripcion = p_descripcion,
        estado = p_estado
    WHERE id = p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_delete_centro_costo;
DELIMITER $$
CREATE PROCEDURE `sp_delete_centro_costo`(IN p_id INT)
BEGIN
    UPDATE centros_costos SET estado = FALSE WHERE id = p_id;
END$$
DELIMITER ;

-- Conceptos
DROP PROCEDURE IF EXISTS sp_create_concepto;
DELIMITER $$
CREATE PROCEDURE `sp_create_concepto`(
    IN p_codigo VARCHAR(20),
    IN p_nombre VARCHAR(255),
    IN p_descripcion TEXT,
    IN p_tipo ENUM('INGRESO', 'GASTO')
)
BEGIN
    INSERT INTO conceptos (codigo, nombre, descripcion, tipo)
    VALUES (p_codigo, p_nombre, p_descripcion, p_tipo);
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_all_conceptos;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_conceptos`(
    IN p_codigo VARCHAR(20),
    IN p_nombre VARCHAR(255),
    IN p_tipo VARCHAR(10)
)
BEGIN
    SELECT * FROM conceptos
    WHERE
        (p_codigo IS NULL OR p_codigo = '' OR codigo LIKE CONCAT('%', p_codigo, '%'))
        AND (p_nombre IS NULL OR p_nombre = '' OR nombre LIKE CONCAT('%', p_nombre, '%'))
        AND (p_tipo IS NULL OR p_tipo = '' OR tipo = p_tipo);
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_concepto_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_concepto_by_id`(IN p_id INT)
BEGIN
    SELECT * FROM conceptos WHERE id = p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_update_concepto;
DELIMITER $$
CREATE PROCEDURE `sp_update_concepto`(
    IN p_id INT,
    IN p_nombre VARCHAR(255),
    IN p_descripcion TEXT,
    IN p_tipo ENUM('INGRESO', 'GASTO'),
    IN p_estado BOOLEAN
)
BEGIN
    UPDATE conceptos
    SET
        nombre = p_nombre,
        descripcion = p_descripcion,
        tipo = p_tipo,
        estado = p_estado
    WHERE id = p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_delete_concepto;
DELIMITER $$
CREATE PROCEDURE `sp_delete_concepto`(IN p_id INT)
BEGIN
    UPDATE conceptos SET estado = FALSE WHERE id = p_id;
END$$
DELIMITER ;

-- Tipos de Auxiliar
DROP PROCEDURE IF EXISTS sp_create_tipo_auxiliar;
DELIMITER $$
CREATE PROCEDURE `sp_create_tipo_auxiliar`(
    IN p_codigo VARCHAR(10),
    IN p_nombre VARCHAR(100),
    IN p_descripcion TEXT
)
BEGIN
    INSERT INTO tipos_auxiliar (codigo, nombre, descripcion)
    VALUES (p_codigo, p_nombre, p_descripcion);
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_all_tipos_auxiliar;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_tipos_auxiliar`(
    IN p_codigo VARCHAR(10),
    IN p_nombre VARCHAR(100)
)
BEGIN
    SELECT * FROM tipos_auxiliar
    WHERE
        (p_codigo IS NULL OR p_codigo = '' OR codigo LIKE CONCAT('%', p_codigo, '%'))
        AND (p_nombre IS NULL OR p_nombre = '' OR nombre LIKE CONCAT('%', p_nombre, '%'));
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_tipo_auxiliar_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_tipo_auxiliar_by_id`(IN p_id INT)
BEGIN
    SELECT * FROM tipos_auxiliar WHERE id = p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_update_tipo_auxiliar;
DELIMITER $$
CREATE PROCEDURE `sp_update_tipo_auxiliar`(
    IN p_id INT,
    IN p_nombre VARCHAR(100),
    IN p_descripcion TEXT,
    IN p_estado BOOLEAN
)
BEGIN
    UPDATE tipos_auxiliar
    SET
        nombre = p_nombre,
        descripcion = p_descripcion,
        estado = p_estado
    WHERE id = p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_delete_tipo_auxiliar;
DELIMITER $$
CREATE PROCEDURE `sp_delete_tipo_auxiliar`(IN p_id INT)
BEGIN
    UPDATE tipos_auxiliar SET estado = FALSE WHERE id = p_id;
END$$
DELIMITER ;

-- Auxiliares
DROP PROCEDURE IF EXISTS sp_create_auxiliar;
DELIMITER $$
CREATE PROCEDURE `sp_create_auxiliar`(
    IN p_id_tipo_auxiliar INT,
    IN p_id_tipo_documento_identidad INT,
    IN p_num_doc_identidad VARCHAR(20),
    IN p_razon_social_nombres VARCHAR(255),
    IN p_direccion VARCHAR(255),
    IN p_telefono VARCHAR(50),
    IN p_email VARCHAR(100),
    IN p_ubigeo VARCHAR(6),
    IN p_TipoERP CHAR(1),
    IN p_CodigoERP VARCHAR(5)
)
BEGIN
    INSERT INTO auxiliares (id_tipo_auxiliar, id_tipo_documento_identidad, num_doc_identidad, razon_social_nombres, direccion, telefono, email, ubigeo, TipoERP, CodigoERP)
    VALUES (p_id_tipo_auxiliar, p_id_tipo_documento_identidad, p_num_doc_identidad, p_razon_social_nombres, p_direccion, p_telefono, p_email, p_ubigeo, p_TipoERP, p_CodigoERP);
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_all_auxiliares;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_auxiliares`(
    IN p_nombre VARCHAR(255),
    IN p_num_doc VARCHAR(20),
    IN p_tipo_aux INT,
    IN p_tipo_erp CHAR(1),
    IN p_codigo_erp VARCHAR(5)
)
BEGIN
    SELECT a.*, ta.nombre as nombre_tipo_auxiliar
    FROM auxiliares a
    JOIN tipos_auxiliar ta ON a.id_tipo_auxiliar = ta.id
    WHERE
        (p_nombre IS NULL OR p_nombre = '' OR a.razon_social_nombres LIKE CONCAT('%', p_nombre, '%'))
        AND (p_num_doc IS NULL OR p_num_doc = '' OR a.num_doc_identidad LIKE CONCAT('%', p_num_doc, '%'))
        AND (p_tipo_aux IS NULL OR p_tipo_aux = 0 OR a.id_tipo_auxiliar = p_tipo_aux)
        AND (p_tipo_erp IS NULL OR p_tipo_erp = '' OR a.TipoERP LIKE CONCAT('%', p_tipo_erp, '%'))
        AND (p_codigo_erp IS NULL OR p_codigo_erp = '' OR a.CodigoERP LIKE CONCAT('%', p_codigo_erp, '%'));
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_auxiliar_by_id;
CREATE PROCEDURE `sp_read_auxiliar_by_id`(IN p_id INT)
BEGIN
    SELECT id, id_tipo_auxiliar, tipo_doc_identidad, num_doc_identidad, razon_social_nombres, direccion, telefono, email, estado, TipoERP, CodigoERP, id_tipo_documento_identidad, ubigeo FROM auxiliares WHERE id = p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_update_auxiliar;
DELIMITER $$
CREATE PROCEDURE `sp_update_auxiliar`(
    IN p_id INT,
    IN p_id_tipo_auxiliar INT,
    IN p_id_tipo_documento_identidad INT,
    IN p_num_doc_identidad VARCHAR(20),
    IN p_razon_social_nombres VARCHAR(255),
    IN p_direccion VARCHAR(255),
    IN p_telefono VARCHAR(50),
    IN p_email VARCHAR(100),
    IN p_ubigeo VARCHAR(6),
    IN p_estado BOOLEAN,
    IN p_TipoERP CHAR(1),
    IN p_CodigoERP VARCHAR(5)
)
BEGIN
    UPDATE auxiliares
    SET
        id_tipo_auxiliar = p_id_tipo_auxiliar,
        id_tipo_documento_identidad = p_id_tipo_documento_identidad,
        num_doc_identidad = p_num_doc_identidad,
        razon_social_nombres = p_razon_social_nombres,
        direccion = p_direccion,
        telefono = p_telefono,
        email = p_email,
        ubigeo = p_ubigeo,
        estado = p_estado,
        TipoERP = p_TipoERP,
        CodigoERP = p_CodigoERP
    WHERE id = p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_delete_auxiliar;
DELIMITER $$
CREATE PROCEDURE `sp_delete_auxiliar`(IN p_id INT)
BEGIN
    UPDATE auxiliares SET estado = FALSE WHERE id = p_id;
END$$
DELIMITER ;

-- Tipos de Cambio
DROP PROCEDURE IF EXISTS sp_create_tipo_cambio;
DELIMITER $$
CREATE PROCEDURE `sp_create_tipo_cambio`(
    IN p_fecha DATE,
    IN p_compra DECIMAL(10, 4),
    IN p_venta DECIMAL(10, 4)
)
BEGIN
    INSERT INTO tipos_cambio (fecha, compra, venta)
    VALUES (p_fecha, p_compra, p_venta);
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_all_tipos_cambio;
DELIMITER $$
CREATE PROCEDURE `sp_read_all_tipos_cambio`(
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE
)
BEGIN
    SELECT * FROM tipos_cambio
    WHERE
        (p_fecha_inicio IS NULL OR fecha >= p_fecha_inicio)
        AND (p_fecha_fin IS NULL OR fecha <= p_fecha_fin)
    ORDER BY fecha DESC;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_read_tipo_cambio_by_id;
DELIMITER $$
CREATE PROCEDURE `sp_read_tipo_cambio_by_id`(IN p_id INT)
BEGIN
    SELECT * FROM tipos_cambio WHERE id = p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_update_tipo_cambio;
DELIMITER $$
CREATE PROCEDURE `sp_update_tipo_cambio`(
    IN p_id INT,
    IN p_fecha DATE,
    IN p_compra DECIMAL(10, 4),
    IN p_venta DECIMAL(10, 4)
)
BEGIN
    UPDATE tipos_cambio
    SET
        fecha = p_fecha,
        compra = p_compra,
        venta = p_venta
    WHERE id = p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_delete_tipo_cambio;
DELIMITER $$
CREATE PROCEDURE `sp_delete_tipo_cambio`(IN p_id INT)
BEGIN
    DELETE FROM tipos_cambio WHERE id = p_id;
END$$
DELIMITER ;
