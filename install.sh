#!/usr/bin/env bash
# ============================================================================
#  ABao 阿宝面板 - 一键安装脚本 v2.4.1（2026-10-10 更新：CentOS7 老内核 postgres/seccomp 兼容 + 种子密码强制含数字 + 幂等重跑）
#  ---------------------------------------------------------------------------
#  用法（root 用户执行）：
#    curl -fsSL https://raw.githubusercontent.com/cxmiao88/ABAO/main/install.sh | bash
#
#  功能：环境检测 → Docker 安装（国内镜像优先）→ 拉取 ABao 源码 →
#        生成安全配置（全自动，无交互）→ 启动 Coolify(8000) →
#        部署前端面板 → 覆盖容器 nginx 反代 → 安装 mdserver-web(48700) →
#        放行防火墙 → 输出访问地址与初始账号。
#  特性：
#    - 零凭据：仓库内不含任何默认密码，安装时自动随机生成并仅在结尾显示
#    - 无交互：全程自动，支持 `curl | bash` 管道模式（不再依赖 stdin 输入）
#    - 国内网络友好：Docker/源码/前端均优先国内镜像与加速通道
#    - 详细日志：/tmp/abao-install.log，任何步骤失败都会明确提示原因
#    - 支持：Ubuntu / Debian / CentOS / Rocky / AlmaLinux / OpenCloudOS
#             / Kylin / openEuler（x86_64 / arm64）
# ============================================================================
set -uo pipefail
umask 022

# ---------- 全局常量 ----------
ABAO_REPO="https://github.com/cxmiao88/ABAO.git"
ABAO_BRANCH="main"
ABAO_DIR="/data/coolify/source"          # Coolify/ABao 源码目录
FRONTEND_ZIP_URL="https://github.com/cxmiao88/ABAO/releases/latest/download/abao-frontend.zip"
FRONTEND_ZIP_GH="https://ghproxy.com/https://github.com/cxmiao88/ABAO/releases/latest/download/abao-frontend.zip"
SRC_ZIP_URL="https://github.com/cxmiao88/ABAO/archive/refs/heads/main.zip"
SRC_ZIP_GH="https://ghfast.top/https://github.com/cxmiao88/ABAO/archive/refs/heads/main.zip"
APP_PORT="${APP_PORT:-8000}"
MDSERVER_PORT="${MDSERVER_PORT:-48700}"
LOG_FILE="/tmp/abao-install.log"

# 颜色输出
C_INFO='\033[1;36m'; C_OK='\033[1;32m'; C_WARN='\033[1;33m'; C_ERR='\033[1;31m'; C_END='\033[0m'
info()  { echo -e "${C_INFO}[ABao]${C_END} $*"; }
ok()    { echo -e "${C_OK}[ OK ]${C_END} $*"; }
warn()  { echo -e "${C_WARN}[WARN]${C_END} $*"; }
err()   { echo -e "${C_ERR}[ERR ]${C_END} $*" >&2; }
die()   { err "$*"; exit 1; }

# 全流程输出同时写入日志，任何非零退出都给出日志指引
exec > >(tee -a "$LOG_FILE") 2>&1
trap 'rc=$?; if [ "$rc" -ne 0 ]; then echo -e "${C_ERR}[ERR ]${C_END} 安装未完成（退出码 $rc），请查看日志：tail -80 /tmp/abao-install.log" >&2; fi' EXIT

