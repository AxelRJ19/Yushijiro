<?php
// Lee credenciales del archivo local (gitignoreado)
// En producción: crea api/db-config.php con las credenciales de Hostinger
if (file_exists(__DIR__ . '/db-config.php')) {
    require __DIR__ . '/db-config.php';
} else {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'yushijiro');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER, DB_PASS,
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);
