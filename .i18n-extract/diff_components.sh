#!/bin/bash
L=/mnt/g/xianmu/juqing/baoUIIT
docker exec coolify sh -c 'cd /var/www/html/resources/views/components && find . -type f | sort' > /tmp/c_files.txt 2>&1
docker cp coolify:/tmp/c_files.txt "$L/.i18n-extract/c_files.txt" 2>/dev/null || echo "cp failed"
find "$L/resources/views/components" -type f | sed "s|$L/||" | sort > "$L/.i18n-extract/l_files.txt"
echo "== container-only component files =="
comm -13 <(sed 's|^|resources/views/|' "$L/.i18n-extract/l_files.txt") "$L/.i18n-extract/c_files.txt" | head -40
echo "== local-only component files (not in container) =="
comm -23 <(sed 's|^|resources/views/|' "$L/.i18n-extract/l_files.txt") "$L/.i18n-extract/c_files.txt" | head -60
echo "== counts =="
wc -l "$L/.i18n-extract/l_files.txt" "$L/.i18n-extract/c_files.txt"
