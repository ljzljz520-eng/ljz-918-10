#!/usr/bin/env bash
# 一键部署：修正权限并做目录自检
# 用法: ./setup.sh [php运行用户，默认 www-data]
set -e
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WEBUSER="${1:-www-data}"

chmod +x "$ROOT/bin/vmctl" "$ROOT"/services/*.sh
chmod 750 "$ROOT/config" && chmod 640 "$ROOT/config/config.php"
mkdir -p "$ROOT/var/run" "$ROOT/var/logs" "$ROOT/var/audit"

if id "$WEBUSER" >/dev/null 2>&1 && [[ "$(id -u)" -eq 0 ]]; then
  chown -R "$WEBUSER":"$WEBUSER" "$ROOT/var"
  echo "已将 var/ 属主设为 $WEBUSER"
else
  chmod -R 770 "$ROOT/var"
  echo "提示：非 root 无法 chown $WEBUSER，已设置 var/ 为 770；请确保 PHP 运行用户同组。"
fi

echo "自检 vmctl："
"$ROOT/bin/vmctl" status
echo
echo "部署完成。默认登录：admin / admin123（请及时登录后修改密码）"
