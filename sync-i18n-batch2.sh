#!/bin/bash
# 同步翻译批次文件到容器（二开期间临时用，正式方案是重建镜像/bind mount）
set -e
cd /mnt/g/xianmu/juqing/baoUIIT

FILES=(
  resources/views/livewire/dashboard.blade.php
  resources/views/livewire/dashboard/active-deployments.blade.php
  resources/views/livewire/dashboard/traffic-analytics.blade.php
  resources/views/livewire/project/index.blade.php
  resources/views/livewire/project/show.blade.php
  resources/views/livewire/project/add-empty.blade.php
  resources/views/livewire/project/edit.blade.php
  resources/views/livewire/project/environment-edit.blade.php
  resources/views/livewire/project/delete-project.blade.php
  resources/views/livewire/project/delete-environment.blade.php
  resources/views/livewire/project/clone-me.blade.php
  lang/zh-cn.json
  lang/en.json
)

for f in "${FILES[@]}"; do
  docker cp "$f" "coolify:/var/www/html/$f" || { echo "FAIL: $f"; exit 1; }
done

docker exec coolify php artisan view:clear
echo "ALL_SYNCED (${#FILES[@]} files)"
