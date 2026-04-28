<?php
session_name('YUSHIJIRO_PANEL');
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Metodo no permitido']));
}

$configFile = __DIR__ . '/../config.php';
if (!file_exists($configFile)) {
    http_response_code(503);
    exit(json_encode(['error' => 'Servidor no configurado']));
}
require $configFile;

if (!defined('PANEL_USER') || !defined('PANEL_PASS_HASH') || !PANEL_PASS_HASH) {
    http_response_code(503);
    exit(json_encode(['error' => 'Panel no configurado. Ejecuta: php setup.php']));
}

$body = json_decode(file_get_contents('php://input'), true);
$user = trim((string)($body['user'] ?? ''));
$pass = (string)($body['pass'] ?? '');

if (!$user || !$pass) {
    http_response_code(400);
    exit(json_encode(['error' => 'Datos incompletos']));
}

if (!hash_equals(PANEL_USER, $user) || !password_verify($pass, PANEL_PASS_HASH)) {
    http_response_code(401);
    exit(json_encode(['error' => 'Credenciales incorrectas']));
}

session_regenerate_id(true);
$_SESSION['panel'] = ['user' => $user, 'ts' => time()];
exit(json_encode(['ok' => true]));
