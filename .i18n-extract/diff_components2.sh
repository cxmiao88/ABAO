#!/bin/bash
L=/mnt/g/xianmu/juqing/baoUIIT
docker exec coolify sh -c 'cd /var/www/html/resources/views/components && find . -type f | sort' > "$L/.i18n-extract/c_files.txt" 2>/dev/null
echo "container files captured: $(wc -l < "$L/.i18n-extract/c_files.txt")"
echo "== local-only components (missing in container) =="
comm -23 "$L/.i18n-extract/l_files.txt" "$L/.i18n-extract/c_files.txt" | head -60
echo "== container-only components =="
comm -13 "$L/.i18n-extract/l_files.txt" "$L/.i18n-extract/c_files.txt" | head -40
