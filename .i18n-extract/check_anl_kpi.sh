#!/bin/bash
docker exec coolify sh -c "sed -n '160,230p' /var/www/html/resources/views/livewire/analytics.blade.php" | grep -n "span\|anl_\|请求\|Requests\|REQUESTS" | head -20
echo ===
docker exec coolify sh -c "ls -la /var/www/html/resources/views/livewire/analytics.blade.php"
echo ===
docker exec coolify sh -c "cat /var/www/html/bootstrap/cache/*.php 2>/dev/null | grep -c analytics" 
