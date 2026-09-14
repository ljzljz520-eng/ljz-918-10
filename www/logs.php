<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/services.php';
require_once __DIR__ . '/lib/layout.php';
require_login();

$services = $GLOBALS['CFG']['services'];
$sel = (string)($_GET['svc'] ?? array_key_first($services));
if (!valid_service($sel)) $sel = array_key_first($services);
$lines = (int)($GLOBALS['CFG']['log_lines']);

// 首屏服务端直接取日志，避免空闪
$first = vmctl('logs', $sel, ['lines' => $lines]);
$logText = $first['out'];

page_header('日志查看', 'logs.php');
?>
<div class="page-head">
  <div><h2>服务日志</h2>
    <p class="muted">通过受控通道读取 var/logs，最多显示 2000 行</p></div>
  <form class="log-bar" method="get" id="log-form">
    <select name="svc" id="log-svc">
      <?php foreach ($services as $id => $m): ?>
        <option value="<?= e($id) ?>" <?= $id === $sel ? 'selected' : '' ?>><?= e($m['name']) ?>（<?= e($id) ?>）</option>
      <?php endforeach; ?>
    </select>
    <label class="inline"><input type="checkbox" id="log-follow"> 自动跟随</label>
    <button type="button" class="btn" id="log-refresh">刷新</button>
  </form>
</div>

<div class="log-toolbar">
  <span class="chip mono" id="log-file"><?= e($services[$sel]['log']) ?></span>
  <label class="inline">行数
    <select id="log-lines">
      <?php foreach ([100, 200, 500, 1000, 2000] as $n): ?>
        <option value="<?= $n ?>" <?= $n === $lines ? 'selected' : '' ?>><?= $n ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <button type="button" class="btn-ghost" id="log-download">下载日志</button>
</div>
<pre class="log-viewer" id="log-viewer"><?= e($logText) ?></pre>
<?php page_footer(); ?>
