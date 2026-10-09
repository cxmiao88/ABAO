#!/bin/bash
set -e
SRC=/mnt/g/xianmu/juqing/baoUIIT

echo "=== config (exclude app.php) ==="
tar --no-same-owner --no-same-permissions -C "$SRC/config" --exclude=app.php -cf - . | docker exec -i coolify tar --no-same-owner --no-same-permissions -C /var/www/html/config -xf - || echo "config sync had errors (non-fatal)"
echo "=== database/ ==="
tar --no-same-owner --no-same-permissions -C "$SRC/database" -cf - . | docker exec -i coolify tar --no-same-owner --no-same-permissions -C /var/www/html/database -xf - || echo "database sync had errors"
echo "=== public (exclude build) ==="
tar --no-same-owner --no-same-permissions -C "$SRC/public" --exclude=build -cf - . | docker exec -i coolify tar --no-same-owner --no-same-permissions -C /var/www/html/public -xf - || echo "public sync had errors"
echo "=== composer.json ==="
docker cp "$SRC/composer.json" coolify:/var/www/html/composer.json
echo "=== dump autoload ==="
docker exec coolify composer dump-autoload -o 2>&1 | tail -2
echo "=== clear caches ==="
docker exec coolify php artisan optimize:clear 2>&1 | tail -6
echo "=== restart ==="
docker restart coolify >/dev/null 2>&1
echo ALL_SYNCED
