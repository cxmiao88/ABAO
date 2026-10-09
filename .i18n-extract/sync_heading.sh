#!/bin/bash
L=/mnt/g/xianmu/juqing/baoUIIT
echo "== file sizes =="
wc -c "$L/app/Livewire/Project/Application/Heading.php"
docker exec coolify sh -c 'wc -c /var/www/html/app/Livewire/Project/Application/Heading.php'
echo "== cp single file =="
docker cp "$L/app/Livewire/Project/Application/Heading.php" coolify:/var/www/html/app/Livewire/Project/Application/Heading.php
docker exec coolify sh -c 'wc -c /var/www/html/app/Livewire/Project/Application/Heading.php; grep -c runningDeploymentUrl /var/www/html/app/Livewire/Project/Application/Heading.php || echo 0'
