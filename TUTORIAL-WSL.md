# Coolify 在 Windows (WSL2) 上的运行教程

> 本教程基于一次真实的排障与启动过程整理，涵盖从零搭建、日常启动，以及 Windows + WSL 环境特有的常见故障修复。

---

## 1. 项目简介

**Coolify** 是一个开源自托管 PaaS（Heroku/Vercel 的替代品），通过 SSH 管理服务器、应用、数据库和服务。

| 组件 | 说明 |
|---|---|
| 后端 | Laravel 12 + Livewire 3（PHP 8.4） |
| 前端 | Tailwind CSS v4 + Vite |
| 数据库 | PostgreSQL 15 |
| 缓存/队列 | Redis 7（Horizon 队列） |
| 实时通信 | Laravel Reverb（端口 6001）+ Node 终端服务（端口 6002） |

服务以三个 Docker 容器运行：

- `coolify` — 主应用（含 NGINX + PHP-FPM + Horizon + Reverb）
- `coolify-db` — PostgreSQL
- `coolify-redis` — Redis

---

## 2. 前置要求

- Windows 10/11，启用 WSL2
- Ubuntu 发行版（`wsl --install -d Ubuntu`）
- Ubuntu 内安装 Docker Engine + Docker Compose 插件
- `~/.wslconfig`（Windows 用户目录下）设置虚拟机保活：

```ini
[wsl2]
networkingMode=NAT
guiApplications=false
vmIdleTimeout=-1
```

> `vmIdleTimeout=-1` 很关键：默认 60 秒无会话 WSL 就会关机，Coolify 会被一起杀掉。

---

## 3. 从零搭建

### 3.1 安装 WSL2 + Ubuntu（管理员 PowerShell）

```powershell
wsl --install -d Ubuntu
```

重启后设置 Ubuntu 用户名密码。确认 `/etc/wsl.conf` 中启用了 systemd：

```ini
[boot]
systemd=true
```

修改后需 `wsl --shutdown` 重启 WSL 生效。

### 3.2 在 Ubuntu 中安装 Docker

```bash
# 官方脚本安装
curl -fsSL https://get.docker.com | sh

# 让 Docker 随 systemd 自启（关键！见故障排查 Q2）
sudo systemctl enable docker
```

验证：

```bash
docker --version && docker compose version
```

### 3.3 准备项目目录和 .env

项目位于 Windows 侧 `g:\xianmu\juqing\baoUIIT`，WSL 中路径为 `/mnt/g/xianmu/juqing/baoUIIT`。

复制 Windows 专用示例配置作为 `.env`：

```bash
cd /mnt/g/xianmu/juqing/baoUIIT
cp .env.windows-docker-desktop.example .env
```

`.env` 关键内容（示例已内置，可按需修改）：

```ini
IS_WINDOWS_DOCKER_DESKTOP=true
APP_ID=coolify-windows-docker-desktop
APP_NAME=Coolify
APP_KEY=base64:ssTlCmrIE/q7whnKMvT6DwURikg69COzGsAwFVROm80=
APP_PORT=8000
DB_USERNAME=coolify
DB_PASSWORD=coolify
REDIS_PASSWORD=coolify
PUSHER_APP_ID=coolify
PUSHER_APP_KEY=coolify
PUSHER_APP_SECRET=coolify
PUSHER_BACKEND_PORT=6001
```

> `APP_KEY` 决定数据加密。**换 Key 会导致已有数据库连接密码等加密数据无法解密**，已有数据的环境不要改。

### 3.4 启动服务栈

```bash
cd /mnt/g/xianmu/juqing/baoUIIT
docker compose up -d
```

首次启动会拉取镜像（约 1-2 GB），然后自动执行数据库迁移和种子。查看进度：

```bash
docker compose logs -f coolify
```

看到以下输出说明就绪：

```
✅ NGINX + PHP-FPM is running correctly.
   INFO  Nothing to migrate.
```

### 3.5 验证

```bash
docker ps --format 'table {{.Names}}\t{{.Status}}'
# 期望：coolify / coolify-db / coolify-redis 均为 Up (healthy)

curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8000/login
# 期望：200
```

浏览器访问：

| 服务 | 地址 |
|---|---|
| Coolify 界面 | http://localhost:8000 |
| WebSocket (Reverb) | ws://localhost:6001 |
| 浏览器终端 | localhost:6002 |

首次访问会进入 root 用户注册页，注册第一个账号即可成为实例管理员。

---

