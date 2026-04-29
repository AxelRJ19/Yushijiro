<?php
require __DIR__ . '/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$branchId = $_GET['branch_id'] ?? null;

$sql    = 'SELECT id, name, phone, status, branch_id FROM drivers WHERE 1=1';
$params = [];

if ($branchId) { $sql .= ' AND branch_id=?'; $params[] = $branchId; }

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

echo json_encode(['ok' => true, 'drivers' => $stmt->fetchAll()]);
