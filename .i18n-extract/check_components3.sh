#!/bin/bash
OUT=/mnt/g/xianmu/juqing/baoUIIT/.i18n-extract/components.out
{
echo "== local app/View/Components =="
find "G:\xianmu\juqing\baoUIIT\app\View\Components" -type f 2>/dev/null | sed 's|G:\\xianmu\\juqing\\baoUIIT\\||'
echo "== local components tree (top) =="
ls "G:\xianmu\juqing\baoUIIT\resources\views\components" 2>/dev/null
echo "== local deploying/status/split =="
find "G:\xianmu\juqing\baoUIIT\resources\views\components" \( -iname "*deploy*" -o -iname "*status-summary*" -o -iname "*split-action*" \) 2>/dev/null | sed 's|G:\\xianmu\\juqing\\baoUIIT\\||'
} > "$OUT"
cat "$OUT"
echo "---- container side ----"
docker exec coolify sh -c 'echo "== container components top =="; ls /var/www/html/resources/views/components/; echo "== container View/Components =="; find /var/www/html/app/View/Components -type f; echo "== container deploying-indicator =="; find /var/www/html/resources/views/components /var/www/html/app/View/Components -iname "*deploy*" 2>/dev/null' > /tmp/c.out 2>&1
docker cp coolify:/tmp/c.out "$OUT"
cat "$OUT"
