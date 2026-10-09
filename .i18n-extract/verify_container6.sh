#!/bin/bash
docker cp /mnt/g/xianmu/juqing/baoUIIT/.i18n-extract/blade_smoke.php coolify:/var/www/html/blade_smoke.php
docker exec coolify sh -c 'php blade_smoke.php > /tmp/bs.out 2>&1; echo "exit=$?" >> /tmp/bs.out'
docker cp coolify:/tmp/bs.out /mnt/g/xianmu/juqing/baoUIIT/.i18n-extract/bs.out
docker exec coolify rm -f /tmp/bs.out /var/www/html/blade_smoke.php
cat /mnt/g/xianmu/juqing/baoUIIT/.i18n-extract/bs.out