## 4. 日常操作速查

所有命令在 PowerShell 中执行（前缀 `wsl -d Ubuntu --`）或直接进入 Ubuntu 内执行。

```powershell
# 进入 Ubuntu
wsl -d Ubuntu

# 进入后：
docker ps                          # 查看容器状态
docker logs -f coolify             # 跟踪应用日志
docker compose logs -f coolify-db  # 数据库日志
docker compose restart coolify     # 重启应用
docker compose down                # 停止（数据保留在卷中）
docker compose up -d               # 再次启动

# 容器内执行 artisan 命令
docker exec coolify php artisan migrate
docker exec coolify php artisan config:cache
```

Windows 侧快捷方式（不用进 Ubuntu）：

```powershell
wsl -d Ubuntu -- docker logs --tail 50 coolify
wsl -d Ubuntu -- docker exec coolify php artisan queue:restart
```

完全关闭 WSL 虚拟机（会停止所有容器）：

```powershell
wsl --shutdown
```

---

## 5. 故障排查（实战记录）

### Q1: `wsl` 任何命令都报「灾难性故障 E_UNEXPECTED」

```
WSL 正在完成升级...
灾难性故障
错误代码： Wsl/CallMsi/Install/E_UNEXPECTED
```

**原因**：WSL 的 MSI 安装包处于"升级未完成"的损坏状态。

**修复**：管理员 PowerShell 中执行 MSI 修复（注意不要加 `/qn` 静默参数，会被策略拒绝返回 1625）：

```powershell
# 先从注册表找到产品码
Get-ItemProperty 'HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\*' |
  Where-Object { $_.DisplayName -like '*Subsystem for Linux*' } |
  Select-Object DisplayName, PSChildName

# 修复（替换为上面查到的产品码）
Start-Process msiexec -ArgumentList '/f','{14CEDBC6-042F-4AB4-B177-BAFE1C16BC7A}' -Wait
```

修复成功后 `wsl -l -v` 即可正常列出发行版。

### Q2: Docker 容器总是自动停止

**症状**：`wsl` 会话一结束，Docker 守护进程和容器全部消失。

**原因**：Docker 是手动 `service docker start` 启动的，绑定在该次会话上；WSL 发行版没有用 systemd 托管服务。

**修复**：

```bash
# 确认 /etc/wsl.conf 有 [boot] systemd=true 后：
sudo systemctl enable docker
```

然后 `wsl --shutdown` 重启 WSL。之后 systemd 自动拉起 Docker，容器随 `restart: always` 策略自动恢复。

### Q3: Windows 本机访问 localhost:8000 不通

- 确认 WSL 虚拟机在运行：`wsl -l -v` 应显示 `Running`
- WSL2 NAT 模式下 localhost 端口转发通常自动生效；如失效，重启 WSL：`wsl --shutdown` 后重新进入
- 检查端口映射：`docker ps` 中 coolify 应有 `0.0.0.0:8000->8080/tcp`

### Q4: 容器显示 `health: starting` 很久

首次启动需要执行迁移 + 种子 + Horizon 启动，等待 1-2 分钟。用日志确认进度：

```bash
docker logs coolify --tail 20
```

### Q5: 数据会丢吗？

不会。PostgreSQL 和 Redis 数据存在 Docker 卷 `coolify-db`、`coolify-redis` 中，`docker compose down` 不会删除卷。只有 `docker compose down -v` 才会删数据。

### Q6: Windows 侧 Docker Desktop 和 WSL 内 Docker 冲突吗？

不冲突，二者是独立的 Docker 守护进程。本项目用的是 **WSL Ubuntu 内的 Docker**。如果误启动了 Docker Desktop，可以退出它以节省内存。

---

## 6. 架构备注（开发参考）

- UI 层全部是 Livewire 组件（`app/Livewire/`），无传统 Blade 控制器
- 领域逻辑在 `app/Actions/`（lorisleiva/laravel-actions）与 `app/Services/`
- 队列任务在 `app/Jobs/`（Redis 队列 + Horizon 监控）
- REST API 在 `/api/v1/`，Sanctum 认证
- 设计规范见 `DESIGN.md`，前端开发详见 `DEVELOPMENT.md`
- 生产镜像内代码在容器里（`/var/www/html`），如需改源码调试，参考 `DEVELOPMENT.md` 的开发环境章节

---

*教程整理自 2026-10-07 的实际排障过程（WSL MSI 修复 → systemd 托管 Docker → 容器自动恢复）。*
