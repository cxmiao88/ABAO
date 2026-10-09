#!/bin/bash
echo "== container lang json validity =="
docker exec coolify php -r '
foreach (["/var/www/html/lang/en.json", "/var/www/html/lang/zh-cn.json"] as $f) {
    $d = json_decode(file_get_contents($f), true);
    echo basename($f)." valid=".(json_last_error() === JSON_ERROR_NONE ? "yes" : "no")." keys=".count($d)."\n";
    if (strpos($f, "zh-cn") !== false) {
        echo "sample dep_history: ".$d["application.dep_history"]."\n";
        echo "sample adv_build_title: ".$d["application.adv_build_title"]."\n";
        echo "sample dns_matches: ".$d["application.dns_matches"]."\n";
    }
}
'
