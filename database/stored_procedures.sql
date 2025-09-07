-- Procedimientos Almacenados para la tabla de Usuarios

-- Crear un nuevo usuario
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

-- Leer todos los usuarios
DELIMITER $$
CREATE PROCEDURE `sp_read_all_usuarios`()
BEGIN
    SELECT id, nombre_usuario, rol, nombres, apellidos, email, telefono, estado, is_2fa_enabled FROM usuarios;
END$$
DELIMITER ;

-- Leer un usuario por ID
DELIMITER $$
CREATE PROCEDURE `sp_read_usuario_by_id`(IN p_id INT)
BEGIN
    SELECT id, nombre_usuario, rol, nombres, apellidos, email, telefono, estado, secret_2fa, is_2fa_enabled FROM usuarios WHERE id = p_id;
END$$
DELIMITER ;

-- Leer un usuario por nombre de usuario (para login)
DELIMITER $$
CREATE PROCEDURE `sp_read_usuario_by_username`(IN p_nombre_usuario VARCHAR(50))
BEGIN
    SELECT * FROM usuarios WHERE nombre_usuario = p_nombre_usuario;
END$$
DELIMITER ;

-- Actualizar un usuario
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

-- Eliminar un usuario (cambio de estado)
DELIMITER $$
CREATE PROCEDURE `sp_delete_usuario`(IN p_id INT)
BEGIN
    UPDATE usuarios SET estado = FALSE WHERE id = p_id;
END$$
DELIMITER ;

-- Actualizar el secreto de 2FA para un usuario
-- Actualizar la configuración de 2FA para un usuario
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

-- Procedimientos Almacenados para la tabla de Proyectos
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

DELIMITER $$
CREATE PROCEDURE `sp_read_all_proyectos`()
BEGIN
    SELECT * FROM proyectos;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE `sp_read_proyecto_by_id`(IN p_id INT)
BEGIN
    SELECT * FROM proyectos WHERE id = p_id;
END$$
DELIMITER ;

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

DELIMITER $$
CREATE PROCEDURE `sp_delete_proyecto`(IN p_id INT)
BEGIN
    UPDATE proyectos SET estado = FALSE WHERE id = p_id;
END$$
DELIMITER ;


-- Procedimientos Almacenados para la tabla de Tipos de Documento
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

DELIMITER $$
CREATE PROCEDURE `sp_read_all_tipos_documento`()
BEGIN
    SELECT * FROM tipos_documento;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE `sp_read_tipo_documento_by_id`(IN p_id INT)
BEGIN
    SELECT * FROM tipos_documento WHERE id = p_id;
END$$
DELIMITER ;

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

DELIMITER $$
CREATE PROCEDURE `sp_delete_tipo_documento`(IN p_id INT)
BEGIN
    UPDATE tipos_documento SET estado = FALSE WHERE id = p_id;
END$$
DELIMITER ;

-- Procedimientos Almacenados para la tabla de Sub Proyectos
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

DELIMITER $$
CREATE PROCEDURE `sp_read_all_sub_proyectos`()
BEGIN
    SELECT sp.*, p.nombre as nombre_proyecto
    FROM sub_proyectos sp
    JOIN proyectos p ON sp.id_proyecto = p.id;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE `sp_read_sub_proyecto_by_id`(IN p_id INT)
BEGIN
    SELECT * FROM sub_proyectos WHERE id = p_id;
END$$
DELIMITER ;

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

DELIMITER $$
CREATE PROCEDURE `sp_delete_sub_proyecto`(IN p_id INT)
BEGIN
    UPDATE sub_proyectos SET estado = FALSE WHERE id = p_id;
END$$
DELIMITER ;

-- Procedimientos Almacenados para la tabla de Centros de Costos
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

DELIMITER $$
CREATE PROCEDURE `sp_read_all_centros_costos`()
BEGIN
    SELECT * FROM centros_costos;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE `sp_read_centro_costo_by_id`(IN p_id INT)
BEGIN
    SELECT * FROM centros_costos WHERE id = p_id;
END$$
DELIMITER ;

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