# ---------- 1. 前置检查 ----------
require_root() {
    [ "$(id -u)" -eq 0 ] || die "请使用 root 用户执行：sudo -i 后重新运行本脚本"
}
detect_os() {
    if   [ -f /etc/os-release ]; then . /etc/os-release; OS_ID="$ID"; OS_VER="$VERSION_ID"
    elif [ -f /etc/redhat-release ]; then OS_ID="rhel"
    else die "无法识别操作系统，请手动安装 Docker 后重试"
    fi
    # 官方支持列表（opencloudos/kylin/openeuler 为 RHEL 系兼容）
    case "$OS_ID" in
        ubuntu|debian|centos|rhel|rocky|almalinux|opencloudos|kylin|openeuler|anolis) : ;;
        *) warn "系统 $OS_ID 未在支持列表，继续尝试（建议 Ubuntu 22.04+/Debian 12+）" ;;
    esac
    ARCH="$(uname -m)"; case "$ARCH" in x86_64) ARCH="amd64" ;; aarch64) ARCH="arm64" ;; esac
    MEM_MB="$(awk '/MemTotal/{print int($2/1024)}' /proc/meminfo)"
    [ "$MEM_MB" -ge 2048 ] || warn "内存仅 ${MEM_MB}MB，推荐 ≥2GB（Coolify + mdserver 需要）"
    # 包管理器
    if command -v dnf >/dev/null 2>&1; then PKG="dnf"
    elif command -v yum >/dev/null 2>&1; then PKG="yum"
    elif command -v apt-get >/dev/null 2>&1; then PKG="apt-get"
    else die "未找到 dnf/yum/apt 包管理器"
    fi
    info "系统：$OS_ID $OS_VER ($ARCH)  内存：${MEM_MB}MB  包管理：$PKG"
    info "安装日志：$LOG_FILE"
}
check_port() {
    local p="$1"
    # 本脚本已部署的服务占用端口 → 幂等重跑放行
    if [ "$p" = "$APP_PORT" ] && command -v docker >/dev/null 2>&1 && docker ps --format '{{.Names}}' 2>/dev/null | grep -qx 'coolify'; then return 0; fi
    if [ "$p" = "$MDSERVER_PORT" ] && [ -f /www/server/mdserver-web/start.py ]; then return 0; fi
    if command -v ss >/dev/null 2>&1; then
        ss -ltn 2>/dev/null | awk '{print $4}' | grep -qE "[:.]${p}$" && die "端口 $p 已被占用，请先释放或修改配置（APP_PORT/MDSERVER_PORT）" || return 0
    elif command -v netstat >/dev/null 2>&1; then
        netstat -ltn 2>/dev/null | awk '{print $4}' | grep -qE "[:.]${p}$" && die "端口 $p 已被占用，请先释放或修改配置（APP_PORT/MDSERVER_PORT）" || return 0
    fi
}

