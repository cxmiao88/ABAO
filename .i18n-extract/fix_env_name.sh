#!/bin/bash
docker exec coolify php artisan tinker --execute='DB::table("environments")->where("uuid","zinip5dughzprnlvzatwj80z")->update(["name"=>"生产环境"]); echo DB::table("environments")->where("uuid","zinip5dughzprnlvzatwj80z")->value("name");' 2>&1 | tail -2
