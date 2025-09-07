<?php
// Script para ejecutar desde la línea de comandos: php database/seed.php
require_once __DIR__ . '/../src/database.php';

// --- Configuración del Administrador por Defecto ---
$admin_user = 'admin';
$admin_pass = 'admin1234'; // Cambiar esto en un entorno real
$admin_nombres = 'Administrador';
$admin_apellidos = 'del Sistema';
$admin_email = 'admin@example.com';
// ----------------------------------------------------

echo "Iniciando el proceso de seeding...\n";

try {
    $pdo = getDbConnection();

    // Verificar si el usuario ya existe
    $stmt_check = $pdo->prepare("CALL sp_read_usuario_by_username(?)");
    $stmt_check->execute([$admin_user]);
    $existing_user = $stmt_check->fetch();
    $stmt_check->closeCursor();

    if ($existing_user) {
        echo "El usuario '{$admin_user}' ya existe. No se tomarán acciones.\n";
        exit();
    }

    // Hashear la contraseña
    $hashed_password = password_hash($admin_pass, PASSWORD_DEFAULT);

    // Crear el usuario administrador
    $stmt_create = $pdo->prepare("CALL sp_create_usuario(?, ?, 'administrador', ?, ?, ?, NULL)");
    $stmt_create->execute([
        $admin_user,
        $hashed_password,
        $admin_nombres,
        $admin_apellidos,
        $admin_email
    ]);

    echo "¡Éxito!\n";
    echo "Usuario administrador creado:\n";
    echo "Usuario: " . $admin_user . "\n";
    echo "Contraseña: " . $admin_pass . "\n";

} catch (PDOException $e) {
    echo "Error durante el seeding: " . $e->getMessage() . "\n";
}
?>
