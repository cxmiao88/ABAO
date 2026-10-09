#!/bin/bash
echo "container analytics anl_requests count:"
docker exec coolify sh -c "grep -c 'anl_requests' /var/www/html/resources/views/livewire/analytics.blade.php"
echo "container zh-cn 请求数 count:"
docker exec coolify sh -c "grep -c '请求数' /var/www/html/lang/zh-cn.json"
echo "local file check:"
grep -c 'anl_requests' /mnt/g/xianmu/juqing/baoUIIT/resources/views/livewire/analytics.blade.php