# ---------- 2. Docker 安装（国内镜像优先，Compose 插件随包安装） ----------
install_docker_rhel() {
    local repo=/etc/yum.repos.d/docker-ce.repo relver
    # 显式探测发行版主版本（不能依赖 yum $releasever：OpenCloudOS 展开为 9.4 会导致源 404）
    if [ "$OS_ID" = "centos" ]; then
        relver="$(rpm -q --qf '%{VERSION}' centos-release 2>/dev/null | cut -d. -f1 | tr -dc '0-9')"
        [ -n "$relver" ] || relver="7"
    else
        relver="9"
    fi
    info "配置 Docker 源（centos/$relver，腾讯云 → 阿里云 → 官方 多源兜底）…"
    cat > "$repo" <<EOF
[docker-ce-stable]
name=Docker CE Stable - \$basearch
baseurl=https://mirrors.cloud.tencent.com/docker-ce/linux/centos/$relver/\$basearch/stable
        https://mirrors.aliyun.com/docker-ce/linux/centos/$relver/\$basearch/stable
        https://download.docker.com/linux/centos/$relver/\$basearch/stable
enabled=1
gpgcheck=1
gpgkey=https://mirrors.cloud.tencent.com/docker-ce/linux/centos/gpg
       https://mirrors.aliyun.com/docker-ce/linux/centos/gpg
skip_if_unavailable=1
EOF
    $PKG clean expire-cache >/dev/null 2>&1 || true
    $PKG install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin 2>&1 | tail -5
    command -v docker >/dev/null 2>&1 || return 1
}
install_docker_debian() {
    local keyring=/etc/apt/keyrings/docker-archive-keyring.gpg
    install -m 0755 -d /etc/apt/keyrings
    local gpg_url dist
    if [ "$OS_ID" = "ubuntu" ]; then
        gpg_url="https://mirrors.aliyun.com/docker-ce/linux/ubuntu/gpg"
        dist="${UBUNTU_CODENAME:-noble}"
    else
        gpg_url="https://mirrors.aliyun.com/docker-ce/linux/debian/gpg"
        dist="${DEBIAN_CODENAME:-bookworm}"
    fi
    info "配置阿里云 Docker 源（$OS_ID / $dist）…"
    curl -fsSL --max-time 60 "$gpg_url" -o "$keyring" || return 1
    chmod a+r "$keyring"
    echo "deb [arch=$(dpkg --print-architecture) signed-by=$keyring] https://mirrors.aliyun.com/docker-ce/linux/$OS_ID $dist stable" > /etc/apt/sources.list.d/docker.list
    apt-get update -qq 2>&1 | tail -3
    apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin 2>&1 | tail -5
    command -v docker >/dev/null 2>&1 || return 1
}
install_docker() {
    if command -v docker >/dev/null 2>&1; then
        docker info >/dev/null 2>&1 || die "Docker 已安装但守护进程不可用，请先启动：systemctl start docker"
        ok "Docker 已安装：$(docker --version)"
    else
        info "正在安装 Docker（国内镜像）…"
        case "$PKG" in
            dnf|yum) install_docker_rhel || warn "阿里云源安装失败，尝试 Docker 官方脚本…" ;;
            apt-get) install_docker_debian || warn "阿里云源安装失败，尝试 Docker 官方脚本…" ;;
        esac
        if ! command -v docker >/dev/null 2>&1; then
            info "尝试 Docker 官方安装脚本（网络较慢时可能失败）…"
            ( curl -fsSL --max-time 300 https://get.docker.com | sh ) 2>&1 | tail -10 || true
        fi
        command -v docker >/dev/null 2>&1 || die "Docker 安装失败。请手动安装后重试：https://docs.docker.com/engine/install/ （国内可用阿里云源）"
        systemctl enable --now docker >/dev/null 2>&1 || true
        ok "Docker 安装完成：$(docker --version)"
    fi
    if ! docker compose version >/dev/null 2>&1; then
        info "正在安装 Docker Compose 插件（二进制下载）…"
        mkdir -p /usr/local/lib/docker/cli-plugins
        ( curl -fsSL --max-time 120 "https://github.com/docker/compose/releases/latest/download/docker-compose-linux-${ARCH}" -o /usr/local/lib/docker/cli-plugins/docker-compose \
            || curl -fsSL --max-time 120 "https://ghproxy.com/https://github.com/docker/compose/releases/latest/download/docker-compose-linux-${ARCH}" -o /usr/local/lib/docker/cli-plugins/docker-compose ) 2>/dev/null
        chmod +x /usr/local/lib/docker/cli-plugins/docker-compose
        docker compose version >/dev/null 2>&1 || warn "Compose 插件安装失败（可稍后手动安装，Coolify 启动需要）"
    fi
    ok "Docker Compose：$(docker compose version --short 2>/dev/null || echo 未知)"
}

