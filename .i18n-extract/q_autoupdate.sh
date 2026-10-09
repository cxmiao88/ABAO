#!/bin/bash
docker exec coolify php artisan tinker --execute='$s = App\Models\InstanceSettings::where("id",0)->first(); echo "auto_update=".var_export($s->is_auto_update_enabled,true)."\n";'
