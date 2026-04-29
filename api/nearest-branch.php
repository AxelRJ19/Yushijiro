<?php
require __DIR__ . '/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$lat = (float)($_GET['lat'] ?? 0);
$lng = (float)($_GET['lng'] ?? 0);

$branches = $pdo->query('SELECT id, name, lat, lng FROM branches WHERE active=1')->fetchAll();

// Always return all branches for the manual selector
if (!$lat && !$lng) {
    exit(json_encode(['ok' => true, 'branch' => null, 'all_branches' => $branches]));
}

$nearest = null;
$minDist = PHP_INT_MAX;

foreach ($branches as $b) {
    $d = sqrt(pow($b['lat'] - $lat, 2) + pow($b['lng'] - $lng, 2));
    if ($d < $minDist) {
        $minDist = $d;
        $nearest = $b;
    }
}

$nearest['distance_km'] = round($minDist * 111, 1);
echo json_encode(['ok' => true, 'branch' => $nearest, 'all_branches' => $branches]);
