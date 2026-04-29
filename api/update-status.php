<?php
require __DIR__ . '/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$b         = json_decode(file_get_contents('php://input'), true);
$orderId   = (int)($b['order_id'] ?? 0);
$newStatus = $b['status']    ?? '';
$driverId  = $b['driver_id'] ?? null;

$allowed = ['pending', 'assigned', 'on_way', 'delivered', 'cancelled'];
if (!$orderId || !in_array($newStatus, $allowed)) {
    http_response_code(400);
    exit(json_encode(['error' => 'Datos inválidos']));
}

// Obtener pedido actual para saber el driver anterior
$order = $pdo->prepare('SELECT driver_id FROM orders WHERE id=?');
$order->execute([$orderId]);
$current = $order->fetch();

if ($driverId) {
    // Reasignar repartidor desde el panel
    $pdo->prepare('UPDATE orders SET status=?, driver_id=?, updated_at=NOW() WHERE id=?')
        ->execute([$newStatus, $driverId, $orderId]);
    // Liberar driver anterior si era distinto
    if ($current && $current['driver_id'] && $current['driver_id'] != $driverId) {
        $pdo->prepare("UPDATE drivers SET status='available' WHERE id=?")
            ->execute([$current['driver_id']]);
    }
    $pdo->prepare("UPDATE drivers SET status='busy' WHERE id=?")->execute([$driverId]);
} else {
    $pdo->prepare('UPDATE orders SET status=?, updated_at=NOW() WHERE id=?')
        ->execute([$newStatus, $orderId]);
}

// Al entregar o cancelar, liberar al repartidor
if (in_array($newStatus, ['delivered', 'cancelled'])) {
    $driverToFree = $driverId ?? ($current['driver_id'] ?? null);
    if ($driverToFree) {
        $pdo->prepare("UPDATE drivers SET status='available' WHERE id=?")
            ->execute([$driverToFree]);
    }
}

echo json_encode(['ok' => true]);
