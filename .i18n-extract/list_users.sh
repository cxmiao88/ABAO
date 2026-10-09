#!/bin/bash
docker exec coolify php artisan tinker --execute='echo App\Models\User::all()->map(fn($u) => [$u->id, $u->email, $u->name])->toJson();' > /tmp/users.json 2>&1
cat /tmp/users.json
