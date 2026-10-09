#!/bin/bash
set -e
echo "== direct compile test =="
docker exec coolify php -r '
require "/var/www/html/vendor/autoload.php";
$compiler = new Illuminate\View\Compilers\BladeCompiler(new Illuminate\Filesystem\Filesystem, "/tmp");
$compiler->compileString(file_get_contents("/var/www/html/resources/views/livewire/project/application/swarm.blade.php"));
echo "swarm OK\n";
'
echo "== smoke via file =="
docker cp /mnt/g/xianmu/juqing/baoUIIT/.i18n-extract/blade_smoke.php coolify:/var/www/html/blade_smoke.php
docker exec coolify php /var/www/html/blade_smoke.php
echo "exit=$?"
