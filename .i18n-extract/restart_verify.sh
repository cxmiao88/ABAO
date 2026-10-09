#!/bin/bash
set -e
echo "== restart coolify =="
docker restart coolify
sleep 5
echo "== health =="
docker ps --filter name=coolify --format '{{.Names}} {{.Status}}'
echo "== HTTP after restart =="
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8000/
echo "== swarm blade still translated after restart =="
docker exec coolify sh -c 'grep -c "swarm_configuration" /var/www/html/resources/views/livewire/project/application/swarm.blade.php'
echo "== zh-cn key still present =="
docker exec coolify php -r '$d=json_decode(file_get_contents("/var/www/html/lang/zh-cn.json"),true); echo $d["application.swarm_configuration"]."\n";'
