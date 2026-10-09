#!/bin/bash
# 从容器内下载官方 dify 模板（容器网络可达）
docker exec coolify sh -c "curl -sL 'http://raw.githubusercontent.com/coollabsio/coolify/refs/tags/v4.0.0-beta.420.1/templates/compose/dify.yaml' -o /tmp/dify.yaml"
docker exec coolify wc -c /tmp/dify.yaml
docker cp coolify:/tmp/dify.yaml /mnt/g/xianmu/juqing/baoUIIT/.i18n-extract/compose_dify.yaml
echo DOWNLOAD_DONE
