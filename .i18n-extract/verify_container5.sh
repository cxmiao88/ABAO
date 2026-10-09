#!/bin/bash
set -e
docker cp /mnt/g/xianmu/juqing/baoUIIT/.i18n-extract/blade_smoke3.php coolify:/var/www/html/blade_smoke3.php
docker exec coolify php /var/www/html/blade_smoke3.php
