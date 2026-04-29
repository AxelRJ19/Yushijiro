<?php
require __DIR__ . '/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$items = $pdo->query('SELECT * FROM menu_items WHERE available=1 ORDER BY category, sort_order')->fetchAll();

$grouped = [];
foreach ($items as $item) {
    $grouped[$item['category']][] = $item;
}

echo json_encode(['ok' => true, 'menu' => $grouped]);
