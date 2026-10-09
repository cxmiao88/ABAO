#!/bin/bash
docker exec coolify php artisan tinker --execute='$u = App\Models\User::where("email","preview@local.test")->first(); $u->locale = "zh-cn"; $u->save(); echo "locale updated";' > /tmp/loc3.txt 2>&1
cat /tmp/loc3.txt
