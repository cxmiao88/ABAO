#!/bin/bash
set -e
L=/mnt/g/xianmu/juqing/baoUIIT
echo "== remove wrong nested dir =="
docker exec coolify sh -c 'rm -rf /var/www/html/resources/views/components/components && echo removed'
echo "== copy components content =="
docker cp "$L/resources/views/components/." coolify:/var/www/html/resources/views/components/
echo "content copied"
docker exec coolify php artisan view:clear
echo "view:clear done"
echo "== verify =="
docker exec coolify sh -c 'ls /var/www/html/resources/views/components/deploying-indicator.blade.php /var/www/html/resources/views/components/status-summary.blade.php /var/www/html/resources/views/components/application/settings-section.blade.php 2>&1'
