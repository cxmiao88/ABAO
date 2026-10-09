#!/bin/bash
set -e
echo "== minimal php -r =="
docker exec coolify php -r "echo 'min-ok';"
echo
echo "== blade_smoke run =="
docker cp /mnt/g/xianmu/juqing/baoUIIT/.i18n-extract/blade_smoke.php coolify:/var/www/html/blade_smoke.php
docker exec coolify php /var/www/html/blade_smoke.php
