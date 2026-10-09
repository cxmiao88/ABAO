#!/bin/bash
docker exec coolify sh -c "tail -80 /var/www/html/storage/logs/laravel.log 2>/dev/null" | grep -iE "error|exception|failed|login|csrf" | tail -15
echo "---LOG_TAIL_DONE---"
docker logs coolify --tail 20 2>&1 | tail -20
echo "---DOCKER_DONE---"
