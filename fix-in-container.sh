#!/bin/bash
# 在容器内直接修复 navbar.blade.php

# 读取修复后的文件内容
sed -i 's/@if (auth()->user()?->isAdmin())/@if (auth()->user()?->isAdmin() \&\& Route::has('\''registries.index'\''))/' /var/www/html/resources/views/components/navbar.blade.php

# 清理缓存
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "修复完成"