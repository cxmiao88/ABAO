<?php
$zh = json_decode(file_get_contents('/var/www/html/lang/zh-cn.json'), true);
echo 'total=' . count($zh) . PHP_EOL;
foreach ($zh as $k => $v) {
    if (str_starts_with($k, 'nav.')) {
        echo $k . ' => ' . $v . PHP_EOL;
    }
}
