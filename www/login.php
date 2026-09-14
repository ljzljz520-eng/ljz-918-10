<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/audit.php';

if (logged_in()) { header('Location: index.php'); exit; }

$error = '';
if (is_post()) {
    csrf_check();
    $u = trim((string)($_POST['username'] ?? ''));
    $p = (string)($_POST['password'] ?? '');
    $r = attempt_login($u, $p);
    if ($r['ok']) {
        header('Location: index.php');
        exit;
    }
    $error = $r['error'];
}
$lock = lock_status();
?><!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>登录 · <?= e($GLOBALS['CFG']['app']['name']) ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-body">
<form class="login-card" method="post" autocomplete="off">
  <div class="login-logo">▣</div>
  <h1>售货机本地守护面板</h1>
  <p class="login-sub">VM LOCAL DAEMON CONSOLE · 机台 <?= e($GLOBALS['CFG']['app']['machine']) ?></p>

  <?php if (isset($_GET['expired'])): ?><div class="alert alert-error">登录已超时，请重新登录</div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <?php if ($lock['locked']): ?>
    <div class="alert alert-error">账户已锁定，<span id="lock-remain"><?= (int)$lock['remain'] ?></span> 秒后重试</div>
  <?php endif; ?>

  <?= csrf_field() ?>
  <label class="field">
    <span>用户名</span>
    <input type="text" name="username" required autofocus maxlength="32"
           <?= $lock['locked'] ? 'disabled' : '' ?>>
  </label>
  <label class="field">
    <span>密码</span>
    <input type="password" name="password" required maxlength="64"
           <?= $lock['locked'] ? 'disabled' : '' ?>>
  </label>
  <button class="btn-primary" type="submit" <?= $lock['locked'] ? 'disabled' : '' ?>>登 录</button>
  <p class="login-hint">默认账号 admin / admin123，首次部署请立即修改密码</p>
</form>
<?php if ($lock['locked']): ?>
<script>
(function(){var s=<?= (int)$lock['remain'] ?>,el=document.getElementById('lock-remain');
var t=setInterval(function(){s--;if(s<=0){clearInterval(t);location.reload();}el.textContent=s;},1000);})();
</script>
<?php endif; ?>
</body>
</html>
