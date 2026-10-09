#!/bin/bash
echo "== HTTP status =="
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8000/
echo "== recent app log tail =="
docker logs coolify --tail 30 2>/dev/null | grep -iE 'error|exception|500' | tail -10 || echo "(no error lines)"
