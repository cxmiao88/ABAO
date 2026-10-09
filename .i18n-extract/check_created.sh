#!/bin/bash
docker exec coolify php artisan tinker --execute='
$out = "apps: ";
foreach (DB::table("applications")->get(["id","name","environment_id","destination_id","build_pack"]) as $a) { $out .= json_encode($a)." ; "; }
$out .= "\nusers: ";
foreach (DB::table("users")->get(["id","name","email"]) as $u) { $out .= json_encode($u)." ; "; }
file_put_contents("/tmp/check.out", $out);
'
docker cp coolify:/tmp/check.out /mnt/g/xianmu/juqing/baoUIIT/.i18n-extract/check.out
cat /mnt/g/xianmu/juqing/baoUIIT/.i18n-extract/check.out
