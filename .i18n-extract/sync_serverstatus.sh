#!/bin/bash
set -e
docker cp /mnt/g/xianmu/juqing/baoUIIT/app/Models/Server.php coolify:/var/www/html/app/Models/Server.php
docker cp /mnt/g/xianmu/juqing/baoUIIT/app/Livewire/Dashboard/ServerStatus.php coolify:/var/www/html/app/Livewire/Dashboard/ServerStatus.php
docker cp /mnt/g/xianmu/juqing/baoUIIT/app/Livewire/Server/SystemOverview.php coolify:/var/www/html/app/Livewire/Server/SystemOverview.php
docker cp /mnt/g/xianmu/juqing/baoUIIT/bootstrap/helpers/shared.php coolify:/var/www/html/bootstrap/helpers/shared.php
docker cp /mnt/g/xianmu/juqing/baoUIIT/resources/views/. coolify:/var/www/html/resources/views/
docker cp /mnt/g/xianmu/juqing/baoUIIT/lang/en.json coolify:/var/www/html/lang/en.json
docker cp /mnt/g/xianmu/juqing/baoUIIT/lang/zh-cn.json coolify:/var/www/html/lang/zh-cn.json
docker exec coolify php artisan view:clear 2>&1 | tail -1
docker exec coolify php artisan route:clear 2>&1 | tail -1
docker restart coolify >/dev/null 2>&1
echo "synced and restarted"
