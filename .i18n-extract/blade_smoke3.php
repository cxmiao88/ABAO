<?php
// Blade syntax smoke test v3: distinguish real syntax errors from
// component-resolution errors that need the full container.
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

$ok = 0; $container = 0; $fail = 0;
foreach ($files as $f) {
    if (! file_exists($f)) { echo "MISSING $f\n"; $fail++; continue; }
    try {
        $compiler->compileString(file_get_contents($f));
        echo 'OK    '.basename(dirname($f)).'/'.basename($f)."\n";
        $ok++;
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        if (strpos($msg, 'not instantiable') !== false) {
            echo 'SKIP  '.basename($f)." (component needs container)\n";
            $container++;
        } else {
            echo 'FAIL  '.basename($f).' :: '.$msg."\n";
            $fail++;
        }
    }
}
echo "compiled=$ok container_expected=$container failed=$fail\n";
exit($fail === 0 ? 0 : 1);
