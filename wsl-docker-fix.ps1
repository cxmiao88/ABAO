# 使用 WSL 调用 docker
$wslPath = "C:\Windows\System32\wsl.exe"
$dockerCmd = "docker exec coolify sh -c 'sed -i \"s/@if (auth()->user()?->isAdmin())/@if (auth()->user()?->isAdmin() \&\& Route::has('\''registries.index'\''))/\" /var/www/html/resources/views/components/navbar.blade.php && php artisan config:clear && php artisan route:clear && php artisan view:clear'"

& $wslPath -d Ubuntu -- $dockerCmd