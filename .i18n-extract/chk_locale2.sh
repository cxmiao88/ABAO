#!/bin/bash
docker exec coolify php -r '
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cols = Illuminate\Support\Facades\Schema::getColumnListing("users");
echo "cols: ".implode(",", $cols)."\n";
foreach (App\Models\User::all() as $u) {
  echo $u->id." | ".$u->email." | locale=".($u->locale ?? "NULL")." | settings=".json_encode($u->settings ?? null)."\n";
}
' > /tmp/loc2.txt 2>&1
cat /tmp/loc2.txt
