#!/bin/bash
# 修复 navbar.blade.php 中的路由问题
docker cp /opt/coolify-src/resources/views/components/navbar.blade.php coolify:/var/www/html/resources/views/components/navbar.blade.php
docker exec coolify php artisan config:clear
docker exec coolify php artisan route:clear
echo "Navbar 修复完成"