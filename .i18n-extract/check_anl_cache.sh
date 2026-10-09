#!/bin/bash
# 以 preview 会话直接请求 analytics 页面，看服务端渲染的 KPI 文本
docker exec coolify sh -c "php artisan tinker --execute='echo App\Models\InstanceSettings::find(0)->id;'" >/dev/null 2>&1
# 用 curl 内部请求（无登录态会重定向），改直接查编译缓存：
docker exec coolify sh -c "ls -t /var/www/html/storage/framework/views/ | head -5"
echo ===
docker exec coolify sh -c "find /var/www/html/storage/framework/views -name '*.php' -newer /var/www/html/resources/views/livewire/analytics.blade.php | head -5"
echo ===
docker exec coolify sh -c "cat /var/www/html/storage/framework/views/*.php 2>/dev/null | grep -l 'anl_unique_visitors' | head -3"
echo done
