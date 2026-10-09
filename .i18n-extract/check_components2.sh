#!/bin/bash
echo "== local component locations =="
find "G:\xianmu\juqing\baoUIIT\resources\views\components" -iname "*deploying*" -o -iname "*status-summary*" -o -iname "*split-action*" 2>/dev/null
find "G:\xianmu\juqing\baoUIIT\app\View\Components" -type f 2>/dev/null | head -30
echo "== local components tree (top) =="
ls "G:\xianmu\juqing\baoUIIT\resources\views\components" 2>/dev/null
echo "== container components tree (top) =="
docker exec coolify sh -c 'ls /var/www/html/resources/views/components/'
echo "== container has deploying-indicator? =="
docker exec coolify sh -c 'find /var/www/html/resources/views/components -iname "*deploy*"; find /var/www/html/app/View/Components -iname "*Deploy*"'
echo "== container View/Components full =="
docker exec coolify sh -c 'find /var/www/html/app/View/Components -type f | head -30'
