<?php
$base = $argv[1] ?? __DIR__;
foreach (['zh-cn', 'en'] as $l) {
    $f = "{$base}/lang/{$l}.json";
    if (! file_exists($f)) { echo "{$l}: FILE NOT FOUND {$f}\n"; continue; }
    $d = json_decode(file_get_contents($f), true);
    echo "{$l}: ".($d === null ? 'INVALID - '.json_last_error_msg() : 'OK ('.count($d)." keys)")."\n";
}
