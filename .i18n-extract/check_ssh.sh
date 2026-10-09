#!/bin/bash
echo "--- test ssh host.docker.internal:22 from coolify container ---"
docker exec coolify sh -c "timeout 5 bash -c ':> /dev/tcp/host.docker.internal/22' && echo SSH_PORT_OPEN || echo SSH_PORT_CLOSED"
echo "--- test docker socket ---"
docker exec coolify sh -c "ls -la /var/run/docker.sock 2>/dev/null || echo no-docker-sock-in-container"
echo "--- test docker from host ---"
docker ps --format '{{.Names}}' | head -5
echo "--- check sshd on windows host ---"
netstat -ano | findstr ":22 " | findstr LISTENING | head -3
echo "--- done ---"
