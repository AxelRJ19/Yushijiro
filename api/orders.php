<?php
require __DIR__ . '/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$branchId = $_GET['branch_id'] ?? null;
$status   = $_GET['status']    ?? null;

$sql    = 'SELECT o.*, b.name AS branch_name, d.name AS driver_name
           FROM orders o
           LEFT JOIN branches b ON o.branch_id = b.id
           LEFT JOIN drivers  d ON o.driver_id  = d.id
           WHERE 1=1';
$params = [];

if ($branchId) { $sql .= ' AND o.branch_id=?'; $params[] = $branchId; }
if ($status)   { $sql .= ' AND o.status=?';    $params[] = $status; }

$sql .= ' ORDER BY o.created_at DESC LIMIT 200';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$list = $stmt->fetchAll();

foreach ($list as &$order) {
    $s = $pdo->prepare('SELECT * FROM order_items WHERE order_id=?');
    $s->execute([$order['id']]);
    $order['items'] = $s->fetchAll();
}

echo json_encode(['ok' => true, 'orders' => $list]);
