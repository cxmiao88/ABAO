#!/bin/bash
echo "=== 5a43056 (旧,含 Unique visitors) 头部 ==="
docker exec coolify sh -c "head -3 /var/www/html/storage/framework/views/5a43056ec73c9fd23ef3b798fd9f48ad.php"
echo "=== b191bb5 (新,含 anl_unique) 头部 ==="
docker exec coolify sh -c "head -3 /var/www/html/storage/framework/views/b191bb53e74734f444deea5cb00a1ffd.php"
echo "=== 5a43056 里 KPI 附近 ==="
docker exec coolify sh -c "grep -n 'Unique visitors' /var/www/html/storage/framework/views/5a43056ec73c9fd23ef3b798fd9f48ad.php | head -2"
echo "=== 视图目录还有其他 analytics? ==="
docker exec coolify sh -c "find /var/www/html -name 'analytics*.blade.php' -not -path '*/node_modules/*' 2>/dev/null"
