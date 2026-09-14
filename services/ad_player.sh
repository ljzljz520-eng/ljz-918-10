#!/usr/bin/env bash
# 广告播放服务：轮播本地广告物料
SVC_ID="ad-player"
source "$(dirname "${BASH_SOURCE[0]}")/common.sh"

ADS=("brand_cola.mp4:15" "summer_promo.mp4:30" "new_tea.mp4:20" "member_day.mp4:15" "ops_notice.jpg:10")
IDX=0
log "INFO" "广告播放服务启动，屏幕 screen-main(1080x1920)，素材 ${#ADS[@]} 个"
while true; do
  entry="${ADS[$((IDX % ${#ADS[@]}))]}"
  file="${entry%%:*}"; dur="${entry##*:}"
  log "INFO" "开始播放 media=$file duration=${dur}s slot=screen-main"
  sleep_loop "$dur"
  log "INFO" "播放回执已上报 media=$file completed=1 proof=ack-$((RANDOM % 900000 + 100000))"
  IDX=$((IDX + 1))
  if (( RANDOM % 20 == 0 )); then
    log "WARN" "素材远端清单同步失败，使用本地缓存清单"
  fi
done
