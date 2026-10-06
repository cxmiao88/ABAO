#!/bin/bash
# 同步服务器模块第三批（proxy/log-drains/sentinel/cloudflare-tunnel）
set -e
cd /mnt/g/xianmu/juqing/baoUIIT

FILES="
resources/views/livewire/server/proxy.blade.php
resources/views/livewire/server/proxy/show.blade.php
resources/views/livewire/server/proxy/logs.blade.php
resources/views/livewire/server/proxy/dynamic-configurations.blade.php
resources/views/livewire/server/proxy/certificates.blade.php
resources/views/livewire/server/proxy/certificates-show.blade.php
resources/views/livewire/server/proxy/new-dynamic-configuration.blade.php
resources/views/livewire/server/proxy/dynamic-configuration-navbar.blade.php
resources/views/livewire/server/log-drains.blade.php
resources/views/livewire/server/sentinel.blade.php
resources/views/livewire/server/sentinel/show.blade.php
resources/views/livewire/server/sentinel/logs.blade.php
resources/views/livewire/server/cloudflare-tunnel.blade.php
"
for f in $FILES; do
    docker cp "$f" "coolify:/var/www/html/$f"
done
docker cp lang/zh-cn.json coolify:/var/www/html/lang/zh-cn.json
docker cp lang/en.json coolify:/var/www/html/lang/en.json
docker exec coolify php artisan view:clear
echo "SERVER_BATCH3_SYNCED"
