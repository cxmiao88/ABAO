#!/bin/bash
# 同步 socialite helper（oauth_default_redirect_uri 等）到容器并验证
set -e
docker exec coolify sh -c 'ls /var/www/html/bootstrap/helpers/' > /tmp/coolify_helpers_list.txt 2>&1 || true
cat /tmp/coolify_helpers_list.txt
echo "---"
docker exec coolify sh -c 'test -f /var/www/html/bootstrap/helpers/socialite.php && echo EXISTS || echo MISSING'
