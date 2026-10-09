#!/bin/bash
echo "== local heading.blade.php component refs =="
grep -oE '<x-[a-z0-9-]+' "G:\xianmu\juqing\baoUIIT\resources\views\livewire\project\application\heading.blade.php" | sort -u
echo "== local components dir (matching) =="
ls "G:\xianmu\juqing\baoUIIT\resources\views\components" 2>/dev/null | grep -iE "deploy|indicator|badge" || echo "(none in root components)"
find "G:\xianmu\juqing\baoUIIT\resources\views\components" -iname "*deploy*" 2>/dev/null
echo "== local app/View/Components =="
find "G:\xianmu\juqing\baoUIIT\app" -iname "*Deploy*" -o -iname "*Indicator*" -o -iname "*Badge*" 2>/dev/null | head -10
echo "== container components dir =="
docker exec coolify sh -c 'ls /var/www/html/resources/views/components/ | grep -iE "deploy|indicator|badge" || echo "(none in container components)"'
echo "== container app/View/Components =="
docker exec coolify sh -c 'ls /var/www/html/app/View/Components/ 2>/dev/null | head -20'
