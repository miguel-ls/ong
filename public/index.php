<?php
session_start();

// Si el usuario no está logueado, redirigir a la página de login.
// Todas las páginas manejadas por este enrutador son protegidas.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Incluir el header
require_once('../src/includes/header.php');

// Lógica de enrutamiento simple
$page = isset($_GET['page']) ? $_GET['page'] : 'inicio';
$page_path = "../src/pages/{$page}.php";

if (file_exists($page_path)) {
    require_once($page_path);
} else {
    // Página de error 404 simple
    echo "<header><h1>Error 404</h1></header>";
    echo "<p>Página no encontrada.</p>";
}

// Incluir el footer
require_once('../src/includes/footer.php');
?>
