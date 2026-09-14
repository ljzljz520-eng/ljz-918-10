<?php
/**
 * 修改面板密码：直接改写 config/config.php 中的 password_hash。
 * 仅本机已登录管理员可用。
 */
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/audit.php';
require_once __DIR__ . '/lib/layout.php';
require_login();

$cfgFile = dirname(__DIR__) . '/config/config.php';
$error = ''; $ok = '';

if (is_post()) {
    csrf_check();
    $old = (string)($_POST['old_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $new2 = (string)($_POST['new_password2'] ?? '');

    if (!password_verify($old, $GLOBALS['CFG']['app']['password_hash'])) {
        $error = '当前密码不正确';
    } elseif (strlen($new) < 8) {
        $error = '新密码长度至少 8 位';
    } elseif ($new !== $new2) {
        $error = '两次输入的新密码不一致';
    } elseif (!is_writable($cfgFile)) {
        $error = '配置文件不可写：' . $cfgFile;
    } else {
        $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 10]);
        $src = (string)file_get_contents($cfgFile);
        $pattern = "/('password_hash'\\s*=>\\s*)'[^']*'/";
        // 用回调避免新哈希中的 $2y$ 被 preg_replace 当成反向引用
        $out = preg_replace_callback($pattern, fn($m) => $m[1] . "'" . $hash . "'", $src, 1, $cnt);
        if ($cnt === 1 && file_put_contents($cfgFile, $out, LOCK_EX) !== false) {
            $ok = '密码已更新，请牢记新密码。';
            audit_log('auth', 'password_change', '管理员修改登录密码');
        } else {
            $error = '写入配置失败，请检查文件权限';
            audit_log('auth', 'password_change', '密码修改失败：写入配置失败', false);
        }
    }
    if ($error) audit_log('auth', 'password_change', '密码修改失败：' . $error, false);
}

page_header('修改密码', '');
?>
<div class="page-head"><div><h2>修改登录密码</h2>
  <p class="muted">更新 config/config.php 中存储的 bcrypt 密码哈希</p></div></div>

<?php if ($ok): ?><div class="alert alert-ok"><?= e($ok) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<form method="post" class="form-card" style="max-width:460px">
  <?= csrf_field() ?>
  <label class="field"><span>当前密码</span>
    <input type="password" name="old_password" required autocomplete="current-password"></label>
  <label class="field"><span>新密码（至少 8 位）</span>
    <input type="password" name="new_password" required minlength="8" autocomplete="new-password"></label>
  <label class="field"><span>确认新密码</span>
    <input type="password" name="new_password2" required minlength="8" autocomplete="new-password"></label>
  <button class="btn-primary" type="submit">保存新密码</button>
</form>
<?php page_footer(); ?>
