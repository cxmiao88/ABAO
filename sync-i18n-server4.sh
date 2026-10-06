#!/bin/bash
# 同步服务器模块第四批（云平台开通/指标/Swarm/GitHub Runners/镜像仓库）
set -e
cd /mnt/g/xianmu/juqing/baoUIIT

FILES="
resources/views/livewire/server/new/by-hetzner.blade.php
resources/views/livewire/server/new/by-vultr.blade.php
resources/views/livewire/server/new/by-digital-ocean.blade.php
resources/views/livewire/server/charts.blade.php
resources/views/livewire/server/swarm.blade.php
resources/views/livewire/server/github-runners.blade.php
resources/views/livewire/server/docker-registries.blade.php
resources/views/components/server/provider-token-picker.blade.php
"
for f in $FILES; do
    docker cp "$f" "coolify:/var/www/html/$f"
done
docker cp lang/zh-cn.json coolify:/var/www/html/lang/zh-cn.json
docker cp lang/en.json coolify:/var/www/html/lang/en.json
docker exec coolify php artisan view:clear
echo "SERVER_BATCH4_SYNCED"
