#!/bin/bash
echo "== create app =="
docker exec coolify php artisan tinker --execute='$a = new App\Models\Application; $a->name = "PreviewApp"; $a->environment_id = 1; $a->destination_type = "App\Models\StandaloneDocker"; $a->destination_id = 0; $a->build_pack = "nixpacks"; $a->status = "exited"; $a->save(); echo "APP_ID=".$a->id;'
echo
echo "== create user =="
docker exec coolify php artisan tinker --execute='$u = App\Models\User::where("email","preview@local.test")->first(); if(!$u){$u = new App\Models\User; $u->email = "preview@local.test"; $u->name = "Preview";} $u->password = bcrypt("Preview@2026!"); $u->save(); DB::table("team_user")->updateOrInsert(["team_id"=>0,"user_id"=>$u->id],["role"=>"owner"]); echo "USER_ID=".$u->id;'
echo
