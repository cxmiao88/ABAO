#!/bin/bash
docker exec coolify php artisan tinker --execute='echo json_encode(["cols" => Schema::getColumnListing("users"), "admin_locale" => optional(App\Models\User::find(0))->locale, "preview_locale" => optional(App\Models\User::where("email","preview@local.test")->first())->locale]);' > /tmp/loc.txt 2>&1
cat /tmp/loc.txt
