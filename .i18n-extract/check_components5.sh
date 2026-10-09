#!/bin/bash
docker exec coolify sh -c 'ls /var/www/html/resources/views/components/ | head -30; echo ===; ls /var/www/html/resources/views/components/components 2>/dev/null | head -20; echo ===; ls /var/www/html/resources/views/components/deployment 2>/dev/null' 2>/dev/null | head -60
