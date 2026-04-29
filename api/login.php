<?php
session_name('YUSHIJIRO_REP');
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Método no permitido']));
}

require __DIR__ . '/db.php';

$body     = json_decode(file_get_contents('php://input'), true);
$username = strtolower(trim((string)($body['user'] ?? '')));
$password = (string)($body['pass'] ?? '');

if (!$username || !$password) {
    http_response_code(400);
    exit(json_encode(['error' => 'Datos incompletos']));
}

$stmt = $pdo->prepare('SELECT d.*, b.name AS branch_name FROM drivers d LEFT JOIN branches b ON d.branch_id = b.id WHERE d.username=?');
$stmt->execute([$username]);
$driver = $stmt->fetch();

if (!$driver || !password_verify($password, $driver['password_hash'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Usuario o contraseña incorrectos']));
}

session_regenerate_id(true);

$_SESSION['rep'] = [
    'driver_id'   => $driver['id'],
    'user'        => $driver['username'],
    'nombre'      => $driver['name'],
    'inicial'     => strtoupper(substr($driver['name'], 0, 1) . substr(strrchr($driver['name'], ' '), 1, 1)),
    'branch'      => $driver['branch_id'],
    'branch_name' => $driver['branch_name'] ?? '',
];

// Marcar como disponible al conectarse
$pdo->prepare("UPDATE drivers SET status='available' WHERE id=?")->execute([$driver['id']]);

exit(json_encode(['ok' => true, 'user' => $_SESSION['rep']]));
