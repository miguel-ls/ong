<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Gestión</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7fa; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background-color: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 320px; }
        .login-container h1 { text-align: center; color: #004a99; margin-bottom: 20px; }
        .login-container label { display: block; margin-bottom: 8px; font-weight: bold; }
        .login-container input { width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .login-container button { width: 100%; padding: 12px; background-color: #005cb3; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .login-container button:hover { background-color: #004a99; }
        .error-message { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="mb-3">
            <img src="images/LOGO.jpg" alt="Logo del Sistema" style="width: 80px; height: 80px; border-radius: 50%; display: block; margin-left: auto; margin-right: auto;">
        </div>
        <h1>Iniciar Sesión</h1>
        <?php
        if (isset($_GET['error'])) {
            echo '<div class="error-message">' . htmlspecialchars($_GET['error']) . '</div>';
        }
        ?>
        <form action="../src/actions/login_process.php" method="POST">
            <label for="username">Usuario</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Ingresar</button>
        </form>
    </div>
</body>
</html>
