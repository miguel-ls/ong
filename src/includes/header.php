<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de ONG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="main-wrapper">
            <aside class="sidebar">
            <div class="logo">
                <button id="sidebarToggleBtn" class="btn btn-link text-white"><i class="fas fa-bars"></i></button>
                <span class="logo-text">ONG System</span>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php?page=inicio"><i class="fas fa-home"></i><span class="sidebar-item-text"> Inicio</span></a></li>
                    <li>
                        <a href="#mantenimientoSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                            <div><i class="fas fa-cogs"></i><span class="sidebar-item-text"> Mantenimiento</span></div>
                            <i class="fas fa-chevron-down float-indicator"></i>
                        </a>
                        <ul class="collapse list-unstyled" id="mantenimientoSubmenu">
                            <li><a href="index.php?page=proyectos"><span class="sidebar-item-text">Proyectos</span></a></li>
                            <li><a href="index.php?page=sub_proyectos"><span class="sidebar-item-text">Sub Proyectos</span></a></li>
                            <li><a href="index.php?page=centros_costos"><span class="sidebar-item-text">Centros de Costos</span></a></li>
                            <li><a href="index.php?page=conceptos"><span class="sidebar-item-text">Conceptos</span></a></li>
                            <li><a href="index.php?page=tipos_documento"><span class="sidebar-item-text">Tipos de Documento</span></a></li>
                            <li><a href="index.php?page=tipos_documento_identidad"><span class="sidebar-item-text">Tipos de Doc. Identidad</span></a></li>
                            <li><a href="index.php?page=tipos_auxiliar"><span class="sidebar-item-text">Tipos de Auxiliar</span></a></li>
                            <li><a href="index.php?page=auxiliares"><span class="sidebar-item-text">Auxiliares</span></a></li>
                            <li><a href="index.php?page=usuarios"><span class="sidebar-item-text">Usuarios</span></a></li>
                            <li><a href="index.php?page=tipos_cambio"><span class="sidebar-item-text">Tipo de Cambio</span></a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="#operacionesSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                            <div><i class="fas fa-file-invoice-dollar"></i><span class="sidebar-item-text"> Operaciones</span></div>
                            <i class="fas fa-chevron-down float-indicator"></i>
                        </a>
                        <ul class="collapse list-unstyled" id="operacionesSubmenu">
                            <li><a href="index.php?page=ingreso_documentos"><span class="sidebar-item-text">Ingreso de documentos</span></a></li>
                        </ul>
                    </li>
                    <li><a href="index.php?page=reportes"><i class="fas fa-chart-bar"></i><span class="sidebar-item-text"> Reportes</span></a></li>
                    <hr>
                    <li><a href="index.php?page=perfil"><i class="fas fa-user-circle"></i><span class="sidebar-item-text"> Mi Perfil</span></a></li>
                    <li><a href="../src/actions/logout_process.php"><i class="fas fa-sign-out-alt"></i><span class="sidebar-item-text"> Cerrar Sesión</span></a></li>
                </ul>
            </nav>
        </aside>
        <main class="main-content">
</body>
</html>
