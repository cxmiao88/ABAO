#!/bin/bash
set -e
L=/mnt/g/xianmu/juqing/baoUIIT
echo "== sync Livewire/Project =="
docker cp "$L/app/Livewire/Project/." coolify:/var/www/html/app/Livewire/Project/
echo "synced"
docker exec coolify php artisan view:clear
echo "view:clear done"
docker exec coolify sh -c 'grep -c runningDeploymentUrl /var/www/html/app/Livewire/Project/Application/Heading.php || echo 0'
