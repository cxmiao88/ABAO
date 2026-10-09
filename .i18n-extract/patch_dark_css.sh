#!/bin/bash
# Patch container built CSS so elements that stay light in dark mode become dark gray.
CSS=/var/www/html/public/build/assets/app-CLbX3K1W.css
PATCH='/* i18n-dark-patch: keep light cards gray in dark mode */
:where(.dark, .dark *) .bg-red-50 { background-color: color-mix(in oklab, var(--color-red-500) 8%, var(--color-raised, #171717)) !important; }
:where(.dark, .dark *) .bg-red-100 { background-color: color-mix(in oklab, var(--color-red-500) 12%, var(--color-raised, #171717)) !important; }'

docker cp coolify:$CSS /tmp/app.css.bak 2>/dev/null || true
docker exec coolify sh -c "cp $CSS ${CSS}.bak-20261008"
if docker exec coolify sh -c "grep -c i18n-dark-patch $CSS" | grep -q 1; then
  echo "already patched"
else
  docker exec coolify sh -c "printf '\n%s\n' '$PATCH' >> $CSS"
  echo "patched"
fi
docker exec coolify sh -c "grep -c bg-red-50 $CSS"
