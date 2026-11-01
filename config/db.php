<?php
/**
 * config/db.php — Conexión PDO centralizada
 * LUXE MOTORS MX
 */
$host = 'localhost';
$db = 'agencia_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // En producción ocultar el mensaje detallado
    $msg = (ini_get('display_errors')) ? $e->getMessage() : 'Error de conexión a la base de datos.';
    http_response_code(500);
    die('<div style="font-family:monospace;background:#1a0000;color:#ff6b6b;padding:20px;border-radius:6px;margin:20px">
            <strong>Error de conexión:</strong> ' . htmlspecialchars($msg) . '
         </div>');
}