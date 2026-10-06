# Fix 500 error - sync navbar to container and clear cache
docker cp "g:/xianmu/juqing/baoUIIT/resources/views/components/navbar.blade.php" coolify:/var/www/html/resources/views/components/navbar.blade.php
docker exec coolify php artisan config:clear
docker exec coolify php artisan route:clear
docker exec coolify php artisan view:clear
Write-Host "500 error fix completed!"