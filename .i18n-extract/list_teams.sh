#!/bin/bash
docker exec coolify php artisan tinker --execute='echo json_encode(DB::table("teams")->select("id","name")->get());' 2>&1 | tail -2
