#!/bin/bash
# 同步服务器模块第二批（二级页面）
set -e
cd /mnt/g/xianmu/juqing/baoUIIT

FILES="
resources/views/livewire/server/resources.blade.php
resources/views/livewire/server/destinations.blade.php
resources/views/livewire/server/advanced.blade.php
resources/views/livewire/server/delete.blade.php
resources/views/livewire/server/validate-and-install.blade.php
resources/views/livewire/server/docker-cleanup.blade.php
resources/views/livewire/server/docker-images.blade.php
resources/views/livewire/server/private-key/show.blade.php
"
for f in $FILES; do
    docker cp "$f" "coolify:/var/www/html/$f"
done
docker cp lang/zh-cn.json coolify:/var/www/html/lang/zh-cn.json
docker cp lang/en.json coolify:/var/www/html/lang/en.json
docker exec coolify php artisan view:clear
echo "SERVER_BATCH2_SYNCED"
