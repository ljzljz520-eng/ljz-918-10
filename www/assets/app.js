(function () {
  'use strict';
  var REFRESH = window.VM_REFRESH_MS || 5000;
  var statusTimer = null, logTimer = null;

  function csrf() {
    var f = document.querySelector('input[name="csrf"]');
    return f ? f.value : '';
  }
  function post(url, data) {
    var fd = new FormData();
    fd.append('csrf', csrf());
    Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
    return fetch(url, {method: 'POST', headers: {'X-Requested-With': 'XMLHttpRequest'},
      body: fd, credentials: 'same-origin'}).then(function (r) {
      return r.json().then(function (j) { return {status: r.status, body: j}; });
    });
  }
  function getJSON(url) {
    return fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}, credentials: 'same-origin'})
      .then(function (r) { return r.json().then(function (j) { return {status: r.status, body: j}; }); });
  }
  function relogin() {
    if (location.pathname.indexOf('login.php') === -1) location.href = 'login.php?expired=1';
  }

  /* ---------------- 仪表盘 ---------------- */
  var grid = document.getElementById('service-grid');

  function renderCards(services) {
    if (!grid) return;
    services.forEach(function (s) {
      var card = grid.querySelector('[data-svc="' + s.id + '"]');
      if (!card) return;
      card.classList.remove('state-running', 'state-stopped');
      card.classList.add('state-' + s.state);
      var badge = card.querySelector('.state-text');
      badge.classList.remove('badge-running', 'badge-stopped');
      badge.classList.add(s.state === 'running' ? 'badge-running' : 'badge-stopped');
      var dot = badge.querySelector('.dot');
      dot.classList.remove('dot-running', 'dot-stopped');
      dot.classList.add('dot-' + s.state);
      badge.querySelector('.state-label').textContent = s.state === 'running' ? '运行中' : '已停止';
      card.querySelector('.svc-pid').textContent = s.pid ? String(s.pid) : '-';
      card.querySelector('.svc-uptime').textContent = humanUptime(s.uptime);
      card.querySelector('.btn-start').disabled  = s.state === 'running';
      card.querySelector('.btn-stop').disabled   = s.state !== 'running';
    });
  }

  function humanUptime(sec) {
    sec = parseInt(sec, 10) || 0;
    if (sec <= 0) return '-';
    if (sec < 60) return sec + ' 秒';
    if (sec < 3600) return Math.floor(sec / 60) + ' 分 ' + (sec % 60) + ' 秒';
    if (sec < 86400) return Math.floor(sec / 3600) + ' 时 ' + Math.floor(sec % 3600 / 60) + ' 分';
    return Math.floor(sec / 86400) + ' 天 ' + Math.floor(sec % 86400 / 3600) + ' 时';
  }

  function refreshStatus() {
    getJSON('api_status.php').then(function (r) {
      if (r.status === 401) { relogin(); return; }
      if (r.body && r.body.ok) {
        renderCards(r.body.services);
        var sum = document.getElementById('sum-running');
        if (sum) sum.textContent = r.body.running;
      }
    }).catch(function () {});
  }

  if (grid) {
    statusTimer = setInterval(refreshStatus, REFRESH);
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) { clearInterval(statusTimer); }
      else { refreshStatus(); statusTimer = setInterval(refreshStatus, REFRESH); }
    });

    grid.addEventListener('click', function (ev) {
      var btn = ev.target.closest('button[data-act]');
      if (btn) return doAction(btn, btn.getAttribute('data-act'),
        btn.closest('.svc-card').getAttribute('data-svc'));
      var mini = ev.target.closest('.mini-log');
      if (mini) { location.href = mini.getAttribute('data-href'); }
    });

    document.querySelectorAll('[data-batch]').forEach(function (b) {
      b.addEventListener('click', function () {
        var act = b.getAttribute('data-batch');
        var label = {start: '全部启动', stop: '全部停止', restart: '全部重启'}[act];
        if (!confirm('确定执行「' + label + '」？')) return;
        setBusy(b, true);
        post('api_service.php', {action: act, service: ''}).then(function (r) {
          if (r.status === 401) return relogin();
          if (r.body && r.body.status) renderCards(r.body.status);
          if (r.body && r.body.results) {
            var fails = r.body.results.filter(function (x) { return !x.ok; });
            if (fails.length) alert('部分服务操作失败：\n' +
              fails.map(function (x) { return '· ' + x.id + '：' + x.message; }).join('\n'));
          }
          refreshStatus();
        }).catch(function () { alert('请求失败，请检查网络/服务'); })
         .finally(function () { setBusy(b, false); });
      });
    });
  }

  function doAction(btn, act, svc) {
    var label = {start: '启动', stop: '停止', restart: '重启'}[act];
    if (!confirm('确定' + label + '服务「' + svc + '」？')) return;
    var card = btn.closest('.svc-card');
    card.querySelectorAll('button[data-act]').forEach(function (x) { x.disabled = true; });
    post('api_service.php', {action: act, service: svc}).then(function (r) {
      if (r.status === 401) return relogin();
      if (r.body && r.body.status) renderCards(r.body.status);
      if (r.body && r.body.results && r.body.results.length) {
        var res = r.body.results[0];
        if (!res.ok) alert('操作失败：' + res.message);
      }
    }).catch(function () { alert('请求失败'); })
      .finally(function () { refreshStatus(); });
  }

  function setBusy(btn, busy) {
    if (!btn.dataset.txt) btn.dataset.txt = btn.textContent;
    btn.disabled = busy;
    btn.textContent = busy ? '执行中…' : btn.dataset.txt;
  }

  /* ---------------- 日志页 ---------------- */
  var viewer = document.getElementById('log-viewer');
  if (viewer) {
    var svcSel = document.getElementById('log-svc');
    var linesSel = document.getElementById('log-lines');
    var follow = document.getElementById('log-follow');
    var fileChip = document.getElementById('log-file');
    var followAuto = false;

    function loadLogs(scrollPinned) {
      var pinned = viewer.scrollHeight - viewer.scrollTop - viewer.clientHeight < 40;
      return getJSON('api_logs.php?svc=' + encodeURIComponent(svcSel.value) +
        '&lines=' + encodeURIComponent(linesSel.value)).then(function (r) {
        if (r.status === 401) { relogin(); return; }
        if (r.body && r.body.ok) {
          viewer.innerHTML = colorize(r.body.logs || '');
          fileChip.textContent = r.body.file;
          if (scrollPinned && pinned) viewer.scrollTop = viewer.scrollHeight;
        }
      }).catch(function () {});
    }
    function colorize(text) {
      var esc = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
      // 日志格式：[时间] [LEVEL] 内容
      esc = esc.replace(/\[(INFO|WARN|ERROR|DEBUG|FATAL)\s*\]/g,
        function (m, lvl) { return '[<span class="lv-' + lvl + '">' + lvl + '</span>]'; });
      esc = esc.replace(/^(\[[\d\-: ]+\])/gm,
        '<span style="color:#566573">$1</span>');
      return esc;
    }

    document.getElementById('log-refresh').addEventListener('click', function () {
      loadLogs(false);
    });
    svcSel.addEventListener('change', function () {
      history.replaceState(null, '', 'logs.php?svc=' + encodeURIComponent(svcSel.value));
      loadLogs(true);
    });
    linesSel.addEventListener('change', function () { loadLogs(false); });
    follow.addEventListener('change', function () {
      followAuto = follow.checked;
      if (followAuto) {
        viewer.scrollTop = viewer.scrollHeight;
        logTimer = setInterval(function () { loadLogs(true); }, REFRESH);
      } else {
        clearInterval(logTimer);
      }
    });
    document.getElementById('log-download').addEventListener('click', function () {
      var blob = new Blob([viewer.innerText], {type: 'text/plain;charset=utf-8'});
      var a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = svcSel.value + '-' + Date.now() + '.log';
      a.click();
      URL.revokeObjectURL(a.href);
    });
    // 初始滚到底
    viewer.scrollTop = viewer.scrollHeight;
  }

  /* ---------------- 通用：退出确认 ---------------- */
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!confirm(f.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  /* ---------------- 时钟 ---------------- */
  var clock = document.getElementById('clock');
  function tick() {
    if (clock) clock.textContent = new Date().toLocaleString('zh-CN', {hour12: false});
  }
  tick(); setInterval(tick, 1000);
})();
