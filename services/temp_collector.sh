#!/usr/bin/env bash
# 温控采集服务：模拟柜内温度采集与压缩机控制
SVC_ID="temp-collector"
source "$(dirname "${BASH_SOURCE[0]}")/common.sh"

TEMP_C=62            # 6.2°C，用 0.1°C 为单位
TARGET=40            # 目标 4.0°C
HYST=50              # 高于 5.0°C 开压缩机
COMP=0

log "INFO" "温控采集服务启动，采样周期 5s，探头 sensor-01/sensor-02"
while true; do
  if (( COMP == 1 )); then
    TEMP_C=$((TEMP_C - (RANDOM % 4) - 1))     # 每次 -0.1 ~ -0.4°C
    if (( TEMP_C <= TARGET )); then COMP=0; log "INFO" "温度达到目标，压缩机停机"; fi
  else
    TEMP_C=$((TEMP_C + (RANDOM % 3)))         # 每次 +0.0 ~ +0.2°C
    if (( TEMP_C >= HYST )); then COMP=1; log "INFO" "温度超限，压缩机启动"; fi
  fi
  (( TEMP_C < 15 )) && TEMP_C=15
  t1=$((TEMP_C)); t2=$((TEMP_C + (RANDOM % 3) - 1))
  temp1="$(awk -v x="$t1" 'BEGIN{printf "%.1f", x/10}')"
  temp2="$(awk -v x="$t2" 'BEGIN{printf "%.1f", x/10}')"
  state=$(( COMP == 1 ? 1 : 0 ))
  sname="cooling"; (( COMP == 0 )) && sname="idle"
  log "INFO" "采集 sensor-01=${temp1}°C sensor-02=${temp2}°C compressor=$sname 上报=OK"
  if (( RANDOM % 40 == 0 )); then log "WARN" "sensor-02 数据抖动，已按中值滤波处理"; fi
  sleep_loop 5
done
