#!/bin/bash
set -e
L=/mnt/g/xianmu/juqing/baoUIIT
echo "== sync views + lang =="
docker cp "$L/resources/views/." coolify:/var/www/html/resources/views/
docker cp "$L/lang/en.json" coolify:/var/www/html/lang/en.json
docker cp "$L/lang/zh-cn.json" coolify:/var/www/html/lang/zh-cn.json
echo "synced"
docker exec coolify php artisan view:clear
echo "view:clear done"
