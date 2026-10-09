<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
    $out = view('livewire.server.system-overview-partial', ['stats' => null])->render();
    echo 'LEN:' . strlen($out) . "\n";
    echo substr($out, 0, 300) . "\n";
} catch (\Throwable $e) {
    echo 'ERR: ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n";
}
