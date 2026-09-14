<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/services.php';
require_login();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$svc = (string)($_GET['svc'] ?? '');
$lines = (int)($_GET['lines'] ?? $GLOBALS['CFG']['log_lines']);
if ($lines < 1) $lines = 100;
if ($lines > 2000) $lines = 2000;

if (!valid_service($svc)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '未知服务']); exit;
}

$r = vmctl('logs', $svc, ['lines' => $lines]);
echo json_encode([
    'ok'   => true,
    'svc'  => $svc,
    'file' => service_meta($svc)['log'],
    'logs' => $r['out'],
    'server' => date('Y-m-d H:i:s'),
], JSON_UNESCAPED_UNICODE);
