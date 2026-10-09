<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $u = \App\Models\User::first();
    auth()->login($u);
    echo 'user: ' . ($u->email ?? 'none') . "\n";
    $server = \App\Models\Server::find(0);
    echo 'server: ' . ($server->name ?? 'none') . "\n";
    $html = app('livewire')->mount('server.system-overview', ['server' => $server], 't-key');
    echo 'LEN:' . strlen($html) . "\n";
    echo substr($html, 0, 400) . "\n";
} catch (\Throwable $e) {
    echo 'ERR: ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n";
}
