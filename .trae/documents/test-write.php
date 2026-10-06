<?php
$f = '/var/www/html/lang/zh-cn.json';
$d = json_decode(file_get_contents($f), true);
$d['nav.test'] = '测试';
file_put_contents($f, json_encode($d, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
clearstatcache();
echo 'size=' . filesize($f) . PHP_EOL;
$d2 = json_decode(file_get_contents($f), true);
echo isset($d2['nav.test']) ? 'WRITE_OK' : 'WRITE_FAIL';
echo PHP_EOL;
