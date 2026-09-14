<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/selfcheck.php';
require_once __DIR__ . '/lib/audit.php';
require_once __DIR__ . '/lib/layout.php';
require_login();

$fixed = false;
if (is_post()) {
    csrf_check();
    $doFix = ($_POST['fix'] ?? '') === '1';
    $res = selfcheck_run($doFix);
    audit_log('selfcheck', 'run',
        '目录自检执行（自动修复：' . ($doFix ? '是' : '否') . '），通过 ' . $res['pass'] . '，失败 ' . $res['fail'],
        $res['ok']);
    set_flash('自检完成：通过 ' . $res['pass'] . ' 项，失败 ' . $res['fail'] . ' 项',
              $res['ok'] ? 'ok' : 'error');
    header('Location: selfcheck.php');
    exit;
}

$res = selfcheck_run(false);
page_header('目录自检', 'selfcheck.php');
flash();
?>
<div class="page-head">
  <div><h2>目录自检</h2>
    <p class="muted">检查运行所需的目录、文件、权限与 PHP 环境；缺失的运行时目录可自动修复</p></div>
  <form method="post" class="log-bar" onsubmit="return confirm('确定执行自检？')">
    <?= csrf_field() ?>
    <button name="fix" value="0" class="btn">重新自检</button>
    <button name="fix" value="1" class="btn btn-start">自检并自动修复</button>
  </form>
</div>

<div class="check-summary">
  <span class="chip chip-ok">通过 <strong><?= (int)$res['pass'] ?></strong></span>
  <span class="chip chip-<?= $res['fail'] ? 'err' : 'ok' ?>">异常 <strong><?= (int)$res['fail'] ?></strong></span>
  <span class="chip <?= $res['ok'] ? 'chip-ok' : 'chip-err' ?>">
    总体：<strong><?= $res['ok'] ? '正常' : '存在异常' ?></strong></span>
  <span class="muted">检查时间 <?= e($res['ran_at']) ?></span>
</div>

<table class="table">
  <thead><tr><th style="width:46px">#</th><th>检查项</th><th>路径 / 对象</th><th>状态</th><th>说明</th></tr></thead>
  <tbody>
  <?php foreach ($res['checks'] as $i => $c): ?>
    <tr class="<?= $c['ok'] ? 'row-ok' : 'row-err' ?>">
      <td class="mono"><?= $i + 1 ?></td>
      <td><?= e($c['label']) ?></td>
      <td class="mono muted"><?= e($c['path']) ?></td>
      <td><?php if ($c['ok']): ?><span class="badge badge-running">通过</span>
          <?php else: ?><span class="badge badge-stopped">异常</span><?php endif; ?>
          <?php if ($c['auto_fixed']): ?><span class="tag tag-fix">已自动修复</span><?php endif; ?></td>
      <td><?= e($c['detail']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php page_footer(); ?>
