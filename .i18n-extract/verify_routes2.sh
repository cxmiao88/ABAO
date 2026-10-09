#!/bin/bash
echo "== routes =="
for u in /project /login /dashboard; do
  code=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:8000$u")
  echo "$u -> $code"
done
echo "== local lang sizes =="
ls -la /mnt/g/xianmu/juqing/baoUIIT/lang/en.json /mnt/g/xianmu/juqing/baoUIIT/lang/zh-cn.json
echo "== container general blade keys =="
docker exec coolify sh -c 'grep -c "gen_title" /var/www/html/resources/views/livewire/project/application/general.blade.php || echo "no gen_title"'
