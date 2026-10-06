<?php
require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
app()->setLocale('zh-cn');
echo __('nav.dashboard') . PHP_EOL;
echo __('nav.projects') . PHP_EOL;
app()->setLocale('en');
echo __('nav.dashboard') . PHP_EOL;
