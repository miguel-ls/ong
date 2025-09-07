<?php
// Este archivo requiere que las dependencias de Composer estén instaladas.
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../database.php';

use PragmaRX\Google2FAQRCode\Google2FA;

// Asegurarse de que el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../public/login.php');
    exit();
}

$google2fa = new Google2FA();

// Generar un nuevo secreto para el usuario
$secretKey = $google2fa->generateSecretKey();
$_SESSION['2fa_temp_secret'] = $secretKey; // Guardar en sesión temporalmente

// Obtener información del usuario para el QR code
$user_id = $_SESSION['user_id'];
try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("CALL sp_read_usuario_by_id(?)");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    die("Error al obtener datos del usuario: " . $e->getMessage());
}

$appName = 'ONG Document Manager';
$userEmail = $user['email'];

// Generar el QR code
$qrCodeUrl = $google2fa->getQRCodeUrl($appName, $userEmail, $secretKey);

// Crear el QR code como una imagen inline (data URI)
$qrCodeImage = (new \chillerlan\QRCode\QRCode)->render($qrCodeUrl);

?>

<style>
.setup-container { max-width: 600px; text-align: center; }
.qr-code { margin: 20px auto; padding: 10px; background: white; display: inline-block; }
.secret-key { font-weight: bold; background-color: #eee; padding: 5px; border-radius: 4px; display: inline-block; margin-top: 10px; }
.form-group { margin-top: 20px; }
.form-group input { padding: 10px; font-size: 1.2em; text-align: center; width: 200px; }
.btn-verify { background-color: #005cb3; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px; }
</style>

<header>
    <h1>Configurar Autenticación de Dos Factores</h1>
</header>

<section class="setup-container">
    <p>Siga estos pasos para activar la 2FA:</p>
    <ol style="text-align: left;">
        <li>Instale una aplicación de autenticación en su teléfono (ej. Google Authenticator, Authy, etc.).</li>
        <li>Escanee el código QR a continuación con la aplicación.</li>
        <li>La aplicación le dará un código de 6 dígitos. Introdúzcalo en el campo de abajo para verificar y completar la configuración.</li>
    </ol>

    <div class="qr-code">
        <img src="<?= htmlspecialchars($qrCodeImage) ?>" alt="QR Code">
    </div>

    <p>Si no puede escanear el código, puede introducir manually esta clave:</p>
    <p class="secret-key"><?= htmlspecialchars($secretKey) ?></p>

    <form action="../src/actions/2fa_setup_process.php" method="POST">
        <div class="form-group">
            <label for="one_time_password">Código de Verificación de 6 dígitos</label><br>
            <input type="text" id="one_time_password" name="one_time_password" required autocomplete="off" maxlength="6">
        </div>
        <button type="submit" class="btn-verify">Verificar y Activar</button>
    </form>
</section>
