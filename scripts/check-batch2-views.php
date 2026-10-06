<?php
// Compile-only syntax check for translated Blade views, using the real app container.
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$compiler = $app->make('blade.compiler');

$files = [
    'resources/views/livewire/dashboard.blade.php',
    'resources/views/livewire/dashboard/active-deployments.blade.php',
    'resources/views/livewire/project/index.blade.php',
    'resources/views/livewire/project/show.blade.php',
];

$failed = false;
foreach ($files as $file) {
    $path = '/var/www/html/'.$file;
    if (! is_file($path)) {
        fwrite(STDERR, "MISSING: {$file}\n");
        $failed = true;
        continue;
    }
    try {
        $compiler->compileString((string) file_get_contents($path));
        echo "OK: {$file}\n";
    } catch (Throwable $e) {
        echo "FAIL: {$file} => {$e->getMessage()}\n";
        $failed = true;
    }
}

exit($failed ? 1 : 0);
