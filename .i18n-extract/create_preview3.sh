#!/bin/bash
docker exec coolify php artisan tinker --execute='$a = new App\Models\Application; $a->name = "PreviewApp"; $a->environment_id = 1; $a->destination_type = "App\Models\StandaloneDocker"; $a->destination_id = 0; $a->build_pack = "nixpacks"; $a->git_repository = "https://example.com/preview.git"; $a->git_branch = "main"; $a->status = "exited"; $a->save(); echo "APP_ID=".$a->id;'
echo
