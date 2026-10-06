# 修复 500 错误 - 同步 navbar 到容器并清理缓存
docker cp "g:/xianmu/juqing/baoUIIT/resources/views/components/navbar.blade.php" coolify:/var/www/html/resources/views/components/navbar.blade.php
docker exec coolify php artisan config:clear
docker exec coolify php artisan route:clear
docker exec coolify php artisan view:clear
Write-Host "500 错误修复完成！"