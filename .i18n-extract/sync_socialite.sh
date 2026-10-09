#!/bin/bash
set -e
docker cp /mnt/g/xianmu/juqing/baoUIIT/bootstrap/helpers/socialite.php coolify:/var/www/html/bootstrap/helpers/socialite.php
echo "copied socialite.php"
docker exec coolify sh -c 'grep -c "function oauth_default_redirect_uri" /var/www/html/bootstrap/helpers/socialite.php || echo 0' > /tmp/grep_out.txt 2>&1 || true
cat /tmp/grep_out.txt
