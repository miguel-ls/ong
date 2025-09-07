<?php
// Este archivo se encargará de la conexión a la base de datos.
require_once __DIR__ . '/../config/config.php';

function getDbConnection() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (\PDOException $e) {
        // En un entorno de producción, no deberías mostrar el error detallado.
        // Deberías registrar el error y mostrar un mensaje genérico.
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
}
?>
