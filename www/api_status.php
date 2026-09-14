<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/services.php';
require_login();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$rows = all_status();
$now = time();
echo json_encode([
    'ok'       => true,
    'server'   => date('Y-m-d H:i:s'),
    'ts'       => $now,
    'running'  => count(array_filter($rows, fn($r) => $r['state'] === 'running')),
    'total'    => count($rows),
    'services' => $rows,
], JSON_UNESCAPED_UNICODE);
