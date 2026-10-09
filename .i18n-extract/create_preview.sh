#!/bin/bash
docker exec coolify php artisan tinker --execute='
$out = "";
try {
  $app = new App\Models\Application();
  $app->name = "预览测试应用";
  $app->environment_id = 1;
  $app->destination_type = "App\Models\StandaloneDocker";
  $app->destination_id = 0;
  $app->build_pack = "nixpacks";
  $app->status = "exited";
  $app->save();
  $out .= "app created: ".$app->id." ".$app->uuid."\n";

  $u = App\Models\User::where("email","preview@local.test")->first();
  if (!$u) { $u = new App\Models\User(); $u->email = "preview@local.test"; $u->name = "Preview"; }
  $u->password = bcrypt("Preview@2026!");
  $u->save();
  DB::table("team_user")->updateOrInsert(["team_id"=>0,"user_id"=>$u->id],["role"=>"owner"]);
  $out .= "user: ".$u->id." ".$u->email."\n";
} catch (Throwable $e) {
  $out .= "ERROR: ".$e->getMessage()."\n";
}
file_put_contents("/tmp/preview.out", $out);
'
docker cp coolify:/tmp/preview.out /mnt/g/xianmu/juqing/baoUIIT/.i18n-extract/preview.out
cat /mnt/g/xianmu/juqing/baoUIIT/.i18n-extract/preview.out
