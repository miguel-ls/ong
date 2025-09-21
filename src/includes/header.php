<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de ONG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx-js-style/dist/xlsx.bundle.js"></script>
</head>
<body>
    <div class="container-fluid">
        <div class="main-wrapper">
            <aside class="sidebar">
            <div class="logo">
                <button id="sidebarToggleBtn" class="btn btn-link text-white"><i class="fas fa-bars"></i></button>
                <span class="logo-text">ERPPlus GesDoc ®</span>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php?page=inicio"><i class="fas fa-home"></i><span class="sidebar-item-text"> Inicio</span></a></li>
                    <li>
                        <a href="#mantenimientoSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                            <div><i class="fas fa-cogs"></i><span class="sidebar-item-text"> Mantenimiento</span></div>

                        </a>
                        <ul class="collapse list-unstyled" id="mantenimientoSubmenu">
                            <li><a href="index.php?page=proyectos"><span class="sidebar-item-text">Proyectos</span></a></li>
                            <li><a href="index.php?page=sub_proyectos"><span class="sidebar-item-text">Sub Proyectos</span></a></li>
                            <li><a href="index.php?page=centros_costos"><span class="sidebar-item-text">Centros de Costos</span></a></li>
                            <li><a href="index.php?page=conceptos"><span class="sidebar-item-text">Conceptos</span></a></li>
                            <li><a href="index.php?page=tipos_documento"><span class="sidebar-item-text">Tipos de Documento</span></a></li>
                            <li><a href="index.php?page=tipos_documento_identidad"><span class="sidebar-item-text">Tipos de Doc. Identidad</span></a></li>
                            <li><a href="index.php?page=tipos_auxiliar"><span class="sidebar-item-text">Tipos de Auxiliar</span></a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="#operacionesSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                            <div><i class="fas fa-file-invoice-dollar"></i><span class="sidebar-item-text"> Operaciones</span></div>

                        </a>
                        <ul class="collapse list-unstyled" id="operacionesSubmenu">
                            <li><a href="index.php?page=tipos_cambio"><span class="sidebar-item-text">Tipo de Cambio</span></a></li>                            
                            <li><a href="index.php?page=auxiliares"><span class="sidebar-item-text">Auxiliares</span></a></li>                            
                            <li><a href="index.php?page=ingreso_documentos"><span class="sidebar-item-text">Ingreso de Documentos</span></a></li>
                        </ul>
                    </li>


                    <li>
                        <a href="#reportesSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                            <div><i class="fas fa-chart-bar"></i><span class="sidebar-item-text"> Reportes</span></div>

                        </a>
                        <ul class="collapse list-unstyled" id="reportesSubmenu">
                            <li><a href="index.php?page=reportes"><span class="sidebar-item-text"> Reportes Dinamicos</span></a></li>
                        </ul>
                    </li>


                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'administrador'): ?>
                    <li>
                        <a href="#seguridadSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                            <div><i class="fas fa-users"></i><span class="sidebar-item-text"> Seguridad</span></div>

                        </a>
                        <ul class="collapse list-unstyled" id="seguridadSubmenu">
                            <li><a href="index.php?page=usuarios"><span class="sidebar-item-text">Usuarios</span></a></li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <hr>
                    <li><a href="index.php?page=perfil"><i class="fas fa-user-circle"></i><span class="sidebar-item-text"> Mi Perfil</span></a></li>
                    <li><a href="../src/actions/logout_process.php"><i class="fas fa-sign-out-alt"></i><span class="sidebar-item-text"> Cerrar Sesión</span></a></li>
                </ul>
            </nav>
        </aside>
        <main class="main-content">
</body>
</html>
