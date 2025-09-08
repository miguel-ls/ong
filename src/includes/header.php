<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de ONG</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">
                ONG System
            </div>
            <nav>
                <ul>
                    <li><a href="index.php?page=inicio"><i class="fas fa-home"></i> Inicio</a></li>
                    <li>
                        <a href="#mantenimientoSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                            <span><i class="fas fa-cogs"></i> Mantenimiento</span>
                            <i class="fas fa-chevron-down float-indicator"></i>
                        </a>
                        <ul class="collapse list-unstyled" id="mantenimientoSubmenu">
                            <li><a href="index.php?page=proyectos">Proyectos</a></li>
                            <li><a href="index.php?page=sub_proyectos">Sub Proyectos</a></li>
                            <li><a href="index.php?page=centros_costos">Centros de Costos</a></li>
                            <li><a href="index.php?page=conceptos">Conceptos</a></li>
                            <li><a href="index.php?page=tipos_documento">Tipos de Documento</a></li>
                            <li><a href="index.php?page=tipos_documento_identidad">Tipos de Doc. Identidad</a></li>
                            <li><a href="index.php?page=tipos_auxiliar">Tipos de Auxiliar</a></li>
                            <li><a href="index.php?page=auxiliares">Auxiliares</a></li>
                            <li><a href="index.php?page=usuarios">Usuarios</a></li>
                            <li><a href="index.php?page=tipos_cambio">Tipo de Cambio</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="#operacionesSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                            <span><i class="fas fa-file-invoice-dollar"></i> Operaciones</span>
                            <i class="fas fa-chevron-down float-indicator"></i>
                        </a>
                        <ul class="collapse list-unstyled" id="operacionesSubmenu">
                            <li><a href="index.php?page=ingreso_documentos">Ingreso de documentos</a></li>
                        </ul>
                    </li>
                    <li><a href="index.php?page=reportes"><i class="fas fa-chart-bar"></i> Reportes</a></li>
                    <hr>
                    <li><a href="index.php?page=perfil"><i class="fas fa-user-circle"></i> Mi Perfil</a></li>
                    <li><a href="../src/actions/logout_process.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
                </ul>
            </nav>
        </aside>
        <main class="main-content">
</body>
</html>
