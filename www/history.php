<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/audit.php';
require_once __DIR__ . '/lib/layout.php';
require_login();

$limit = (int)($_GET['limit'] ?? 200);
$limit = in_array($limit, [50, 100, 200, 500], true) ? $limit : 200;
$filterModule = trim((string)($_GET['module'] ?? ''));
$filterResult = trim((string)($_GET['result'] ?? ''));

$rows = audit_read($limit * 3); // 粗过滤后再截断
if ($filterModule !== '') $rows = array_filter($rows, fn($r) => ($r['module'] ?? '') === $filterModule);
if ($filterResult !== '') $rows = array_filter($rows, fn($r) => ($r['result'] ?? '') === $filterResult);
$rows = array_slice(array_values($rows), 0, $limit);

$modules = ['pay-listener','stock-reporter','temp-collector','ad-player','auth','selfcheck'];
page_header('操作记录', 'history.php');
?>
<div class="page-head">
  <div><h2>操作记录</h2>
    <p class="muted">登录、服务启停与自检等全部敏感操作留痕（var/audit/audit.log）</p></div>
  <form method="get" class="log-bar">
    <select name="module">
      <option value="">全部模块</option>
      <?php foreach ($modules as $m): ?>
        <option value="<?= e($m) ?>" <?= $m === $filterModule ? 'selected' : '' ?>><?= e($m) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="result">
      <option value="">全部结果</option>
      <option value="success" <?= $filterResult==='success'?'selected':'' ?>>成功</option>
      <option value="fail" <?= $filterResult==='fail'?'selected':'' ?>>失败</option>
    </select>
    <select name="limit">
      <?php foreach ([50, 100, 200, 500] as $n): ?>
        <option value="<?= $n ?>" <?= $n === $limit ? 'selected' : '' ?>>最近 <?= $n ?> 条</option>
      <?php endforeach; ?>
    </select>
    <button class="btn" type="submit">筛选</button>
  </form>
</div>

<table class="table">
  <thead><tr>
    <th>时间</th><th>用户</th><th>来源 IP</th><th>模块</th><th>动作</th><th>详情</th><th>结果</th>
  </tr></thead>
  <tbody>
  <?php if (!$rows): ?>
    <tr><td colspan="7" class="muted center">暂无操作记录</td></tr>
  <?php endif; ?>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td class="mono nowrap"><?= e($r['time'] ?? '') ?></td>
      <td><?= e($r['user'] ?? '-') ?></td>
      <td class="mono"><?= e($r['ip'] ?? '-') ?></td>
      <td><span class="chip mono"><?= e($r['module'] ?? '-') ?></span></td>
      <td class="mono"><?= e($r['action'] ?? '-') ?></td>
      <td class="detail-cell"><?= e($r['detail'] ?? '') ?></td>
      <td><?= ($r['result'] ?? '') === 'success'
            ? '<span class="badge badge-running">成功</span>'
            : '<span class="badge badge-stopped">失败</span>' ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php page_footer(); ?>
