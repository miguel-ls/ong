<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de ONG</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">
                ONG System
            </div>
            <nav>
                <ul>
                    <li><a href="index.php?page=inicio">Inicio</a></li>
                    <li>
                        <a href="#mantenimientoSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">Mantenimiento</a>
                        <ul class="collapse list-unstyled" id="mantenimientoSubmenu">
                            <li><a href="index.php?page=proyectos">Proyectos</a></li>
                            <li><a href="index.php?page=sub_proyectos">Sub Proyectos</a></li>
                            <li><a href="index.php?page=centros_costos">Centros de Costos</a></li>
                            <li><a href="index.php?page=tipos_documento">Tipos de Documento</a></li>
                            <li><a href="index.php?page=conceptos">Conceptos</a></li>
                            <li><a href="index.php?page=tipos_auxiliar">Tipos de Auxiliar</a></li>
                            <li><a href="index.php?page=auxiliares">Auxiliares</a></li>
                            <li><a href="index.php?page=usuarios">Usuarios</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="#operacionesSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">Operaciones</a>
                        <ul class="collapse list-unstyled" id="operacionesSubmenu">
                            <li><a href="index.php?page=ingreso_documentos">Ingreso de documentos</a></li>
                        </ul>
                    </li>
                    <li><a href="index.php?page=reportes">Reportes</a></li>
                </ul>
            </nav>
        </aside>
        <main class="main-content">
</body>
</html>
