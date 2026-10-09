#!/bin/bash
docker exec coolify php artisan tinker --execute='$u = App\Models\User::where("email","preview@local.test")->first(); $u->password = bcrypt("password123"); $u->save(); echo "ok";' > /tmp/pw.txt 2>&1
cat /tmp/pw.txt
