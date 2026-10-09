#!/bin/bash
set -e
L=/mnt/g/xianmu/juqing/baoUIIT
echo "== syncing components dir =="
docker cp "$L/resources/views/components" coolify:/var/www/html/resources/views/components
echo "components synced"
docker exec coolify php artisan view:clear
echo "view:clear done"
echo "== verify deploying-indicator now present =="
docker exec coolify sh -c 'ls -la /var/www/html/resources/views/components/deploying-indicator.blade.php'
