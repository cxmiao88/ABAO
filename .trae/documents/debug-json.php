<?php
$f = '/tmp/zh-cn-current.json';
echo 'exists=' . file_exists($f) . PHP_EOL;
echo 'size=' . filesize($f) . PHP_EOL;
$raw = file_get_contents($f);
echo 'first100=' . substr($raw, 0, 100) . PHP_EOL;
$d = json_decode($raw, true);
echo 'decoded=' . (is_array($d) ? count($d) : 'NULL') . PHP_EOL;
echo 'error=' . json_last_error_msg() . PHP_EOL;
