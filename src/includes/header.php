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
                        <a href="#mantenimientoSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-cogs"></i> Mantenimiento</a>
                        <ul class="collapse list-unstyled" id="mantenimientoSubmenu">
                            <li><a href="index.php?page=proyectos"><i class="fas fa-project-diagram"></i> Proyectos</a></li>
                            <li><a href="index.php?page=sub_proyectos"><i class="fas fa-sitemap"></i> Sub Proyectos</a></li>
                            <li><a href="index.php?page=centros_costos"><i class="fas fa-bullseye"></i> Centros de Costos</a></li>
                            <li><a href="index.php?page=tipos_documento"><i class="fas fa-file-alt"></i> Tipos de Documento</a></li>
                            <li><a href="index.php?page=conceptos"><i class="fas fa-lightbulb"></i> Conceptos</a></li>
                            <li><a href="index.php?page=tipos_auxiliar"><i class="fas fa-tags"></i> Tipos de Auxiliar</a></li>
                            <li><a href="index.php?page=auxiliares"><i class="fas fa-address-book"></i> Auxiliares</a></li>
                            <li><a href="index.php?page=usuarios"><i class="fas fa-users"></i> Usuarios</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="#operacionesSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-file-invoice-dollar"></i> Operaciones</a>
                        <ul class="collapse list-unstyled" id="operacionesSubmenu">
                            <li><a href="index.php?page=ingreso_documentos"><i class="fas fa-edit"></i> Ingreso de documentos</a></li>
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