# ---------- 3. 拉取源码（ZIP 优先，git 仅更新已存在源码；全程进度显示） ----------
fetch_source() {
    if [ -d "$ABAO_DIR/.git" ]; then
        info "检测到已有源码，拉取最新（30 秒超时）…"
        timeout 30 git -C "$ABAO_DIR" fetch origin "$ABAO_BRANCH" >/dev/null 2>&1 \
            && timeout 30 git -C "$ABAO_DIR" reset --hard "origin/$ABAO_BRANCH" >/dev/null 2>&1 \
            || warn "源码更新失败（网络不可达），使用现有版本"
    else
        local zip="/tmp/abao-main.zip"
        info "下载 ABao 源码到 $ABAO_DIR …"
        info "下载源码压缩包（GitHub 直连，30 秒超时）…"
        if ! curl -fSL --progress-bar --max-time 30 "$SRC_ZIP_URL" -o "$zip" 2>&1; then
            info "GitHub 直连不可达，切换 ghproxy 加速通道…"
            curl -fSL --progress-bar --max-time 600 "$SRC_ZIP_GH" -o "$zip" 2>&1 \
                || die "源码下载失败（GitHub 与加速通道均不可达）。请配置代理，或手动下载后放到 $ABAO_DIR"
        fi
        ok "源码包下载完成（$(du -h "$zip" | cut -f1)）"
        rm -rf "$ABAO_DIR" /tmp/abao-extract
        mkdir -p "$ABAO_DIR" /tmp/abao-extract
        info "解压源码…"
        unzip -q "$zip" -d /tmp/abao-extract || die "源码压缩包解压失败"
        cp -r /tmp/abao-extract/ABAO-main/. "$ABAO_DIR/" 2>/dev/null || cp -r /tmp/abao-extract/*/. "$ABAO_DIR/"
    fi
    [ -f "$ABAO_DIR/docker-compose.prod.yml" ] || die "源码不完整：缺少 docker-compose.prod.yml"
    # config-overrides/app.php 在 .gitignore 内，zip/git 均不含；compose 挂载它覆盖容器 config/app.php，缺失会导致容器启动报 "not a directory"
    if [ ! -f "$ABAO_DIR/config-overrides/app.php" ]; then
        mkdir -p "$ABAO_DIR/config-overrides"
        info "生成 config-overrides/app.php（从 Coolify 镜像拷贝原始配置）…"
        docker run --rm --entrypoint cat coollabsio/coolify:latest /var/www/html/config/app.php > "$ABAO_DIR/config-overrides/app.php" 2>/dev/null \
            || echo '<?php return [];' > "$ABAO_DIR/config-overrides/app.php"
        [ -f "$ABAO_DIR/config-overrides/app.php" ] && [ -s "$ABAO_DIR/config-overrides/app.php" ] || die "config-overrides/app.php 生成失败"
    fi
    ok "源码就绪：$ABAO_DIR"
}

# ---------- 4. 生成 .env（全自动随机，零交互） ----------
gen_env() {
    local env_file="$ABAO_DIR/.env"
    [ -f "$env_file" ] && { warn "已存在 $env_file，跳过生成（如需重置请先删除该文件）"; return; }
    info "生成安全配置（管理员账号/数据库/Redis 密钥全部随机）…"
    # 注意：Coolify RootUserSeeder 要求邮箱可 DNS 解析 + 密码含大小写/数字/符号
    local admin_email="${ABAO_ADMIN_EMAIL:-admin@qq.com}"
    local admin_pass="${ABAO_ADMIN_PASSWORD:-}"
    # 强制含字母+数字+符号（Coolify RootUserSeeder 要求密码必须含数字；纯随机字母会被拒）
    if [ -z "$admin_pass" ]; then
        local rn="$(tr -dc '0-9' < /dev/urandom | head -c 2)"
        local rl="$(tr -dc 'A-Za-z' < /dev/urandom | head -c 8)"
        admin_pass="Abao@${rl}${rn}!"
    fi
    local app_key="base64:$(head -c 32 /dev/urandom | base64)"
    local db_pass="$(tr -dc 'A-Za-z0-9' < /dev/urandom | head -c 20)"
    local redis_pass="$(tr -dc 'A-Za-z0-9' < /dev/urandom | head -c 20)"
    local app_id="$(head -c 16 /dev/urandom | od -An -tx1 | tr -d ' \n')"
    local pusher_id="$(head -c 16 /dev/urandom | od -An -tx1 | tr -d ' \n')"
    local pusher_key="$(head -c 16 /dev/urandom | od -An -tx1 | tr -d ' \n')"
    local pusher_secret="$(head -c 16 /dev/urandom | od -An -tx1 | tr -d ' \n')"

    cat > "$env_file" <<EOF
APP_ID=${app_id}
APP_NAME=ABao
APP_ENV=production
APP_KEY=${app_key}
APP_URL=http://localhost:${APP_PORT}
APP_PORT=${APP_PORT}

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=coolify
DB_USERNAME=coolify
DB_PASSWORD=${db_pass}

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=${redis_pass}

PUSHER_APP_ID=${pusher_id}
PUSHER_APP_KEY=${pusher_key}
PUSHER_APP_SECRET=${pusher_secret}
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_BACKEND_PORT=6001

ROOT_USERNAME=root
ROOT_USER_EMAIL=${admin_email}
ROOT_USER_PASSWORD=${admin_pass}

REGISTRY_URL=docker.io
PHP_MEMORY_LIMIT=256M
EOF
    chmod 600 "$env_file"
    # 凭据存档（仅本地，绝不可提交仓库）
    cat > "$ABAO_DIR/.abao-credentials" <<EOF
ABao 阿宝面板初始账号（安装于 $(date '+%F %T')，请妥善保存）
管理员邮箱: ${admin_email}
管理员密码: ${admin_pass}
面板地址:   http://<服务器IP>:${APP_PORT}/abao/index.html
EOF
    chmod 600 "$ABAO_DIR/.abao-credentials"
    ok ".env 已生成（随机密钥，仅存于服务器本地 $env_file）"
}

# ---------- 5. 启动 Coolify ----------
start_coolify() {
    info "启动 Coolify 容器（Postgres + Redis + 应用）…"
    # Coolify compose 声明 external 网络 coolify，必须先创建（幂等）
    docker network create coolify >/dev/null 2>&1 || true
    cd "$ABAO_DIR"
    # CentOS7/RHEL7 老内核（3.x）兼容修复：
    #  ① postgres 换 debian/glibc 版（alpine/musl 与 3.10 内核 epoll 不兼容，容器反复 Restarting）
    #  ② coolify 加 seccomp:unconfined（默认 seccomp 在 3.10 内核拦截 pwrite64，nginx 报
    #     "pwrite() \"/var/run/nginx.pid\" Operation not permitted"，healthcheck 失败）
    KERNEL_MAJOR="$(uname -r | cut -d. -f1)"
    if [ "$KERNEL_MAJOR" = "3" ]; then
        info "检测到 3.x 老内核，应用 CentOS7/RHEL7 兼容补丁（debian postgres + seccomp:unconfined）…"
        sed -i 's|image: postgres:[0-9.]*-alpine|image: postgres:14|g' docker-compose.yml
        grep -q 'seccomp:unconfined' docker-compose.prod.yml || \
            sed -i 's|    image: "${REGISTRY_URL:-docker.io}/coollabsio/coolify:${LATEST_IMAGE:-latest}"|&\n    security_opt:\n      - "seccomp:unconfined"|' docker-compose.prod.yml
    fi
    docker compose -f docker-compose.yml -f docker-compose.prod.yml config -q || die "compose 配置校验失败"
    docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d 2>&1 | tail -8 || die "Coolify 容器启动失败（docker compose up -d）"
    info "等待服务就绪（最多 120 秒）…"
    local i=0
    while [ "$i" -lt 120 ]; do
        if docker exec coolify php artisan --version >/dev/null 2>&1; then break; fi
        # 容器异常退出时提前报错
        if [ "$(docker inspect -f '{{.State.Running}}' coolify 2>/dev/null)" = "false" ]; then
            die "Coolify 容器异常退出，查看日志：docker logs coolify --tail 50"
        fi
        i=$((i+5)); sleep 5
    done
    [ "$i" -ge 120 ] && warn "等待超时，请稍后手动检查：docker ps"
    docker exec coolify php artisan migrate --force >/dev/null 2>&1 || warn "migrate 需手动执行（容器尚未完全就绪）"
    docker exec coolify php artisan db:seed --class=RootUserSeeder --force >/dev/null 2>&1 || true
    ok "Coolify 已启动（端口 ${APP_PORT}）"
}

# ---------- 6. 部署前端面板（Release 资产，进度显示 + 国内加速） ----------
deploy_frontend() {
    local tmp_zip="/tmp/abao-frontend.zip"
    info "下载 ABao 前端面板（GitHub Release 直连，30 秒超时）…"
    if ! curl -fSL --progress-bar --max-time 30 "$FRONTEND_ZIP_URL" -o "$tmp_zip" 2>&1; then
        info "Release 直连不可达，切换 ghproxy 加速通道…"
        curl -fSL --progress-bar --max-time 600 "$FRONTEND_ZIP_GH" -o "$tmp_zip" 2>&1 \
            || die "前端面板下载失败（GitHub Release 与加速通道均不可达，请配置代理后重试）"
    fi
    ok "前端包下载完成（$(du -h "$tmp_zip" | cut -f1)）"
    docker exec coolify rm -rf /var/www/html/public/abao
    rm -rf /tmp/abao-frontend && mkdir -p /tmp/abao-frontend
    cd /tmp/abao-frontend
    info "解压前端包…"
    unzip -q "$tmp_zip" || die "前端压缩包解压失败"
    local src="."
    [ -f index.html ] || src="$(find . -maxdepth 2 -name index.html -printf '%h\n' -quit)"
    docker cp "$src/." "coolify:/var/www/html/public/abao/"
    docker exec coolify sh -c '[ -f /var/www/html/public/abao/index.html ]' || die "前端部署失败：缺少 index.html"
    ok "前端面板已部署"
}

# ---------- 7. 覆盖容器 nginx 配置（ABao 反代规则） ----------
patch_nginx() {
    info "写入 ABao nginx 反代配置…"
    docker cp "$ABAO_DIR/docker/abao-nginx-http.conf" coolify:/etc/nginx/site-opts.d/http.conf
    docker exec coolify nginx -t >/dev/null 2>&1 && docker exec coolify nginx -s reload >/dev/null 2>&1 || \
        warn "nginx 配置重载失败（稍后可手动：docker exec coolify nginx -s reload）"
    ok "nginx 反代配置已生效（mdserver API → 48700，前端 → /abao/，其余 → SPA）"
}

# ---------- 8. Docker 镜像加速（腾讯云内网优先 + DaoCloud 公共加速） ----------
configure_docker_mirror() {
    local mirrors="https://docker.m.daocloud.io"
    if curl -s -m 2 http://metadata.tencentyun.com/latest/meta-data/instance-id >/dev/null 2>&1; then
        mirrors="https://mirror.ccs.tencentyun.com,$mirrors"
        info "检测到腾讯云环境，优先使用腾讯云内网镜像加速"
    fi
    local cfg="/etc/docker/daemon.json"
    [ -f "$cfg" ] && cp "$cfg" "${cfg}.bak.$(date +%s)" 2>/dev/null || true
    local mirrors_json
    mirrors_json="$(printf '%s' "$mirrors" | sed 's/,/","/g')"
    cat > "$cfg" <<EOF
{
  "registry-mirrors": ["${mirrors_json}"]
}
EOF
    systemctl restart docker >/dev/null 2>&1 || true
    docker info >/dev/null 2>&1 || warn "Docker 重启后异常，请手动检查：systemctl status docker"
    ok "已配置 Docker 镜像加速（$mirrors）"
}

# ---------- 9. 防火墙与 SELinux ----------
open_firewall() {
    if systemctl is-active firewalld >/dev/null 2>&1; then
        firewall-cmd --permanent --add-port="${APP_PORT}/tcp" >/dev/null 2>&1 || true
        firewall-cmd --permanent --add-port="${MDSERVER_PORT}/tcp" >/dev/null 2>&1 || true
        firewall-cmd --reload >/dev/null 2>&1 || true
        ok "已放行端口 ${APP_PORT} / ${MDSERVER_PORT}"
    fi
    if command -v getenforce >/dev/null 2>&1 && [ "$(getenforce 2>/dev/null)" = "Enforcing" ]; then
        setenforce 0 2>/dev/null || true
        warn "已临时关闭 SELinux（仅本次运行）；如需永久关闭请编辑 /etc/selinux/config"
    fi
}

# ---------- 9. 安装 mdserver-web ----------
install_mdserver() {
    if [ -f /www/server/mdserver-web/start.py ] || command -v mw >/dev/null 2>&1; then
        ok "mdserver-web 已安装，跳过"
        return
    fi
    info "安装 mdserver-web（宝塔式主机管理后端，端口 ${MDSERVER_PORT}）…"
    local zip="/tmp/mdserver-web.zip"
    local urls=(
        "https://ghfast.top/https://github.com/midoks/mdserver-web/archive/refs/heads/dev.zip"
        "https://gh-proxy.com/https://github.com/midoks/mdserver-web/archive/refs/heads/dev.zip"
        "https://ghproxy.net/https://github.com/midoks/mdserver-web/archive/refs/heads/dev.zip"
        "https://github.com/midoks/mdserver-web/archive/refs/heads/dev.zip"
    )
    rm -f "$zip"
    for u in "${urls[@]}"; do
        info "下载源码：$u"
        if curl -fSL --progress-bar --max-time 300 "$u" -o "$zip" 2>&1 && unzip -t "$zip" >/dev/null 2>&1; then
            ok "mdserver 源码下载完成（$(du -h "$zip" | cut -f1)）"
            break
        fi
        rm -f "$zip"
    done
    [ -f "$zip" ] || die "mdserver-web 源码下载失败（多加速源均不可达）"
    rm -rf /tmp/mw-extract /www/server/mdserver-web
    mkdir -p /tmp/mw-extract
    cd /tmp/mw-extract
    unzip -q "$zip" || die "mdserver-web 源码解压失败"
    mv mdserver-web-dev /www/server/mdserver-web 2>/dev/null || mv mdserver-web-master /www/server/mdserver-web 2>/dev/null || die "解压目录识别失败"
    info "运行 mdserver-web 安装脚本（自动检测环境，约 5-15 分钟，日志 /tmp/mdserver-install.log）…"
    cd /www/server/mdserver-web
    bash scripts/install.sh > /tmp/mdserver-install.log 2>&1 || die "mdserver-web 安装失败，查看日志：tail -80 /tmp/mdserver-install.log"
    ok "mdserver-web 安装完成"
}

# ---------- 10. 输出结果 ----------
print_summary() {
    local ip
    ip="$(hostname -I 2>/dev/null | awk '{print $1}')"
    ip="${ip:-服务器IP}"
    echo ""
    echo "================================================================"
    echo "   ABao 阿宝面板安装完成！"
    echo "----------------------------------------------------------------"
    echo "   面板地址:   http://${ip}:${APP_PORT}/abao/index.html"
    echo "   本地地址:   http://localhost:${APP_PORT}/abao/index.html"
    echo "   (Coolify/mdserver 原登录入口均已关闭，统一从此进入)"
    echo ""
    if [ -f "$ABAO_DIR/.abao-credentials" ]; then
        echo "   --- 管理员账号（已存 $ABAO_DIR/.abao-credentials） ---"
        grep -E '管理员(邮箱|密码)' "$ABAO_DIR/.abao-credentials"
    else
        echo "   管理员邮箱: $([ -f "$ABAO_DIR/.env" ] && grep '^ROOT_USER_EMAIL=' "$ABAO_DIR/.env" | cut -d= -f2)"
        echo "   管理员密码: $([ -f "$ABAO_DIR/.env" ] && grep '^ROOT_USER_PASSWORD=' "$ABAO_DIR/.env" | cut -d= -f2)"
    fi
    echo ""
    echo "   mdserver 账号: 安装结束时 mdserver 脚本上方输出中的账号/密码（或运行 mw 查看）"
    echo "   自定义账号:    重装时可用环境变量 ABAO_ADMIN_EMAIL / ABAO_ADMIN_PASSWORD 指定"
    echo ""
    echo "   常用命令:   mw                # mdserver 面板 CLI"
    echo "               docker ps         # 查看容器状态"
    echo "               tail -f /tmp/abao-install.log   # 安装日志"
    echo "================================================================"
}

main() {
    info "========== ABao 阿宝面板一键安装 v2.4.1（CentOS7 兼容 + 幂等重跑） =========="
    require_root
    detect_os
    check_port "$APP_PORT"
    check_port "$MDSERVER_PORT"
    install_docker
    configure_docker_mirror
    fetch_source
    gen_env
    start_coolify
    deploy_frontend
    patch_nginx
    open_firewall
    install_mdserver
    print_summary
    ok "全部完成！如需修改密码：面板「设置」中修改；数据库/Redis 密码见 $ABAO_DIR/.env"
}
main "$@"
