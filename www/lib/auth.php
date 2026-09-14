<?php
/**
 * 登录认证：密码校验、失败锁定。
 */
declare(strict_types=1);
if (!defined('VMPANEL')) { http_response_code(403); exit('Forbidden'); }

function lock_status(): array {
    $f = $_SESSION['login_fail'] ?? [];
    $count = (int)($f['count'] ?? 0);
    $until = (int)($f['until'] ?? 0);
    $cfg = $GLOBALS['CFG']['app'];
    if ($count >= $cfg['max_failures'] && $until > time()) {
        return ['locked' => true, 'remain' => $until - time()];
    }
    if ($until && $until <= time()) {
        unset($_SESSION['login_fail']);
    }
    return ['locked' => false, 'remain' => 0];
}

/**
 * @return array{ok:bool,error?:string}
 */
function attempt_login(string $user, string $pass): array {
    $cfg = $GLOBALS['CFG']['app'];
    $lock = lock_status();
    if ($lock['locked']) {
        return ['ok' => false, 'error' => '失败次数过多，请 ' . $lock['remain'] . ' 秒后再试'];
    }

    $okUser = hash_equals($cfg['username'], $user);
    $hash = $cfg['password_hash'];
    $okPass = $hash !== '' && strpos($hash, '$2y$') === 0 && password_verify($pass, $hash);

    if ($okUser && $okPass) {
        unset($_SESSION['login_fail']);
        session_regenerate_id(true);
        $_SESSION['auth'] = true;
        $_SESSION['user'] = $user;
        $_SESSION['login_at'] = time();
        $_SESSION['last_active'] = time();
        csrf_token();
        audit_log('auth', 'login', '用户登录成功');
        return ['ok' => true];
    }

    // 失败计数
    $f = $_SESSION['login_fail'] ?? ['count' => 0];
    $f['count'] = (int)$f['count'] + 1;
    if ($f['count'] >= $cfg['max_failures']) {
        $f['until'] = time() + (int)$cfg['lock_seconds'];
    }
    $_SESSION['login_fail'] = $f;
    audit_log('auth', 'login_fail', '登录失败（用户名：' . $user . '，第 ' . $f['count'] . ' 次）');
    return ['ok' => false, 'error' => '用户名或密码错误'];
}

function logout(): void {
    if (logged_in()) audit_log('auth', 'logout', '用户退出登录');
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
