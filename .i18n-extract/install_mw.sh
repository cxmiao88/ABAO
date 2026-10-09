#!/bin/bash
set -x
mkdir -p /www/server
if [ ! -d /www/server/mdserver-web ]; then
  cp -r /mnt/g/xianmu/juqing/mdserver-web /www/server/mdserver-web
  rm -rf /www/server/mdserver-web/.git
fi
cd /www/server/mdserver-web
bash scripts/install/ubuntu.sh
echo "INSTALL_DONE"
