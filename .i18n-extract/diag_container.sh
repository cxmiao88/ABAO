#!/bin/bash
echo "== container mounts =="
docker inspect coolify --format '{{range .Mounts}}{{.Type}} {{.Source}} -> {{.Destination}}{{println}}{{end}}'
echo "== app root candidates =="
docker exec coolify sh -c 'ls /var/www/html/lang/ 2>/dev/null | head -5; echo ---; ls /data/coolify 2>/dev/null; echo ---; pwd'
echo "== does /data/coolify/source exist on host (WSL) =="
ls -la /data/coolify/source 2>/dev/null | head -20 || echo "no /data/coolify/source"
echo "== current swarm blade in container =="
docker exec coolify sh -c 'grep -c "swarm_configuration" /var/www/html/resources/views/livewire/project/application/swarm.blade.php 2>/dev/null || echo "not found in /var/www/html"'
