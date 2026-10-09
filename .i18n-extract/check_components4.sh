#!/bin/bash
L=/mnt/g/xianmu/juqing/baoUIIT
OUT=$L/.i18n-extract/components.out
{
echo "== local app/View/Components files =="
find "$L/app/View/Components" -type f 2>/dev/null
echo "== local resources/views/components (top) =="
ls "$L/resources/views/components" 2>/dev/null
echo "== local deploying/status/split/summary components =="
find "$L/resources/views/components" \( -iname "*deploy*" -o -iname "*status-summary*" -o -iname "*split-action*" -o -iname "*reicon*" -o -iname "*modal-confirmation*" \) 2>/dev/null
} > "$OUT"
echo "== LOCAL =="
cat "$OUT"
docker exec coolify sh -c 'echo "== CONTAINER components top =="; ls /var/www/html/resources/views/components/; echo "== CONTAINER View/Components =="; find /var/www/html/app/View/Components -type f; echo "== CONTAINER deploy components =="; find /var/www/html/resources/views/components /var/www/html/app/View/Components -iname "*deploy*" 2>/dev/null; echo END' > /tmp/c.out 2>&1
docker cp coolify:/tmp/c.out "$OUT" 2>/dev/null
echo "== CONTAINER =="
cat "$OUT"
