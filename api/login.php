<?php
session_name('YUSHIJIRO_REP');
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Metodo no permitido']));
}

$body     = json_decode(file_get_contents('php://input'), true);
$username = strtolower(trim((string)($body['user'] ?? '')));
$password = (string)($body['pass'] ?? '');

if (!$username || !$password) {
    http_response_code(400);
    exit(json_encode(['error' => 'Datos incompletos']));
}

$usersFile = __DIR__ . '/../users.php';
if (!file_exists($usersFile)) {
    error_log('Yushijiro: users.php no encontrado. Ejecuta setup.php');
    http_response_code(503);
    exit(json_encode(['error' => 'Servidor no configurado. Contacta al administrador.']));
}

$users = require $usersFile;

if (!isset($users[$username]) || !password_verify($password, $users[$username]['pass'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Usuario o contrasena incorrectos']));
}

session_regenerate_id(true);

$u = $users[$username];
$_SESSION['rep'] = [
    'user'    => $username,
    'nombre'  => $u['nombre'],
    'inicial' => $u['inicial'],
    'branch'  => $u['branch'],
];

exit(json_encode(['ok' => true, 'user' => $_SESSION['rep']]));
