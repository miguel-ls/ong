<?php
session_start();

// Si no hay un login de 2FA pendiente, no se debería estar aquí.
if (!isset($_SESSION['2fa_pending_user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Dos Factores</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7fa; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background-color: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 320px; text-align: center; }
        .login-container h1 { color: #004a99; margin-bottom: 10px; }
        .login-container p { margin-bottom: 20px; color: #555; }
        .login-container label { display: block; margin-bottom: 8px; font-weight: bold; }
        .login-container input { width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; text-align: center; font-size: 1.2em; }
        .login-container button { width: 100%; padding: 12px; background-color: #005cb3; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .login-container button:hover { background-color: #004a99; }
        .error-message { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Verificación de Dos Factores</h1>
        <p>Abre tu aplicación de autenticación e introduce el código de 6 dígitos.</p>
        <?php
        if (isset($_GET['error'])) {
            echo '<div class="error-message">Código incorrecto. Inténtalo de nuevo.</div>';
        }
        ?>
        <form action="../src/actions/2fa_verify_process.php" method="POST">
            <label for="one_time_password">Código de Verificación</label>
            <input type="text" id="one_time_password" name="one_time_password" required autofocus autocomplete="off" maxlength="6">
            <button type="submit">Verificar e Ingresar</button>
        </form>
    </div>
</body>
</html>
