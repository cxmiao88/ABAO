#!/bin/bash
# 同步服务器模块翻译文件到容器
set -e
cd /mnt/g/xianmu/juqing/baoUIIT

docker cp resources/views/components/server/sidebar.blade.php coolify:/var/www/html/resources/views/components/server/sidebar.blade.php
docker cp resources/views/livewire/server/navbar.blade.php coolify:/var/www/html/resources/views/livewire/server/navbar.blade.php
docker cp resources/views/livewire/server/index.blade.php coolify:/var/www/html/resources/views/livewire/server/index.blade.php
docker cp resources/views/livewire/server/show.blade.php coolify:/var/www/html/resources/views/livewire/server/show.blade.php
docker cp resources/views/livewire/server/partials/server-details.blade.php coolify:/var/www/html/resources/views/livewire/server/partials/server-details.blade.php
docker cp resources/views/livewire/server/partials/localhost-general.blade.php coolify:/var/www/html/resources/views/livewire/server/partials/localhost-general.blade.php
docker cp resources/views/livewire/server/create.blade.php coolify:/var/www/html/resources/views/livewire/server/create.blade.php
docker cp resources/views/livewire/server/new/by-ip.blade.php coolify:/var/www/html/resources/views/livewire/server/new/by-ip.blade.php
docker cp resources/views/components/limit-reached.blade.php coolify:/var/www/html/resources/views/components/limit-reached.blade.php
docker cp lang/zh-cn.json coolify:/var/www/html/lang/zh-cn.json
docker cp lang/en.json coolify:/var/www/html/lang/en.json

docker exec coolify php artisan view:clear
echo "SERVER_BATCH_SYNCED"
