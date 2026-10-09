#!/bin/bash
docker exec coolify php artisan tinker --execute='
echo "environments: ";
foreach (DB::table("environments")->get() as $e) { echo json_encode($e)." ; "; }
echo "\nstandalone_dockers: ";
foreach (DB::table("standalone_dockers")->get() as $d) { echo json_encode($d)." ; "; }
echo "\napplications columns: ";
echo implode(",", DB::getSchemaBuilder()->getColumnListing("applications"))."\n";
echo "team_user rows: ";
foreach (DB::table("team_user")->get() as $t) { echo json_encode($t)." ; "; }
echo "\n"
' 2>&1 | tail -12
