#!/usr/bin/env bash
# 支付监听服务：模拟轮询支付平台回调队列
SVC_ID="pay-listener"
source "$(dirname "${BASH_SOURCE[0]}")/common.sh"

log "INFO" "支付监听服务启动 (模拟回调监听 :8081/callback)"
ORDERS=("ORD2026091400" "ORD2026091401" "ORD2026091402")
CHANNELS=("微信支付" "支付宝" "银联云闪付")
while true; do
  r=$((RANDOM % 100))
  if (( r < 35 )); then
    idx=$((RANDOM % ${#ORDERS[@]})); o="${ORDERS[$idx]}$((RANDOM % 900 + 100))"
    cents=$(( (RANDOM % 60 + 3) * 100 ))
    yuan="$(awk -v c="$cents" 'BEGIN{printf "%.2f", c/100}')"
    ch="${CHANNELS[$((RANDOM % 3))]}"
    lane=$((RANDOM % 36 + 1))
    log "INFO" "收到支付回调 order=$o channel=$ch amount=¥$yuan lane=A$lane"
    log "INFO" "出货指令已下发 lane=A$lane result=OK"
    sleep_loop 1
  elif (( r < 42 )); then
    log "WARN" "回调签名校验重试 order=retry-$((RANDOM % 9999)) attempt=2"
  else
    log "DEBUG" "长轮询中... 暂无新订单"
  fi
  sleep_loop 3
done
