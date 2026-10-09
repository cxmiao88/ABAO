#!/bin/bash
set -e
L=/mnt/g/xianmu/juqing/baoUIIT
echo "== local migrations count =="
find "$L/database/migrations" -name "*.php" | wc -l
echo "== container migrations count =="
docker exec coolify sh -c 'find /var/www/html/database/migrations -name "*.php" | wc -l'
echo "== sync migrations =="
docker cp "$L/database/migrations/." coolify:/var/www/html/database/migrations/
echo "migrations synced"
echo "== migrate status (tail) =="
docker exec coolify php artisan migrate:status 2>&1 | tail -30 > /mnt/g/xianmu/juqing/baoUIIT/.i18n-extract/migrate_status.txt || true
cat /mnt/g/xianmu/juqing/baoUIIT/.i18n-extract/migrate_status.txt
