#!/bin/bash
docker exec coolify sh -c 'ls /var/www/html/resources/views/livewire/dashboard/' > /tmp/dash.txt 2>&1
echo "--- files ---"
cat /tmp/dash.txt
echo "--- grep workspaces ---"
docker exec coolify sh -c 'grep -rl "workspaces" /var/www/html/resources/views/ 2>/dev/null | head -5' > /tmp/ws.txt 2>&1
cat /tmp/ws.txt
