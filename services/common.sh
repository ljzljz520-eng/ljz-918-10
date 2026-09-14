#!/usr/bin/env bash
# 公共函数：所有守护服务 source 此文件
set -u
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
RUN_DIR="$ROOT_DIR/var/run"
mkdir -p "$RUN_DIR"

# 写入自己的 PID，并登记进程组便于整组停止
echo "$$" > "$RUN_DIR/${SVC_ID}.pid"

shutdown() { log "INFO" "收到停止信号，服务退出"; rm -f "$RUN_DIR/${SVC_ID}.pid"; exit 0; }
trap shutdown TERM INT

now() { date '+%Y-%m-%d %H:%M:%S'; }
log() { # $1=level $2..=message
  local lvl="$1"; shift
  printf '[%s] [%-5s] %s\n' "$(now)" "$lvl" "$*"
}

sleep_loop() { # 可被 TERM 中断的 sleep
  sleep "$1" &
  local sp=$!
  wait "$sp" 2>/dev/null
}
