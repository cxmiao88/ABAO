#!/bin/bash
set -e
docker cp /mnt/g/xianmu/juqing/baoUIIT/.i18n-extract/blade_smoke2.php coolify:/var/www/html/blade_smoke2.php
docker exec coolify php /var/www/html/blade_smoke2.php
