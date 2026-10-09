#!/bin/bash
L=/mnt/g/xianmu/juqing/baoUIIT
echo "== local Livewire Project Application dirs =="
find "$L/app/Livewire/Project" -maxdepth 2 -type d | sed "s|$L/||"
echo "== local Heading.php exists? =="
ls "$L/app/Livewire/Project/Application/Heading.php" 2>/dev/null && echo YES || echo NO
echo "== container Heading.php exists? =="
docker exec coolify sh -c 'ls /var/www/html/app/Livewire/Project/Application/Heading.php 2>/dev/null && echo YES || echo NO'
echo "== container has runningDeploymentUrl? =="
docker exec coolify sh -c 'grep -c runningDeploymentUrl /var/www/html/app/Livewire/Project/Application/Heading.php 2>/dev/null || echo "0/notfound"'
