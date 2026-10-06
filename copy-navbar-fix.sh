docker cp g:/xianmu/juqing/baoUIIT/resources/views/components/navbar.blade.php coolify:/var/www/html/resources/views/components/navbar.blade.php
docker exec coolify php artisan config:clear
docker exec coolify php artisan route:clear