-- 修复 500 错误：同步 navbar.blade.php 到容器
-- 请在 WSL 终端中执行以下命令：

echo "开始修复 500 错误..."

# 方法1：直接使用 docker cp（如果可用）
docker cp g:/xianmu/juqing/baoUIIT/resources/views/components/navbar.blade.php coolify:/var/www/html/resources/views/components/navbar.blade.php

# 如果 docker cp 不可用，使用方法2：通过临时文件
# echo "docker cp 失败，尝试方法2..."
# echo 'FROM alpine:latest
# COPY resources/views/components/navbar.blade.php /tmp/navbar.blade.php
# CMD ["sh", "-c", "echo 'Navbar file ready for copying'"]' > Dockerfile.temp
# docker build -t temp-navbar-copy . -f Dockerfile.temp
# docker run --rm -v coolify-var-www-html:/target temp-navbar-copy
# docker rmi temp-navbar-copy

# 清理缓存
echo "清理 Laravel 缓存..."
docker exec coolify php artisan config:clear
docker exec coolify php artisan route:clear  
docker exec coolify php artisan view:clear

echo "500 错误修复完成！"