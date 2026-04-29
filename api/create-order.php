<?php
require __DIR__ . '/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'POST requerido']));
}

$b = json_decode(file_get_contents('php://input'), true);

$required = ['customer_name', 'customer_phone', 'customer_address', 'branch_id', 'payment_method', 'items'];
foreach ($required as $k) {
    if (empty($b[$k])) {
        http_response_code(400);
        exit(json_encode(['error' => "Campo requerido: $k"]));
    }
}

if (!in_array($b['payment_method'], ['cash', 'card'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'payment_method debe ser cash o card']));
}

if (!is_array($b['items']) || count($b['items']) === 0) {
    http_response_code(400);
    exit(json_encode(['error' => 'El pedido no tiene items']));
}

// Calcular total
$subtotal = 0;
foreach ($b['items'] as $item) {
    $subtotal += (float)$item['unit_price'] * (int)$item['qty'];
}
$total = $subtotal;

// Cambio si paga en efectivo
$cashReceived = null;
$change       = null;
if ($b['payment_method'] === 'cash' && isset($b['cash_received'])) {
    $cashReceived = (float)$b['cash_received'];
    if ($cashReceived < $total) {
        http_response_code(400);
        exit(json_encode(['error' => 'El monto recibido es menor al total']));
    }
    $change = round($cashReceived - $total, 2);
}

// Buscar repartidor disponible en esa sucursal
$stmt = $pdo->prepare("SELECT id FROM drivers WHERE branch_id=? AND status='available' LIMIT 1");
$stmt->execute([$b['branch_id']]);
$driver   = $stmt->fetch();
$driverId = $driver ? $driver['id'] : null;
$status   = $driverId ? 'assigned' : 'pending';

// Crear pedido
$stmt = $pdo->prepare('
    INSERT INTO orders
        (customer_name, customer_phone, customer_address, customer_lat, customer_lng,
         branch_id, driver_id, status, payment_method, subtotal, total,
         cash_received, change_amount, notes)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
');
$stmt->execute([
    $b['customer_name'],
    $b['customer_phone'],
    $b['customer_address'],
    $b['lat']   ?? null,
    $b['lng']   ?? null,
    $b['branch_id'],
    $driverId,
    $status,
    $b['payment_method'],
    $subtotal,
    $total,
    $cashReceived,
    $change,
    $b['notes'] ?? null,
]);
$orderId = $pdo->lastInsertId();

// Insertar items
foreach ($b['items'] as $it) {
    $pdo->prepare('
        INSERT INTO order_items (order_id, menu_item_id, name, quantity, unit_price, subtotal)
        VALUES (?,?,?,?,?,?)
    ')->execute([
        $orderId,
        $it['menu_item_id'] ?? null,
        $it['name'],
        (int)$it['qty'],
        (float)$it['unit_price'],
        (float)$it['unit_price'] * (int)$it['qty'],
    ]);
}

// Marcar repartidor como ocupado
if ($driverId) {
    $pdo->prepare("UPDATE drivers SET status='busy' WHERE id=?")->execute([$driverId]);
}

echo json_encode([
    'ok'             => true,
    'order_id'       => (int)$orderId,
    'status'         => $status,
    'driver_assigned'=> $driverId !== null,
    'total'          => $total,
    'change'         => $change,
]);
