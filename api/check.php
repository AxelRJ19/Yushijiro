<?php
session_name('YUSHIJIRO_REP');
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store');

if (empty($_SESSION['rep'])) {
    http_response_code(401);
    exit(json_encode(['authenticated' => false]));
}

exit(json_encode(['authenticated' => true, 'user' => $_SESSION['rep']]));
