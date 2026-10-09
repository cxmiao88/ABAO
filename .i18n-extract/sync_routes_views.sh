#!/bin/bash
set -e
L=/mnt/g/xianmu/juqing/baoUIIT
echo "== sync routes =="
docker cp "$L/routes/." coolify:/var/www/html/routes/
echo "routes synced"
echo "== sync full resources/views (local authoritative) =="
docker cp "$L/resources/views/." coolify:/var/www/html/resources/views/
echo "views synced"
echo "== clear caches =="
docker exec coolify php artisan route:clear
docker exec coolify php artisan view:clear
docker exec coolify php artisan config:clear
echo "caches cleared"
echo "== verify analytics route now defined =="
docker exec coolify php artisan route:list --name=application.analytics 2>/dev/null | head -3 || echo "route list n/a"
