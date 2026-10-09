#!/bin/bash
docker exec coolify php artisan tinker --execute='
$out = "";
$out .= "environments: ";
foreach (DB::table("environments")->get() as $e) { $out .= json_encode($e)." ; "; }
$out .= "\nstandalone_dockers: ";
foreach (DB::table("standalone_dockers")->get() as $d) { $out .= json_encode($d)." ; "; }
$out .= "\napplications columns: ".implode(",", DB::getSchemaBuilder()->getColumnListing("applications"))."\n";
$out .= "team_user rows: ";
foreach (DB::table("team_user")->get() as $t) { $out .= json_encode($t)." ; "; }
file_put_contents("/tmp/schema.out", $out);
'
docker cp coolify:/tmp/schema.out /mnt/g/xianmu/juqing/baoUIIT/.i18n-extract/schema.out
cat /mnt/g/xianmu/juqing/baoUIIT/.i18n-extract/schema.out
