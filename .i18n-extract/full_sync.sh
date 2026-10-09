#!/bin/bash
set -e
SRC=/mnt/g/xianmu/juqing/baoUIIT

sync_dir() {
  local from="$1" to="$2" exclude="$3"
  if [ -n "$exclude" ]; then
    tar -C "$from" --exclude="$exclude" -cf - . | docker exec -i coolify tar -C "$to" -xf -
  else
    tar -C "$from" -cf - . | docker exec -i coolify tar -C "$to" -xf -
  fi
  echo "synced: $from -> $to"
}

echo "=== app/ ==="
sync_dir "$SRC/app" "/var/www/html/app"
echo "=== bootstrap/ ==="
sync_dir "$SRC/bootstrap" "/var/www/html/bootstrap" "cache"
echo "=== routes/ ==="
sync_dir "$SRC/routes" "/var/www/html/routes"
echo "=== config/ ==="
sync_dir "$SRC/config" "/var/www/html/config"
echo "=== database/ ==="
sync_dir "$SRC/database" "/var/www/html/database"
echo "=== public (exclude build) ==="
sync_dir "$SRC/public" "/var/www/html/public" "build"
echo "=== composer.json ==="
docker cp "$SRC/composer.json" coolify:/var/www/html/composer.json
echo "=== dump autoload ==="
docker exec coolify composer dump-autoload -o 2>&1 | tail -2
echo "=== clear caches ==="
docker exec coolify php artisan optimize:clear 2>&1 | tail -6
echo "=== restart ==="
docker restart coolify >/dev/null 2>&1
echo ALL_SYNCED
