#!/bin/bash
# Sync application-module i18n changes (blade views + lang files) into the coolify container.
# Follows the established docker cp + view:clear pattern.
set -e
cd /mnt/g/xianmu/juqing/baoUIIT

FILES=(
  "resources/views/livewire/project/application/advanced.blade.php"
  "resources/views/livewire/project/application/analytics-placeholder.blade.php"
  "resources/views/livewire/project/application/analytics.blade.php"
  "resources/views/livewire/project/application/backup/create.blade.php"
  "resources/views/livewire/project/application/backup/index.blade.php"
  "resources/views/livewire/project/application/deployment/index.blade.php"
  "resources/views/livewire/project/application/deployment/show.blade.php"
  "resources/views/livewire/project/application/domains.blade.php"
  "resources/views/livewire/project/application/general.blade.php"
  "resources/views/livewire/project/application/heading.blade.php"
  "resources/views/livewire/project/application/partials/domain-row.blade.php"
  "resources/views/livewire/project/application/preview-domains.blade.php"
  "resources/views/livewire/project/application/preview/form.blade.php"
  "resources/views/livewire/project/application/previews.blade.php"
  "resources/views/livewire/project/application/rollback.blade.php"
  "resources/views/livewire/project/application/server-status-badge.blade.php"
  "resources/views/livewire/project/application/source.blade.php"
  "resources/views/livewire/project/application/swarm.blade.php"
)

for f in "${FILES[@]}"; do
  docker cp "$f" "coolify:/var/www/html/$f"
  echo "synced $f"
done

docker cp lang/zh-cn.json coolify:/var/www/html/lang/zh-cn.json
docker cp lang/en.json coolify:/var/www/html/lang/en.json
echo "synced lang files"

docker exec coolify php artisan view:clear
echo "view:clear done"
