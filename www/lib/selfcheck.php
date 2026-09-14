<?php
/**
 * 目录自检：检查关键目录/文件/权限/运行时环境，
 * 并尝试自动修复可修复项（创建缺失目录、写权限修正由部署用户负责）。
 */
declare(strict_types=1);
if (!defined('VMPANEL')) { http_response_code(403); exit('Forbidden'); }

function selfcheck_run(bool $fix = false): array {
    $cfg = $GLOBALS['CFG'];
    $root = $cfg['paths']['root'];
    $checks = [];

    $dir = function (string $label, string $path, int $mask) use (&$checks, $fix) {
        $item = ['label' => $label, 'path' => $path, 'ok' => true,
                 'detail' => '', 'auto_fixed' => false];
        if (!file_exists($path)) {
            if ($fix && @mkdir($path, $mask, true)) {
                $item['detail'] = '目录缺失，已自动创建';
                $item['auto_fixed'] = true;
            } else {
                $item['ok'] = false;
                $item['detail'] = '目录缺失';
            }
        } elseif (!is_dir($path)) {
            $item['ok'] = false; $item['detail'] = '存在同名文件，应为目录';
        } elseif (!is_readable($path)) {
            $item['ok'] = false; $item['detail'] = '目录不可读';
        } elseif (!is_writable($path)) {
            $item['ok'] = false; $item['detail'] = '目录不可写';
        } else {
            $item['detail'] = '正常';
        }
        $checks[] = $item;
    };

    $file = function (string $label, string $path) use (&$checks) {
        $item = ['label' => $label, 'path' => $path, 'ok' => true,
                 'detail' => '', 'auto_fixed' => false];
        if (!file_exists($path)) { $item['ok'] = false; $item['detail'] = '文件缺失'; }
        elseif (!is_readable($path)) { $item['ok'] = false; $item['detail'] = '文件不可读'; }
        else {
            $mode = substr(sprintf('%04o', fileperms($path)), -4);
            $item['detail'] = '正常（权限 ' . $mode . '）';
        }
        $checks[] = $item;
    };

    // 关键目录
    $dir('根目录', $root, 0750);
    $dir('WWW 页面目录', $cfg['paths']['www'], 0750);
    $dir('运行时 PID 目录', $cfg['paths']['run'], 0750);
    $dir('服务日志目录', $cfg['paths']['logs'], 0750);
    $dir('操作记录目录', dirname($cfg['paths']['audit']), 0750);

    // 关键文件
    $file('配置文件', $root . '/config/config.php');
    $file('服务控制脚本 vmctl', $cfg['paths']['bin']);

    // 运行时写入探针
    $probe = $cfg['paths']['run'] . '/.write_probe_' . bin2hex(random_bytes(4));
    if (@file_put_contents($probe, 'ok') !== false) {
        $checks[] = ['label' => '运行时写入探针', 'path' => $cfg['paths']['run'],
                     'ok' => true, 'detail' => '可写', 'auto_fixed' => false];
        @unlink($probe);
    } else {
        $checks[] = ['label' => '运行时写入探针', 'path' => $cfg['paths']['run'],
                     'ok' => false, 'detail' => '不可写', 'auto_fixed' => false];
    }

    // vmctl 可执行
    $isExe = is_file($cfg['paths']['bin']) && is_executable($cfg['paths']['bin']);
    $checks[] = ['label' => 'vmctl 可执行位', 'path' => $cfg['paths']['bin'],
                 'ok' => $isExe,
                 'detail' => $isExe ? '可执行' : '不可执行，请执行 chmod +x bin/vmctl',
                 'auto_fixed' => false];

    // PHP 函数依赖（shell_exec 需要未被禁用）
    $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    $hasShell = function_exists('shell_exec') && !in_array('shell_exec', $disabled, true);
    $checks[] = ['label' => 'shell_exec 可用', 'path' => 'PHP',
                 'ok' => $hasShell,
                 'detail' => $hasShell ? '可用' : '被禁用，服务控制将不可用',
                 'auto_fixed' => false];

    // bash
    $bashOk = is_file('/bin/bash') || is_file('/usr/bin/bash');
    $checks[] = ['label' => 'Bash 解释器', 'path' => '/bin/bash',
                 'ok' => $bashOk, 'detail' => $bashOk ? '存在' : '缺失',
                 'auto_fixed' => false];

    // 四个服务脚本
    foreach ($cfg['services'] as $id => $meta) {
        $p = $cfg['paths']['services'] . '/' . $meta['script'];
        $file($meta['name'] . ' 服务脚本', $p);
    }

    $pass = 0; $fail = 0;
    foreach ($checks as $c) { $c['ok'] ? $pass++ : $fail++; }
    return ['checks' => $checks, 'pass' => $pass, 'fail' => $fail,
            'ok' => $fail === 0, 'ran_at' => date('Y-m-d H:i:s')];
}
