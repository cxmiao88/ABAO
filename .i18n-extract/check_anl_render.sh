#!/bin/bash
echo "=== container Analytics.php render ==="
docker exec coolify sh -c "grep -n 'return view' /var/www/html/app/Livewire/Analytics.php | tail -3"
echo "=== container view files ==="
docker exec coolify sh -c "ls -la /var/www/html/resources/views/livewire/analytics*.blade.php"
echo "=== container compiled views containing 'Requests' ==="
docker exec coolify sh -c "grep -rl 'Unique visitors' /var/www/html/storage/framework/views/ 2>/dev/null | head -3"
echo "=== container compiled views containing anl_unique ==="
docker exec coolify sh -c "grep -rl 'anl_unique_visitors' /var/www/html/storage/framework/views/ 2>/dev/null | head -3"
