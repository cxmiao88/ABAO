#!/bin/bash
# 同步终端页翻译文件到容器
set -e
cd /mnt/g/xianmu/juqing/baoUIIT

FILES=(
  resources/views/livewire/terminal/index.blade.php
  resources/views/livewire/project/shared/terminal.blade.php
  resources/views/components/terminal/theme-selector.blade.php
  lang/zh-cn.json
  lang/en.json
)

for f in "${FILES[@]}"; do
  docker exec coolify mkdir -p "$(dirname "/var/www/html/$f")"
  docker cp "$f" "coolify:/var/www/html/$f" || { echo "FAIL: $f"; exit 1; }
done

docker exec coolify php artisan view:clear
echo "ALL_SYNCED (${#FILES[@]} files)"
