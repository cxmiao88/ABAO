#!/bin/bash
docker exec coolify php artisan tinker --execute='$out = ""; foreach (DB::table("servers")->get(["id","name","description"]) as $s) { $out .= json_encode($s)."\n"; } echo $out;' 2>&1 | tail -5
echo "== domains blade has DNS Entries? =="
grep -rn "DNS" /mnt/g/xianmu/juqing/baoUIIT/resources/views/livewire/project/application/domains.blade.php /mnt/g/xianmu/juqing/baoUIIT/resources/views/livewire/project/application/partials/domain-row.blade.php 2>/dev/null | head -10
echo "== container domains has DNS Entries? =="
docker exec coolify sh -c 'grep -rn "DNS Entr" /var/www/html/resources/views/livewire/project/application/ /var/www/html/resources/views/components/ 2>/dev/null | head -10' 2>/dev/null || echo none
