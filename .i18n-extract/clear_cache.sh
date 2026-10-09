#!/bin/bash
docker exec coolify php artisan cache:clear 2>&1 | tail -1
echo done
