#!/bin/bash
echo "== local code: user-level locale handling =="
grep -rn "setLocale\|->language\|ui_locale\|app_locale" "G:\xianmu\juqing\baoUIIT\app" 2>/dev/null | grep -iv "//" | head -20
echo "== middleware / livewire locale =="
grep -rn "locale" "G:\xianmu\juqing\baoUIIT\app\Http\Middleware" 2>/dev/null | head -10
grep -rn "locale" "G:\xianmu\juqing\baoUIIT\resources\views\livewire\user" 2>/dev/null | head -10
echo "== container user table schema (locale columns) =="
docker exec coolify sh -c 'php artisan tinker --execute="echo implode(\",\", array_keys(Schema::getColumnListing(\"users\")));"' 2>&1 | tail -2
echo "== current users locale values =="
docker exec coolify sh -c 'php artisan tinker --execute="foreach (DB::table(\"users\")->get([\"id\",\"email\",\"language\"]) as \$u) { echo \$u->id.\"|\".\$u->email.\"|\".(\$u->language ?? \"NULL\").\"\n\"; }"' 2>&1 | tail -6