DELIMITER $$
CREATE PROCEDURE `sp_delete_centro_costo`(IN p_id INT)
BEGIN
    UPDATE centros_costos SET estado = FALSE WHERE id = p_id;
END$$
DELIMITER ;

-- Procedimientos Almacenados para la tabla de Conceptos
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

DELIMITER $$
CREATE PROCEDURE `sp_read_all_conceptos`()
BEGIN
    SELECT * FROM conceptos;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE `sp_read_concepto_by_id`(IN p_id INT)
BEGIN
    SELECT * FROM conceptos WHERE id = p_id;
END$$
DELIMITER ;

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

DELIMITER $$
CREATE PROCEDURE `sp_delete_concepto`(IN p_id INT)
BEGIN
    UPDATE conceptos SET estado = FALSE WHERE id = p_id;
END$$
DELIMITER ;

-- Procedimientos Almacenados para la tabla de Tipos de Auxiliar
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

DELIMITER $$
CREATE PROCEDURE `sp_read_all_tipos_auxiliar`()
BEGIN
    SELECT * FROM tipos_auxiliar;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE `sp_read_tipo_auxiliar_by_id`(IN p_id INT)
BEGIN
    SELECT * FROM tipos_auxiliar WHERE id = p_id;
END$$
DELIMITER ;

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

DELIMITER $$
CREATE PROCEDURE `sp_delete_tipo_auxiliar`(IN p_id INT)
BEGIN
    UPDATE tipos_auxiliar SET estado = FALSE WHERE id = p_id;
END$$
DELIMITER ;

-- Procedimientos Almacenados para la tabla de Auxiliares
DELIMITER $$
CREATE PROCEDURE `sp_create_auxiliar`(
    IN p_id_tipo_auxiliar INT,
    IN p_tipo_doc_identidad ENUM('RUC', 'DNI', 'CE', 'PASAPORTE', 'OTRO'),
    IN p_num_doc_identidad VARCHAR(20),
    IN p_razon_social_nombres VARCHAR(255),
    IN p_direccion VARCHAR(255),
    IN p_telefono VARCHAR(50),
    IN p_email VARCHAR(100)
)
BEGIN
    INSERT INTO auxiliares (id_tipo_auxiliar, tipo_doc_identidad, num_doc_identidad, razon_social_nombres, direccion, telefono, email)
    VALUES (p_id_tipo_auxiliar, p_tipo_doc_identidad, p_num_doc_identidad, p_razon_social_nombres, p_direccion, p_telefono, p_email);
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE `sp_read_all_auxiliares`()
BEGIN
    SELECT a.*, ta.nombre as nombre_tipo_auxiliar
    FROM auxiliares a
    JOIN tipos_auxiliar ta ON a.id_tipo_auxiliar = ta.id;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE `sp_read_auxiliar_by_id`(IN p_id INT)
BEGIN
    SELECT * FROM auxiliares WHERE id = p_id;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE `sp_update_auxiliar`(
    IN p_id INT,
    IN p_id_tipo_auxiliar INT,
    IN p_tipo_doc_identidad ENUM('RUC', 'DNI', 'CE', 'PASAPORTE', 'OTRO'),
    IN p_num_doc_identidad VARCHAR(20),
    IN p_razon_social_nombres VARCHAR(255),
    IN p_direccion VARCHAR(255),
    IN p_telefono VARCHAR(50),
    IN p_email VARCHAR(100),
    IN p_estado BOOLEAN
)
BEGIN
    UPDATE auxiliares
    SET
        id_tipo_auxiliar = p_id_tipo_auxiliar,
        tipo_doc_identidad = p_tipo_doc_identidad,
        num_doc_identidad = p_num_doc_identidad,
        razon_social_nombres = p_razon_social_nombres,
        direccion = p_direccion,
        telefono = p_telefono,
        email = p_email,
        estado = p_estado
    WHERE id = p_id;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE `sp_delete_auxiliar`(IN p_id INT)
BEGIN
    UPDATE auxiliares SET estado = FALSE WHERE id = p_id;
END$$
DELIMITER ;
