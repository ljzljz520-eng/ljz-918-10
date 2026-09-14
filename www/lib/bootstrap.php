<?php
/**
 * 引导文件：加载配置、启动安全会话、定义通用工具。
 * 所有页面入口第一行 require __DIR__ . '/lib/bootstrap.php';
 */
declare(strict_types=1);

define('VMPANEL', true);

// 禁止直接 HTTP 访问本文件
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_name('VMPANEL_SID');
    session_start();
}

$GLOBALS['CFG'] = require dirname(__DIR__, 2) . '/config/config.php';
$CFG = $GLOBALS['CFG'];

date_default_timezone_set('Asia/Shanghai');

/* ---------- 输出转义 ---------- */
function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* ---------- CSRF ---------- */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}
function csrf_check(): void {
    $token = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        if (is_ajax()) { header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => '会话已过期，请刷新页面重试']); exit; }
        exit('CSRF 校验失败，请返回刷新页面。');
    }
}

/* ---------- 请求类型 ---------- */
function is_ajax(): bool {
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
}
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }

/* ---------- 登录态 ---------- */
function logged_in(): bool { return !empty($_SESSION['auth']); }
function require_login(): void {
    if (!logged_in()) {
        if (is_ajax()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => '未登录或会话过期', 'relogin' => true]);
            exit;
        }
        header('Location: login.php');
        exit;
    }
    // 空闲超时
    $lifetime = (int)$GLOBALS['CFG']['app']['session_lifetime'];
    $last = $_SESSION['last_active'] ?? 0;
    if ($last && time() - $last > $lifetime) {
        session_destroy();
        if (is_ajax()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => '登录已超时', 'relogin' => true]); exit;
        }
        header('Location: login.php?expired=1'); exit;
    }
    $_SESSION['last_active'] = time();
}
