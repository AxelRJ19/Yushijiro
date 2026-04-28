<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

// CORS: solo permite los origenes configurados en config.php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, ALLOWED_ORIGINS, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Metodo no permitido']));
}

$body    = json_decode(file_get_contents('php://input'), true);
$message = trim((string)($body['message'] ?? ''));

if (!$message) {
    http_response_code(400);
    exit(json_encode(['error' => 'message requerido']));
}

if (strlen($message) > 2000) {
    http_response_code(400);
    exit(json_encode(['error' => 'Mensaje demasiado largo']));
}

$ch = curl_init(VPS_URL);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode(['message' => $message]),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 120,
]);
$res  = curl_exec($ch);
$err  = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err) {
    http_response_code(502);
    exit(json_encode(['error' => 'Sin conexion con el servidor IA']));
}

http_response_code($code);
echo $res;
