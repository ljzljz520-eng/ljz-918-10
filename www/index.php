<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/services.php';
require_once __DIR__ . '/lib/layout.php';
require_login();

$rows = all_status();
$running = count(array_filter($rows, fn($r) => $r['state'] === 'running'));
page_header('服务总览', 'index.php');
flash();
?>
<div class="page-head">
  <div>
    <h2>服务总览</h2>
    <p class="muted">守护进程状态每 <strong><?= ((int)$GLOBALS['CFG']['refresh_ms'])/1000 ?></strong> 秒自动刷新</p>
  </div>
  <div class="summary">
    <span class="chip chip-live"><i class="dot dot-live" id="live-dot"></i>实时</span>
    <span class="chip"><span id="sum-running"><?= $running ?></span>/<?= count($rows) ?> 运行中</span>
  </div>
</div>

<section class="grid" id="service-grid">
  <?php foreach ($rows as $r): ?>
  <article class="svc-card state-<?= e($r['state']) ?>" data-svc="<?= e($r['id']) ?>">
    <header class="svc-head">
      <div>
        <h3><?= e($r['name']) ?>
          <button type="button" class="mini-log" title="查看日志"
                  data-href="logs.php?svc=<?= e($r['id']) ?>">日志</button>
        </h3>
        <p class="muted"><?= e($r['desc']) ?></p>
      </div>
      <span class="badge badge-<?= e($r['state']) ?> state-text">
        <i class="dot dot-<?= e($r['state']) ?>"></i><span class="state-label">
        <?= $r['state'] === 'running' ? '运行中' : '已停止' ?></span>
      </span>
    </header>
    <dl class="svc-meta">
      <div><dt>服务标识</dt><dd class="mono"><?= e($r['id']) ?></dd></div>
      <div><dt>进程 PID</dt><dd class="mono svc-pid"><?= $r['pid'] ?? '-' ?></dd></div>
      <div><dt>运行时长</dt><dd class="svc-uptime"><?= e(human_uptime((int)$r['uptime'])) ?></dd></div>
    </dl>
    <footer class="svc-actions">
      <?= csrf_field() ?>
      <button class="btn btn-start"  data-act="start"  <?= $r['state']==='running'?'disabled':'' ?>>启动</button>
      <button class="btn btn-stop"   data-act="stop"    <?= $r['state']!=='running'?'disabled':'' ?>>停止</button>
      <button class="btn btn-restart" data-act="restart">重启</button>
    </footer>
  </article>
  <?php endforeach; ?>
</section>

<div class="batch-bar">
  <span class="muted">批量操作：</span>
  <?= csrf_field() ?>
  <button class="btn btn-start" data-batch="start">全部启动</button>
  <button class="btn btn-restart" data-batch="restart">全部重启</button>
  <button class="btn btn-stop" data-batch="stop">全部停止</button>
</div>
<?php page_footer(); ?>
