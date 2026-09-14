# 无人售货机本地守护面板

面向无人售货机终端的本机运维面板，统一管理四个后台服务，支持启停、重启、
日志查看，并提供登录鉴权、目录自检、操作留痕和状态自动刷新。

## 功能

| 模块 | 说明 |
|---|---|
| 支付监听 `pay-listener` | 监听支付回调，驱动出货指令 |
| 库存上报 `stock-reporter` | 定时采集货道余量并上报 |
| 温控采集 `temp-collector` | 采集柜温、控制压缩机 |
| 广告播放 `ad-player` | 轮播本地广告并回传播放回执 |

- 仪表盘：服务状态/PID/运行时长，**每 5 秒自动刷新**（页面不可见时暂停）
- 操作：单个或批量 **启动 / 停止 / 重启**，操作结果即时回显
- 日志：按服务查看、行数选择、自动跟随、关键字级别着色、下载
- 目录自检：检查目录/文件/权限/PHP 环境，缺失的运行时目录可一键自动修复
- 操作记录：登录、启停、自检、改密全部写入审计日志，支持模块/结果筛选
- 登录：bcrypt 密码、CSRF 防护、登录失败 5 次锁定 5 分钟、会话空闲超时
- 所有 **PHP 页面均位于 `www/`**

## 目录结构

```
config/config.php          全局配置（账号、路径、服务白名单、刷新间隔）
www/                       所有 PHP 页面与静态资源
  ├─ login.php / logout.php
  ├─ index.php             服务总览（仪表盘）
  ├─ logs.php              日志查看
  ├─ selfcheck.php         目录自检
  ├─ history.php           操作记录
  ├─ tools_change_password.php  修改密码
  ├─ api_status.php        状态 JSON（轮询）
  ├─ api_service.php       启停/重启 JSON
  ├─ api_logs.php          日志 JSON
  ├─ lib/                  引导/鉴权/CSRF/服务控制/自检/审计/布局
  └─ assets/               style.css / app.js
bin/vmctl                  唯一受控的服务控制脚本（白名单参数）
services/                  四个守护服务（Bash 模拟实现，可替换为真实程序）
var/run  var/logs  var/audit   PID、服务日志、审计日志（运行期生成）
setup.sh                   部署权限脚本
deploy/                    nginx / php-fpm 参考配置
```

## 快速开始

需要 PHP 8.0+（无第三方扩展依赖，需允许 `shell_exec`）与 Bash 4+。

```bash
# 1. 部署权限（生产用 root 指定 php-fpm 用户）
./setup.sh www-data

# 2. 开发环境直接用内置服务器验证
php -S 127.0.0.1:8080 -t www

# 3. 浏览器打开（默认账号 admin / admin123，登录后请立即改密）
#    http://127.0.0.1:8080/login.php
```

生产建议使用 nginx + php-fpm，参考 `deploy/vmpanel.nginx.conf`，
并仅监听内网/回环地址。

## 安全设计

1. **参数白名单**：PHP 不拼 shell，仅以 `escapeshellarg` 调用 `bin/vmctl`，
   服务标识与动作均在 PHP 与 Bash 两层白名单校验，`--lines` 限定 1–2000。
2. **进程治理**：服务以 `setsid` 独立进程组启动、自写 PID 文件；
   停止按进程组 TERM→等待→KILL，自动清理失效 PID。
3. **登录安全**：bcrypt 哈希、`hash_equals` 防时序比较、失败锁定、
   `HttpOnly; SameSite=Strict` Cookie、会话 regenerate、空闲 30 分钟超时。
4. **CSRF**：所有 POST（含登出）携带一次性 token，AJAX 头/表单双通道校验。
5. **最小暴露**：`www/lib` 禁止 HTTP 访问（.htaccess + PHP 守卫常量双保险），
   其余敏感数据（日志/PID/审计）放在 `www` 之外。
6. **操作留痕**：JSON Lines 审计日志带时间、用户、IP、模块、动作、结果，
   追加写 + 文件锁。

## 接入真实服务

`services/*.sh` 是可运行的模拟守护进程。接真实程序时，只需让真实程序
以前台常驻方式运行并将 `echo $$ > var/run/<服务id>.pid`，或在
`bin/vmctl` 的 `SERVICES` 表中替换脚本名即可，面板与协议无需改动。
