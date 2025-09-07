<?php
// Este archivo se encargará de la conexión a la base de datos.
require_once __DIR__ . '/../config/config.php';

function getDbConnection() {
    static $pdo = null;

    // Si hay una conexión, verifica si sigue activa. Si no, la descarta para crear una nueva.
    if ($pdo !== null) {
        try {
            // Usa una consulta ligera para verificar la conexión.
            // Se suprime el warning con @ porque se espera que esta consulta pueda fallar
            // y se maneja la excepción para restablecer la conexión.
            @$pdo->query('SELECT 1');
        } catch (\PDOException $e) {
            // La conexión se ha perdido. Anula el objeto PDO para forzar la reconexión.
            $pdo = null;
        }
    }

    // Si no hay conexión (o se perdió), crea una nueva.
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (\PDOException $e) {
            // En un entorno de producción, no deberías mostrar el error detallado.
            // Deberías registrar el error y mostrar un mensaje genérico.
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    return $pdo;
}
?>
