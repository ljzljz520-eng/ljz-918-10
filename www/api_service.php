<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/services.php';
require_once __DIR__ . '/lib/audit.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

if (!is_post()) { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'仅支持 POST']); exit; }
csrf_check();

$action = (string)($_POST['action'] ?? '');
$svc    = trim((string)($_POST['service'] ?? ''));
$validActions = ['start', 'stop', 'restart'];

if (!in_array($action, $validActions, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '非法操作']); exit;
}

// 空 service => 全部服务（批量）
$targets = [];
if ($svc === '') {
    $targets = service_whitelist();
} elseif (valid_service($svc)) {
    $targets = [$svc];
} else {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '未知服务']); exit;
}

$results = [];
$allOk = true;
foreach ($targets as $id) {
    $r = vmctl($action, $id);
    $meta = service_meta($id);
    audit_log($id, $action,
        $action . ' ' . $meta['name'] . '：' . str_replace("\n", '; ', $r['out']),
        $r['ok']);
    $results[] = ['id' => $id, 'ok' => $r['ok'], 'message' => $r['out']];
    if (!$r['ok']) $allOk = false;
}

// 操作后回读一次最新状态
$status = all_status();
echo json_encode([
    'ok'      => $allOk,
    'results' => $results,
    'status'  => $status,
], JSON_UNESCAPED_UNICODE);
