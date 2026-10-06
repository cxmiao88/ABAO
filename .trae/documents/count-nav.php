<?php
$d = json_decode(file_get_contents('/tmp/zh-cn-base.json'), true);
echo 'count=' . count($d) . PHP_EOL;
$found = 0;
foreach ($d as $k => $v) {
    if (str_starts_with($k, 'nav.')) {
        $found++;
    }
}
echo 'nav=' . $found . PHP_EOL;
