<?php
// Lightweight blade syntax smoke test (no full Laravel bootstrap).
$files = [
    '/var/www/html/resources/views/livewire/project/application/advanced.blade.php',
    '/var/www/html/resources/views/livewire/project/application/analytics-placeholder.blade.php',
    '/var/www/html/resources/views/livewire/project/application/analytics.blade.php',
    '/var/www/html/resources/views/livewire/project/application/backup/create.blade.php',
    '/var/www/html/resources/views/livewire/project/application/backup/index.blade.php',
    '/var/www/html/resources/views/livewire/project/application/deployment/index.blade.php',
    '/var/www/html/resources/views/livewire/project/application/deployment/show.blade.php',
    '/var/www/html/resources/views/livewire/project/application/domains.blade.php',
    '/var/www/html/resources/views/livewire/project/application/general.blade.php',
    '/var/www/html/resources/views/livewire/project/application/heading.blade.php',
    '/var/www/html/resources/views/livewire/project/application/partials/domain-row.blade.php',
    '/var/www/html/resources/views/livewire/project/application/preview-domains.blade.php',
    '/var/www/html/resources/views/livewire/project/application/preview/form.blade.php',
    '/var/www/html/resources/views/livewire/project/application/previews.blade.php',
    '/var/www/html/resources/views/livewire/project/application/rollback.blade.php',
    '/var/www/html/resources/views/livewire/project/application/server-status-badge.blade.php',
    '/var/www/html/resources/views/livewire/project/application/source.blade.php',
    '/var/www/html/resources/views/livewire/project/application/swarm.blade.php',
];

require '/var/www/html/vendor/autoload.php';

$fs = new Illuminate\Filesystem\Filesystem;
$compiler = new Illuminate\View\Compilers\BladeCompiler($fs, '/tmp');

$ok = 0;
$fail = 0;
foreach ($files as $f) {
    if (! file_exists($f)) {
        echo "MISSING $f\n";
        $fail++;
        continue;
    }
    try {
        $compiler->compileString(file_get_contents($f));
        echo 'OK   '.basename(dirname($f)).'/'.basename($f)."\n";
        $ok++;
    } catch (Throwable $e) {
        echo 'FAIL '.basename($f).' :: '.$e->getMessage()."\n";
        $fail++;
    }
}
echo "compiled=$ok failed=$fail\n";
exit($fail === 0 ? 0 : 1);
