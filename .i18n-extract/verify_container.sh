#!/bin/bash
set -e
echo "== php version =="
docker exec coolify php -r 'echo PHP_VERSION."\n";'
echo "== lang files =="
docker exec coolify ls -la /var/www/html/lang/ | grep -E 'en\.json|zh-cn\.json'
echo "== blade smoke =="
docker cp /mnt/g/xianmu/juqing/baoUIIT/.i18n-extract/blade_smoke.php coolify:/var/www/html/blade_smoke.php
docker exec coolify sh -c 'php blade_smoke.php > /tmp/blade_smoke.out 2>&1; rm -f blade_smoke.php'
docker cp coolify:/tmp/blade_smoke.out /mnt/g/xianmu/juqing/baoUIIT/.i18n-extract/blade_smoke.out
docker exec coolify rm -f /tmp/blade_smoke.out
echo "== smoke output =="
cat /mnt/g/xianmu/juqing/baoUIIT/.i18n-extract/blade_smoke.out
