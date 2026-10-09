#!/bin/bash
echo "== bootstrap cache files =="
docker exec coolify sh -c 'ls -la /var/www/html/bootstrap/cache/ 2>/dev/null'
echo "== config/app.php locale (bind-mounted override) =="
docker exec coolify sh -c 'grep -E "locale|fallback" /var/www/html/config/app.php'
echo "== try tinker config read =="
docker exec coolify php artisan tinker --execute='echo "locale=".config("app.locale");' 2>&1 | tail -3 || echo "tinker failed"
echo "== try trans via tinker =="
docker exec coolify php artisan tinker --execute='echo trans("application.swarm_configuration");' 2>&1 | tail -3 || echo "trans tinker failed"
