#!/bin/bash
docker exec coolify php artisan tinker --execute='
echo "users: ";
foreach (DB::table("users")->get(["id","name","email"]) as $u) { echo $u->id."|".$u->name."|".$u->email." ; "; }
echo "\nteams: ";
foreach (DB::table("teams")->get(["id","name"]) as $t) { echo $t->id."|".$t->name." ; "; }
echo "\nprojects: ";
foreach (DB::table("projects")->get(["id","name"]) as $p) { echo $p->id."|".$p->name." ; "; }
echo "\napplications: ";
foreach (DB::table("applications")->get(["id","name"]) as $a) { echo $a->id."|".$a->name." ; "; }
echo "\nservers: ";
foreach (DB::table("servers")->get(["id","name"]) as $s) { echo $s->id."|".$s->name." ; "; }
echo "\n"
' 2>&1 | tail -15
