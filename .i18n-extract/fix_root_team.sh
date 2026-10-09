#!/bin/bash
docker exec coolify php artisan tinker --execute='DB::table("teams")->where("id",0)->update(["name"=>"根团队"]); echo DB::table("teams")->where("id",0)->value("name");' 2>&1 | tail -2
