#!/bin/bash
# 修复 500 错误

docker exec coolify sh -c "
  sed -i 's/@if (auth()->user()?->isAdmin())/@if (auth()->user()?->isAdmin() \&\& Route::has('\''registries.index'\''))/' /var/www/html/resources/views/components/navbar.blade.php
  php artisan config:clear
  php artisan route:clear
  php artisan view:clear
  echo 'Fix completed'
"