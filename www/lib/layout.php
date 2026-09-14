<?php
/**
 * 页面骨架：顶部导航 + 统一 HTML 结构
 */
declare(strict_types=1);
if (!defined('VMPANEL')) { http_response_code(403); exit('Forbidden'); }

function page_header(string $title, string $active = ''): void {
    $cfg = $GLOBALS['CFG'];
    $nav = [
        'index.php'     => '服务总览',
        'logs.php'      => '日志查看',
        'selfcheck.php' => '目录自检',
        'history.php'   => '操作记录',
    ];
    ?><!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · <?= e($cfg['app']['name']) ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
  <div class="brand">
    <span class="logo">▣</span>
    <div>
      <strong><?= e($cfg['app']['name']) ?></strong>
      <small>本地守护面板 · 机台 <?= e($cfg['app']['machine']) ?></small>
    </div>
  </div>
  <nav class="nav">
    <?php foreach ($nav as $href => $label): ?>
      <a href="<?= e($href) ?>" class="<?= $active === $href ? 'active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
    <a href="tools_change_password.php" class="<?= $active === 'tools_change_password.php' ? 'active' : '' ?>">修改密码</a>
    <form method="post" action="logout.php" class="logout-form" data-confirm="确定退出登录？">
      <?= csrf_field() ?>
      <button type="submit" class="btn-ghost">退出</button>
    </form>
  </nav>
</header>
<main class="container">
<?php
}

function page_footer(): void {
    $refresh = (int)$GLOBALS['CFG']['refresh_ms'];
    ?>
</main>
<footer class="footer">
  <span>本机维护用途 · 所有操作均记录在案</span>
  <span id="clock"></span>
</footer>
<script>window.VM_REFRESH_MS = <?= $refresh ?>;</script>
<script src="assets/app.js"></script>
</body>
</html>
<?php
}

function flash(): void {
    if (!empty($_SESSION['flash'])):
        $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
<div class="alert alert-<?= $f['type'] === 'error' ? 'error' : 'ok' ?>"><?= e($f['msg']) ?></div>
    <?php endif;
}
function set_flash(string $msg, string $type = 'ok'): void {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}
