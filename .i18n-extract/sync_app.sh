#!/bin/bash
docker cp /mnt/g/xianmu/juqing/baoUIIT/app/Livewire/Team/AuditLog.php coolify:/var/www/html/app/Livewire/Team/AuditLog.php
docker cp /mnt/g/xianmu/juqing/baoUIIT/app/Livewire/Server/DockerRegistries/. coolify:/var/www/html/app/Livewire/Server/DockerRegistries/
docker cp /mnt/g/xianmu/juqing/baoUIIT/app/Livewire/Project/New/Select.php coolify:/var/www/html/app/Livewire/Project/New/Select.php
docker cp /mnt/g/xianmu/juqing/baoUIIT/resources/views/. coolify:/var/www/html/resources/views/
docker cp /mnt/g/xianmu/juqing/baoUIIT/lang/en.json coolify:/var/www/html/lang/en.json
docker cp /mnt/g/xianmu/juqing/baoUIIT/lang/zh-cn.json coolify:/var/www/html/lang/zh-cn.json
docker exec coolify php artisan view:clear 2>&1 | tail -1
docker exec coolify php artisan route:clear 2>&1 | tail -1
echo done
