#!/bin/bash
docker exec coolify php artisan tinker --execute='DB::table("servers")->where("id",0)->update(["description"=>"这是运行 ABao 的服务器。请勿删除！"]); echo DB::table("servers")->where("id",0)->value("description");' 2>&1 | tail -2
