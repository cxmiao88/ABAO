#!/bin/bash
set -e
ROOT=/mnt/g/xianmu/juqing/baoUIIT
# 路由
docker cp $ROOT/routes/web.php coolify:/var/www/html/routes/web.php
# 布局
docker cp $ROOT/resources/views/layouts/panel.blade.php coolify:/var/www/html/resources/views/layouts/panel.blade.php
# 组件
for f in Home Sites Databases Docker Monitoring System; do
  docker cp $ROOT/app/Livewire/Panel/$f.php coolify:/var/www/html/app/Livewire/Panel/$f.php
done
# 视图
for f in home sites databases docker monitoring system; do
  docker cp $ROOT/resources/views/livewire/panel/$f.blade.php coolify:/var/www/html/resources/views/livewire/panel/$f.blade.php
done
docker exec coolify php artisan view:clear >/dev/null 2>&1
docker restart coolify >/dev/null 2>&1
echo synced-restarted
