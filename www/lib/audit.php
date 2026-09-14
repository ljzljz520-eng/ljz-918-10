<?php
/**
 * 操作记录（审计日志）：JSON Lines 追加写入 var/audit/audit.log
 */
declare(strict_types=1);
if (!defined('VMPANEL')) { http_response_code(403); exit('Forbidden'); }

function audit_log(string $module, string $action, string $detail = '', bool $ok = true): void {
    $entry = [
        'ts'      => date('c'),
        'time'    => date('Y-m-d H:i:s'),
        'user'    => $_SESSION['user'] ?? '-',
        'ip'      => $_SERVER['REMOTE_ADDR'] ?? '-',
        'module'  => $module,
        'action'  => $action,
        'detail'  => $detail,
        'result'  => $ok ? 'success' : 'fail',
    ];
    $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $path = $GLOBALS['CFG']['paths']['audit'];
    @file_put_contents($path, $line . "\n", FILE_APPEND | LOCK_EX);
}

/**
 * 读取最近 N 条（倒序返回）
 */
function audit_read(int $limit = 200): array {
    $path = $GLOBALS['CFG']['paths']['audit'];
    if (!is_file($path)) return [];

    // 内存友好：读最后约 256KB 再解析
    $fp = @fopen($path, 'rb');
    if (!$fp) return [];
    $size = (int)filesize($path);
    $chunk = 262144;
    if ($size > $chunk) { fseek($fp, -$chunk, SEEK_END); }
    else { fseek($fp, 0); }
    $data = (string)stream_get_contents($fp);
    fclose($fp);

    $lines = preg_split('/\r?\n/', trim($data)) ?: [];
    // 跳过可能被截断的第一行
    if ($size > $chunk && !str_starts_with($lines[0] ?? '', '{')) array_shift($lines);

    $rows = [];
    foreach (array_reverse($lines) as $ln) {
        if ($ln === '') continue;
        $j = json_decode($ln, true);
        if (is_array($j)) $rows[] = $j;
        if (count($rows) >= $limit) break;
    }
    return $rows;
}
