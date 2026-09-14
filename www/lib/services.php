<?php
/**
 * 服务控制：所有对后台服务/日志的访问都经由 bin/vmctl 白名单脚本，
 * PHP 层不拼接任何 shell 参数之外的命令。
 */
declare(strict_types=1);
if (!defined('VMPANEL')) { http_response_code(403); exit('Forbidden'); }

function service_whitelist(): array { return array_keys($GLOBALS['CFG']['services']); }

function valid_service(string $id): bool { return isset($GLOBALS['CFG']['services'][$id]); }
function service_meta(string $id): array { return $GLOBALS['CFG']['services'][$id] ?? []; }

/**
 * 调用 vmctl。$action、$svc 均为固定白名单字符串，以 -- 参数传递。
 * @return array{ok:bool,code:int,out:string}
 */
function vmctl(string $action, ?string $svc = null, array $extra = []): array {
    $allowedActions = ['start', 'stop', 'restart', 'status', 'logs'];
    if (!in_array($action, $allowedActions, true)) {
        return ['ok' => false, 'code' => -1, 'out' => '非法动作'];
    }
    if ($svc !== null && !valid_service($svc)) {
        return ['ok' => false, 'code' => -1, 'out' => '未知服务'];
    }

    $cmd = escapeshellarg($GLOBALS['CFG']['paths']['bin']) . ' ' . escapeshellarg($action);
    if ($svc !== null) $cmd .= ' ' . escapeshellarg($svc);
    foreach ($extra as $k => $v) {
        // extra 仅允许整数类选项（如 --lines 200）
        if (!preg_match('/^[a-z][a-z0-9_]*$/', (string)$k)) continue;
        if (!preg_match('/^-?\d+$/', (string)$v)) continue;
        $cmd .= ' --' . $k . ' ' . escapeshellarg((string)$v);
    }

    $out = @shell_exec($cmd . ' 2>&1');
    // vmctl 输出最后一行为 RESULT=<code>
    $code = 1;
    if ($out !== null && preg_match('/\n?RESULT=(-?\d+)\s*$/', $out, $m)) {
        $code = (int)$m[1];
        $out = preg_replace('/\n?RESULT=-?\d+\s*$/', '', $out);
    }
    return ['ok' => $code === 0, 'code' => $code, 'out' => trim((string)$out)];
}

/**
 * 全量状态（供仪表盘与状态接口）
 */
function all_status(): array {
    $r = vmctl('status');
    $rows = [];
    foreach (explode("\n", $r['out']) as $line) {
        $parts = str_getcsv($line, ',', '"', '\\');
        if (count($parts) >= 4) {
            [$id, $state, $pid, $uptime] = $parts;
            if (valid_service($id)) {
                $meta = service_meta($id);
                $rows[$id] = [
                    'id'      => $id,
                    'name'    => $meta['name'],
                    'desc'    => $meta['desc'],
                    'state'   => $state === 'running' ? 'running' : 'stopped',
                    'pid'     => (int)$pid ?: null,
                    'uptime'  => (int)$uptime,
                ];
            }
        }
    }
    // 保证白名单内服务即使无输出也出现
    foreach (service_whitelist() as $id) {
        if (!isset($rows[$id])) {
            $meta = service_meta($id);
            $rows[$id] = ['id' => $id, 'name' => $meta['name'], 'desc' => $meta['desc'],
                          'state' => 'stopped', 'pid' => null, 'uptime' => 0];
        }
    }
    return array_values($rows);
}

function human_uptime(int $sec): string {
    if ($sec <= 0) return '-';
    if ($sec < 60) return $sec . ' 秒';
    if ($sec < 3600) return intdiv($sec, 60) . ' 分 ' . ($sec % 60) . ' 秒';
    if ($sec < 86400) return intdiv($sec, 3600) . ' 时 ' . intdiv($sec % 3600, 60) . ' 分';
    return intdiv($sec, 86400) . ' 天 ' . intdiv($sec % 86400, 3600) . ' 时';
}
