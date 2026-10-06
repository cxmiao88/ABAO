#!/bin/bash
# 同步资源列表页翻译文件到容器
# 注意：容器旧镜像无 Sqlite 支持，组件使用 container-index-compat.php（移除 sqlites），
# 仓库源码 app/Livewire/Project/Resource/Index.php 保持上游新版不变，重建镜像后可去掉此兼容。
set -e
cd /mnt/g/xianmu/juqing/baoUIIT

docker cp resources/views/livewire/project/resource/index.blade.php coolify:/var/www/html/resources/views/livewire/project/resource/index.blade.php
docker cp container-index-compat.php coolify:/var/www/html/app/Livewire/Project/Resource/Index.php
docker cp lang/zh-cn.json coolify:/var/www/html/lang/zh-cn.json
docker cp lang/en.json coolify:/var/www/html/lang/en.json

docker exec coolify php -l /var/www/html/app/Livewire/Project/Resource/Index.php
docker exec coolify php artisan view:clear
echo "ALL_SYNCED"
