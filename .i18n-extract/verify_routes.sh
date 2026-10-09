#!/bin/bash
echo "== unauth access to app routes (expect 302 login redirect) =="
for u in /project /login /dashboard; do
  code=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:8000$u")
  echo "$u -> $code"
done
echo "== container general.blade.php translated? =="
docker exec coolify sh -c 'grep -c "gen_title\|gen_go_labels" /var/www/html/resources/views/livewire/project/application/general.blade.php'
echo "== container lang diff vs local (size) =="
ls -la /mnt/g/xianmu/juqing/baoUIIT/lang/en.json /mnt/g/xianmu/juqing/baoUIIT/lang/zh-cn.json
docker exec coolify sh -c 'wc -c /var/www/html/lang/en.json /var/www/html/lang/zh-cn.json'
