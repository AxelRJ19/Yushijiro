<?php
session_name('YUSHIJIRO_REP');
session_start();
require __DIR__ . '/db.php';
header('Content-Type: application/json');

if (empty($_SESSION['rep'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'No autenticado']));
}

// El driver_id viene guardado en la sesión al hacer login
$driverId = $_SESSION['rep']['driver_id'] ?? null;
if (!$driverId) {
    http_response_code(400);
    exit(json_encode(['error' => 'Sin driver_id en sesión']));
}

$stmt = $pdo->prepare("
    SELECT o.*, b.name AS branch_name
    FROM orders o
    LEFT JOIN branches b ON o.branch_id = b.id
    WHERE o.driver_id = ? AND o.status IN ('assigned','on_way')
    ORDER BY o.created_at DESC
");
$stmt->execute([$driverId]);
$list = $stmt->fetchAll();

foreach ($list as &$order) {
    $s = $pdo->prepare('SELECT * FROM order_items WHERE order_id=?');
    $s->execute([$order['id']]);
    $order['items'] = $s->fetchAll();
}

echo json_encode(['ok' => true, 'orders' => $list]);
