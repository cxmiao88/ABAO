#!/bin/bash
echo "== local general.blade.php keys =="
grep -o "__('application\.[a-z_0-9]*'" /mnt/g/xianmu/juqing/baoUIIT/resources/views/livewire/project/application/general.blade.php | sort -u | head -20
echo "== local general size =="
wc -c /mnt/g/xianmu/juqing/baoUIIT/resources/views/livewire/project/application/general.blade.php
echo "== container general size =="
docker exec coolify sh -c 'wc -c /var/www/html/resources/views/livewire/project/application/general.blade.php'
echo "== container general first 5 lines =="
docker exec coolify sh -c 'head -5 /var/www/html/resources/views/livewire/project/application/general.blade.php'
echo "== container general grep application. =="
docker exec coolify sh -c 'grep -c "application\." /var/www/html/resources/views/livewire/project/application/general.blade.php || echo 0'
