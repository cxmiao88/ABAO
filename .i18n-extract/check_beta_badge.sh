#!/bin/bash
echo "== references to beta-badge in container views =="
docker exec coolify sh -c 'grep -rn "beta-badge" /var/www/html/resources/views/ 2>/dev/null | head -20'
echo "== beta-badge component files on disk =="
docker exec coolify sh -c 'find /var/www/html/resources/views/components -iname "*beta*" 2>/dev/null; find /var/www/html/app -iname "*BetaBadge*" 2>/dev/null; echo done'
echo "== same checks on local repo =="
grep -rn "beta-badge" "G:\xianmu\juqing\baoUIIT\resources\views" 2>/dev/null | head -10
echo "== local component files =="
ls "G:\xianmu\juqing\baoUIIT\resources\views\components" 2>/dev/null | grep -i beta
ls "G:\xianmu\juqing\baoUIIT\app\View\Components" 2>/dev/null | grep -i beta
