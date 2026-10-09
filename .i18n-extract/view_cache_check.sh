#!/bin/bash
docker exec coolify php artisan view:cache 2>&1 | tail -5
echo "exit=$?"
