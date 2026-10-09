#!/bin/bash
set -e
echo "== run pending migrations =="
docker exec coolify php artisan migrate --force 2>&1 | tail -40 > /mnt/g/xianmu/juqing/baoUIIT/.i18n-extract/migrate_run.txt || true
cat /mnt/g/xianmu/juqing/baoUIIT/.i18n-extract/migrate_run.txt
echo "== verify github_runner_configs table =="
docker exec coolify php artisan tinker --execute='echo Schema::hasTable("github_runner_configs") ? "TABLE_OK" : "TABLE_MISSING";' 2>&1 | tail -2
