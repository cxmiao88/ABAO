<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $u = \App\Models\User::first();
    auth()->login($u);
    $html = app('livewire')->mount('server.show', ['server_uuid' => 'esgfxk1y6djklllph4nw2com'], 'show-key');
    echo 'LEN:' . strlen($html) . "\n";
    $pos = strpos($html, 'srv_st_unavailable');
    echo 'srv_st_unavailable: ' . ($pos !== false ? 'YES' : 'NO') . "\n";
    $pos2 = strpos($html, 'system-overview-partial');
    echo 'partial-ref: ' . ($pos2 !== false ? 'YES' : 'NO') . "\n";
    $pos3 = strpos($html, 'TEST-COMPONENT-RENDERED');
    echo 'test-comp: ' . ($pos3 !== false ? 'YES' : 'NO') . "\n";
    if ($pos !== false) {
        echo substr($html, max(0, $pos - 200), 400) . "\n";
    }
} catch (\Throwable $e) {
    echo 'ERR: ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n";
}
