#!/bin/bash
echo "container zh-cn anl keys:"
docker exec coolify sh -c "python3 -c \"
import json
d = json.load(open('/var/www/html/lang/zh-cn.json', encoding='utf-8'))
for k in ['anl_requests','anl_unique_visitors','anl_overview','anl_bandwidth']:
    print(k, '=', d.get(k))
\"" 2>&1 || docker exec coolify sh -c "grep -o '\"anl_requests\"[^,]*' /var/www/html/lang/zh-cn.json | head -2"
echo "local zh-cn anl keys:"
python3 -c "
import json
d = json.load(open('/mnt/g/xianmu/juqing/baoUIIT/lang/zh-cn.json', encoding='utf-8'))
for k in ['anl_requests','anl_unique_visitors','anl_overview','anl_bandwidth']:
    print(k, '=', d.get(k))
"
