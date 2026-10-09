#!/bin/bash
docker exec coolify php artisan route:clear 2>&1 | head -3
docker exec coolify php artisan config:clear 2>&1 | head -3
docker exec coolify php artisan route:list 2>/dev/null > /tmp/routes.txt
echo "exit=$?"
wc -l /tmp/routes.txt
grep -i "shared-variables" /tmp/routes.txt | head -30
