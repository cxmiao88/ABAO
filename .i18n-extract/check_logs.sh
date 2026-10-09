#!/bin/bash
echo "== last 40 lines of app log (errors / requests) =="
docker logs coolify --tail 40 2>/dev/null | tail -40
echo "== laravel log tail =="
docker exec coolify sh -c 'tail -20 /var/www/html/storage/logs/laravel.log 2>/dev/null | grep -iE "error|exception" | tail -5 || echo "(no laravel errors)"'
