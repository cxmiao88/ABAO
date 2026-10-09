#!/bin/bash
curl -s -o /dev/null -w "POST /panel-api/servers -> %{http_code}\n" -X POST http://localhost:8000/panel-api/servers
curl -s -o /dev/null -w "POST /panel-api/applications -> %{http_code}\n" -X POST http://localhost:8000/panel-api/applications
curl -s -o /dev/null -w "POST /panel-api/databases -> %{http_code}\n" -X POST http://localhost:8000/panel-api/databases
curl -s -o /dev/null -w "GET /panel-api/health -> %{http_code}\n" http://localhost:8000/panel-api/health
