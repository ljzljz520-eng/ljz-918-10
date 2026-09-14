#!/usr/bin/env bash
# 库存上报服务：周期性采集货道余量并上报
SVC_ID="stock-reporter"
source "$(dirname "${BASH_SOURCE[0]}")/common.sh"

ITEMS=("可乐330ml" "矿泉水550ml" "绿茶500ml" "美式咖啡" "功能饮料" "苏打水" "橙汁" "牛奶250ml")
declare -A STOCK
for i in "${!ITEMS[@]}"; do STOCK[$i]=$((RANDOM % 20 + 8)); done

log "INFO" "库存上报服务启动，上报周期 20s，平台 https://ops.vm-local/api/stock"
while true; do
  # 模拟随机出货扣减
  i=$((RANDOM % ${#ITEMS[@]}))
  if (( STOCK[$i] > 0 )); then
    STOCK[$i]=$((STOCK[$i] - 1))
    (( STOCK[$i] < 5 )) && log "WARN" "货道 $((i+1)) ${ITEMS[$i]} 库存告急 count=${STOCK[$i]}"
  fi
  total=0; for v in "${STOCK[@]}"; do total=$((total + v)); done
  log "INFO" "库存快照已上报 items=${#ITEMS[@]} total=$total status=200 latency=$((RANDOM % 80 + 12))ms"
  if (( RANDOM % 25 == 0 )); then
    log "ERROR" "平台连接超时，已进入本地缓存队列 (queued=1)"
    sleep_loop 2
    log "INFO" "缓存补传成功 queued=0"
  fi
  sleep_loop 20
done
