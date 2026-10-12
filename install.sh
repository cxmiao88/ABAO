#!/usr/bin/env bash
# ============================================================================
#  ABao 阿宝面板 - 一键安装脚本 v2.6.20（2026-10-11 更新：插件宿主页修复白屏 + 侧边栏可滚动 + CLI 完整菜单）
#  ---------------------------------------------------------------------------
#  用法（root 用户执行）：
#    curl -fsSL https://raw.githubusercontent.com/cxmiao88/ABAO/main/install.sh | bash
#
#  功能：环境检测 → Docker 安装（国内镜像优先）→ 拉取 ABao 源码 →
#        生成安全配置（全自动，无交互）→ 启动 Coolify(8000) →
#        部署前端面板 → 覆盖容器 nginx 反代 → 安装 mdserver-web(48700) →
#        放行防火墙 → 输出访问地址与初始账号。
#        Web 服务（openresty，建站用）不自动编译，到面板后台「软件商店」安装（可实时看日志）。
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
FRONTEND_ZIP_JSD="https://cdn.jsdelivr.net/gh/cxmiao88/ABAO@main/frontend-dist/abao-frontend.zip"
FRONTEND_ZIP_GH="https://ghproxy.com/https://github.com/cxmiao88/ABAO/releases/latest/download/abao-frontend.zip"
FRONTEND_ZIP_GH2="https://ghfast.top/https://github.com/cxmiao88/ABAO/releases/latest/download/abao-frontend.zip"
FRONTEND_ZIP_GH3="https://gh-proxy.com/https://github.com/cxmiao88/ABAO/releases/latest/download/abao-frontend.zip"
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
    # 兼容多种包名：centos-release / centos-stream-release / opencloudos-release + os-release VERSION_ID 兜底
    if [ "$OS_ID" = "centos" ] || [ "$OS_ID" = "rhel" ] || [ "$OS_ID" = "opencloudos" ]; then
        relver="$(rpm -q --qf '%{VERSION}' centos-release 2>/dev/null | cut -d. -f1 | tr -dc '0-9')"
        [ -n "$relver" ] || relver="$(rpm -q --qf '%{VERSION}' centos-stream-release 2>/dev/null | cut -d. -f1 | tr -dc '0-9')"
        [ -n "$relver" ] || relver="$(rpm -q --qf '%{VERSION}' opencloudos-release 2>/dev/null | cut -d. -f1 | tr -dc '0-9')"
        [ -n "$relver" ] || relver="$(echo "${OS_VER}" | cut -d. -f1 | tr -dc '0-9')"
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
        local env_bak=""
        # 重跑/重装保留原 .env（账号密码/数据库密钥不变，与既有 db 数据卷兼容）
        if [ -f "$ABAO_DIR/.env" ]; then
            env_bak="/tmp/abao-env.bak.$$"
            cp "$ABAO_DIR/.env" "$env_bak"
            info "已备份现有 .env（重跑保留原账号与数据库密码）"
        fi
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
        if [ -n "$env_bak" ] && [ -f "$env_bak" ]; then
            cp "$env_bak" "$ABAO_DIR/.env"
            chmod 600 "$ABAO_DIR/.env"
            info "已恢复原有 .env（账号密码不变）"
        fi
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

# ABao 统一登录：panel-api 归属的 Coolify 管理员邮箱（与上方 ROOT_USER_EMAIL 一致）
PANEL_ADMIN_EMAIL=${admin_email}
PANEL_MDSERVER_URL=http://host.docker.internal:${MDSERVER_PORT}

REGISTRY_URL=docker.io
PHP_MEMORY_LIMIT=256M
EOF
    # 644：容器内 www-data 需要读取 .env（bind 只读挂载）
    chmod 644 "$env_file"
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
    # 跳过首次 onboarding（新装用户直接进入面板，与 ABao 统一入口一致）
    docker exec coolify php artisan tinker --execute '\App\Models\Team::query()->update(["show_boarding" => false]);' >/dev/null 2>&1 || true
    ok "Coolify 已启动（端口 ${APP_PORT}）"
}

# ---------- 6. 部署前端面板（Release 资产，进度显示 + 国内加速） ----------
deploy_frontend() {
    local tmp_zip="/tmp/abao-frontend.zip"
    rm -f "$tmp_zip"
    # ① 源码目录内置前端包（git clone / main.zip 已含 frontend-dist/）→ 零额外下载
    if [ -f "$ABAO_DIR/frontend-dist/abao-frontend.zip" ]; then
        cp "$ABAO_DIR/frontend-dist/abao-frontend.zip" "$tmp_zip"
        ok "前端包取自源码目录（frontend-dist/，免下载）"
    fi
    # ② jsdelivr 仓库文件加速（国内可达；文件仅 ~1MB）
    if [ ! -f "$tmp_zip" ]; then
        info "下载 ABao 前端面板（jsdelivr 仓库文件加速，120 秒超时）…"
        curl -fSL --progress-bar --max-time 120 "$FRONTEND_ZIP_JSD" -o "$tmp_zip" 2>&1 || rm -f "$tmp_zip"
    fi
    # ③ GitHub Release 直连 + 多加速源兜底
    if [ ! -f "$tmp_zip" ]; then
        info "下载 ABao 前端面板（GitHub Release 直连，30 秒超时）…"
        if ! curl -fSL --progress-bar --max-time 30 "$FRONTEND_ZIP_URL" -o "$tmp_zip" 2>&1; then
            for u in "$FRONTEND_ZIP_GH2" "$FRONTEND_ZIP_GH3" "$FRONTEND_ZIP_GH"; do
                info "切换加速通道：$u"
                curl -fSL --progress-bar --max-time 300 "$u" -o "$tmp_zip" 2>&1 && break
                rm -f "$tmp_zip"
            done
        fi
    fi
    [ -f "$tmp_zip" ] || die "前端面板下载失败（源码目录/jsdelivr/Release/多加速源均不可达，请配置代理后重试）"
    ok "前端包下载完成（$(du -h "$tmp_zip" | cut -f1)）"
    docker exec -u 0 coolify rm -rf /var/www/html/public/abao 2>/dev/null || docker exec coolify rm -rf /var/www/html/public/abao 2>/dev/null || true
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

# ---------- 7. 覆盖容器 nginx 配置（ABao 反代规则 + 随机码入口） ----------
patch_nginx() {
    info "写入 ABao nginx 反代配置（随机码入口隐藏面板）…"
    # 随机码：已生成则复用（重跑不换），否则新生成，持久化到 .env
    local entry_code
    entry_code="$(grep '^ABAO_ENTRY_CODE=' "$ABAO_DIR/.env" 2>/dev/null | cut -d= -f2)"
    if [ -z "$entry_code" ]; then
        entry_code="$(openssl rand -hex 8 2>/dev/null)"
        echo "ABAO_ENTRY_CODE=${entry_code}" >> "$ABAO_DIR/.env"
        ok "已生成面板入口随机码（写 .env 持久化）"
    fi
    # 占位符替换 → 临时文件 → 进容器
    sed "s|__ABAO_ENTRY__|${entry_code}|g" "$ABAO_DIR/docker/abao-nginx-http.conf" > /tmp/abao-nginx-http.conf
    docker cp /tmp/abao-nginx-http.conf coolify:/etc/nginx/site-opts.d/http.conf
    docker exec coolify nginx -t >/dev/null 2>&1 && docker exec coolify nginx -s reload >/dev/null 2>&1 || \
        warn "nginx 配置重载失败（稍后可手动：docker exec coolify nginx -s reload）"
    ok "nginx 反代配置已生效（入口 http://IP:${APP_PORT}/${entry_code}，/abao/ 与原入口均已隐藏）"
}

# ---------- 7b. 部署 panel-api 二开代码（统一登录/数据接口） ----------
deploy_panel_api() {
    local psrc="$ABAO_DIR/panel-src"
    [ -d "$psrc" ] || { warn "panel-src 缺失（源码不含二开补丁，跳过 panel-api；面板登录将不可用）"; return; }
    info "部署 panel-api 二开代码（路由补丁 + 控制器 + 服务）…"
    local PY=""
    for c in python3 python python2; do
        if command -v "$c" >/dev/null 2>&1; then PY="$c"; break; fi
    done
    [ -n "$PY" ] || die "未找到 python，无法为 routes/web.php 打补丁"
    # 1) config / controller / services 直接进容器
    docker cp "$psrc/config-panel-api.php" coolify:/var/www/html/config/panel-api.php
    docker exec coolify mkdir -p /var/www/html/app/Http/Controllers/Panel /var/www/html/app/Services/Panel
    docker cp "$psrc/PanelApiController.php" coolify:/var/www/html/app/Http/Controllers/Panel/PanelApiController.php
    for f in "$psrc"/services/*.php; do
        docker cp "$f" "coolify:/var/www/html/app/Services/Panel/$(basename "$f")" 2>/dev/null || true
    done
    # 2) routes/web.php 补丁（幂等，宿主 python 操作，再拷回容器）
    cp "$psrc/patch_routes.py" /tmp/abao-patch.py
    cp "$psrc/seg_a.php" /tmp/seg_a.php
    cp "$psrc/seg_b.php" /tmp/seg_b.php
    docker cp coolify:/var/www/html/routes/web.php /tmp/abao-web.php
    if ! "$PY" /tmp/abao-patch.py /tmp/abao-web.php /tmp/seg_a.php /tmp/seg_b.php; then
        warn "routes/web.php 补丁失败（可能是版本差异），panel-api 路由可能不完整"
    fi
    docker cp /tmp/abao-web.php coolify:/var/www/html/routes/web.php
    # 3) 清缓存，路由/配置立即生效
    docker exec coolify php artisan config:clear >/dev/null 2>&1 || true
    docker exec coolify php artisan route:clear >/dev/null 2>&1 || true
    ok "panel-api 已部署（/panel-api/health|login + 全部数据接口）"
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
# ---------- 0. 启用 IP 转发（Docker 端口映射依赖；部分云厂商镜像默认关闭导致外网访问不了） ----------
enable_ip_forward() {
    if [ "$(cat /proc/sys/net/ipv4/ip_forward 2>/dev/null)" != "1" ]; then
        echo 1 > /proc/sys/net/ipv4/ip_forward
        sysctl -w net.ipv4.ip_forward=1 >/dev/null 2>&1
    fi
    if ! grep -q '^net.ipv4.ip_forward' /etc/sysctl.conf 2>/dev/null; then
        echo 'net.ipv4.ip_forward = 1' >> /etc/sysctl.conf
    else
        sed -i 's/^net.ipv4.ip_forward.*/net.ipv4.ip_forward = 1/' /etc/sysctl.conf 2>/dev/null || true
    fi
    sysctl -p >/dev/null 2>&1 || true
    ok "已启用 IP 转发（net.ipv4.ip_forward=1，Docker 端口映射依赖）"
}
open_firewall() {
    if systemctl is-active firewalld >/dev/null 2>&1; then
        firewall-cmd --permanent --add-port="${APP_PORT}/tcp" >/dev/null 2>&1 || true
        firewall-cmd --permanent --add-port="${MDSERVER_PORT}/tcp" >/dev/null 2>&1 || true
        firewall-cmd --reload >/dev/null 2>&1 || true
        ok "已放行端口 ${APP_PORT} / ${MDSERVER_PORT}"
        # firewalld 重启会清空 Docker 自定义网络规则，导致容器间（Postgres/Redis/应用）TCP 断连（实测），
        # 面板服务器统一停用，由云安全组兜底限制公网访问。
        warn "停用 firewalld（Docker 容器网络需要；请在云安全组仅放行 ${APP_PORT}/${MDSERVER_PORT}）"
        systemctl stop firewalld >/dev/null 2>&1 || true
        systemctl disable firewalld >/dev/null 2>&1 || true
    fi
    if command -v getenforce >/dev/null 2>&1 && [ "$(getenforce 2>/dev/null)" = "Enforcing" ]; then
        setenforce 0 2>/dev/null || true
        warn "已临时关闭 SELinux（仅本次运行）；如需永久关闭请编辑 /etc/selinux/config"
    fi
}

# ---------- 9. 安装 mdserver-web ----------
install_mdserver() {
    if [ -f /www/server/mdserver-web/scripts/install.sh ] || [ -f /www/server/mdserver-web/start.py ]; then
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
    mkdir -p /tmp/mw-extract /www/server
    cd /tmp/mw-extract
    unzip -q "$zip" || die "mdserver-web 源码解压失败"
    # 自动识别源码根目录：优先 start.py；其次 scripts/install.sh（面板安装入口）；再兜底任意 .py
    local mw_root
    mw_root="$(find /tmp/mw-extract -maxdepth 2 -name start.py -printf '%h\n' -quit 2>/dev/null)"
    [ -n "$mw_root" ] || mw_root="$(find /tmp/mw-extract -maxdepth 3 -path '*/scripts/install.sh' -printf '%h\n' -quit 2>/dev/null | sed 's|/scripts$||')"
    [ -n "$mw_root" ] || mw_root="$(find /tmp/mw-extract -maxdepth 2 -name '*.py' -printf '%h\n' -quit 2>/dev/null)"
    [ -n "$mw_root" ] || die "解压目录识别失败（/tmp/mw-extract 内未找到源码）"
    # 放置源码：直接 cp -r（比 mv 更稳，兼容跨分区；13M 秒级），成功后清理源
    rm -f /tmp/mw-cp.err
    cp -r "$mw_root" /www/server/mdserver-web 2>/tmp/mw-cp.err || {
        err "cp 错误：$(tail -3 /tmp/mw-cp.err 2>/dev/null || true)"
        die "mdserver 源码放置失败（$mw_root → /www/server/mdserver-web）"
    }
    rm -rf "$mw_root"
    [ -f /www/server/mdserver-web/scripts/install.sh ] || [ -f /www/server/mdserver-web/start.py ] \
        || die "mdserver 源码放置校验失败（缺少 scripts/install.sh / start.py）"
    ok "mdserver 源码就绪：/www/server/mdserver-web"
    info "运行 mdserver-web 安装脚本（自动检测环境，约 5-15 分钟，日志 /tmp/mdserver-install.log）…"
    cd /www/server/mdserver-web
    bash scripts/install.sh > /tmp/mdserver-install.log 2>&1 &
    local mw_pid=$!
    local mw_start=$SECONDS
    local mw_ok=0
    # 心跳 + 健康检查兜底：安装日志出现完成标志 或 面板 API(48700) 可达 即视为成功（安装脚本偶发装完仍挂起）
    while kill -0 $mw_pid 2>/dev/null && [ $(( SECONDS - mw_start )) -lt 1800 ]; do
        sleep 45
        if grep -qE 'Install completed|安装完毕' /tmp/mdserver-install.log 2>/dev/null; then
            sleep 15   # 留出收尾时间
            mw_ok=1
            break
        fi
        if curl -s -o /dev/null -w '%{http_code}' --connect-timeout 5 http://127.0.0.1:48700/login 2>/dev/null | grep -qE '200|30[0-9]|40[0-9]'; then
            mw_ok=1
            break
        fi
        info "mdserver 安装中（已运行 $(( (SECONDS - mw_start) / 60 )) 分钟，日志 tail -20 /tmp/mdserver-install.log）…"
    done
    if [ "$mw_ok" = "1" ]; then
        kill $mw_pid 2>/dev/null || true
        ok "mdserver-web 已就绪（面板 API 可达，耗时 $(( (SECONDS - mw_start) / 60 )) 分钟）"
    else
        wait $mw_pid || die "mdserver-web 安装失败，查看日志：tail -80 /tmp/mdserver-install.log"
        ok "mdserver-web 安装完成（耗时 $(( (SECONDS - mw_start) / 60 )) 分钟）"
    fi
    # 对齐面板端口 48700（配合 nginx 反代）+ 关闭安全入口（去掉第一道认证）+ 重启面板
    printf '48700' > /www/server/mdserver-web/data/port.pl 2>/dev/null || true
    sqlite3 /www/server/mdserver-web/data/panel.db "UPDATE option SET value='' WHERE name='admin_path';" 2>/dev/null || true
    /etc/rc.d/init.d/mw restart >/dev/null 2>&1 || true
    sleep 3
    if curl -s -o /dev/null -w '%{http_code}' --connect-timeout 5 http://127.0.0.1:48700/login 2>/dev/null | grep -qE '200|30[0-9]|40[0-9]'; then
        ok "mdserver 端口已对齐 48700，安全入口已关闭"
    else
        warn "mdserver 面板重启后未响应 48700（可稍后 AB 菜单手动处理）"
    fi
    # ABao 增强：站点创建支持指定 PHP 版本（建站/已建站点切换 PHP 8.2/8.3，幂等 patch）
    if [ -f /www/server/mdserver-web/web/admin/site/site.py ] && [ -f /www/server/mdserver-web/web/utils/site.py ]; then
        cp /www/server/mdserver-web/web/admin/site/site.py /www/server/mdserver-web/web/admin/site/site.py.bak-phpver 2>/dev/null || true
        cp /www/server/mdserver-web/web/utils/site.py /www/server/mdserver-web/web/utils/site.py.bak-phpver 2>/dev/null || true
        /www/server/mdserver-web/bin/python3 - <<'ABAOPHPVER'
import os
u = '/www/server/mdserver-web/web/utils/site.py'
a = '/www/server/mdserver-web/web/admin/site/site.py'
changed = False
if os.path.exists(u):
    s = open(u).read()
    seg = s.split('def add(')[1].split('def addDomain')[0] if 'def add(' in s and 'def addDomain' in s else s
    if 'php_version' not in seg:
        s = s.replace(
            'def add(self, site_info, port, ps, path, version):',
            "def add(self, site_info, port, ps, path, version, php_version=''):"
        )
        anchor = "        mw.restartWeb()\n\n        self.runHook('site_cb', 'add')"
        ins = "        if php_version:\n            self.setPhpVersion(self.siteName, php_version)\n\n        mw.restartWeb()\n\n        self.runHook('site_cb', 'add')"
        if anchor in s:
            s = s.replace(anchor, ins, 1)
            open(u, 'w').write(s)
            changed = True
if os.path.exists(a):
    s = open(a).read()
    if "php_version = request.form.get('php_version', '')" not in s:
        old = "    port = request.form.get('port', '')\n    return MwSites.instance().add(webinfo, port, ps, path, version)"
        new = "    port = request.form.get('port', '')\n    php_version = request.form.get('php_version', '')\n    return MwSites.instance().add(webinfo, port, ps, path, version, php_version)"
        if old in s:
            s = s.replace(old, new, 1)
            open(a, 'w').write(s)
            changed = True
print('phpver-patch:', 'applied' if changed else 'already-ok')
ABAOPHPVER
        /www/server/mdserver-web/bin/python3 -m py_compile /www/server/mdserver-web/web/admin/site/site.py /www/server/mdserver-web/web/utils/site.py 2>/dev/null \
            && ok "站点 PHP 版本增强已固化（建站可选 PHP 版本）" || warn "站点 PHP 版本增强 patch 未完全生效（建站仍可后切）"
        /etc/rc.d/init.d/mw restart >/dev/null 2>&1 || true
        sleep 2
    fi
    # 插件宿主页：/static/app/plugin-host.html（补齐 jQuery/layer/插件公共函数，解决前端 iframe 插件配置白屏）
    if [ -f "$ABAO_DIR/panel-src/plugin-host.html" ]; then
        mkdir -p /www/server/mdserver-web/web/static/app
        /bin/cp -f "$ABAO_DIR/panel-src/plugin-host.html" /www/server/mdserver-web/web/static/app/plugin-host.html
        chmod 644 /www/server/mdserver-web/web/static/app/plugin-host.html
        ok "插件宿主页已部署（/static/app/plugin-host.html）"
    else
        warn "缺少 panel-src/plugin-host.html，插件配置页可能白屏"
    fi
    # 插件页组件级深色：site.css 追加 html[data-mode="dark"] 规则（幂等；配合 plugin-host.html 宿主页 data-mode）
    if [ -f /www/server/mdserver-web/web/static/css/site.css ] && ! grep -q 'ABao 深色主题' /www/server/mdserver-web/web/static/css/site.css; then
        cat >> /www/server/mdserver-web/web/static/css/site.css <<'ABAODARK'
/* ===== ABao 深色主题 ===== */
html[data-mode="dark"] body,
html[data-mode="dark"] .bt-form,
html[data-mode="dark"] .bt-w-main,
html[data-mode="dark"] .bt-w-con { background: #1e2025 !important; color: #d8dae0 !important; }
html[data-mode="dark"] .bt-w-menu { background: #17181c !important; color: #d8dae0 !important; }
html[data-mode="dark"] .bt-w-menu p { color: #c9cdd6 !important; }
html[data-mode="dark"] .bt-w-menu p.bgw,
html[data-mode="dark"] .bt-w-menu .bgw,
html[data-mode="dark"] .bt-w-menu p.on,
html[data-mode="dark"] .bt-w-menu p.active { background: #23262c !important; color: #fff !important; }
html[data-mode="dark"] .bt-w-menu p:hover { background: #26292f !important; }
html[data-mode="dark"] .bt-input-text-shell,
html[data-mode="dark"] .bt-input-text,
html[data-mode="dark"] input[type=text],
html[data-mode="dark"] input[type=password],
html[data-mode="dark"] input[type=number],
html[data-mode="dark"] select,
html[data-mode="dark"] textarea { background: #23262c !important; border-color: #3a3f47 !important; color: #e8eaee !important; }
html[data-mode="dark"] table th { background: #26292f !important; color: #e8eaee !important; border-color: #343941 !important; }
html[data-mode="dark"] table td { border-color: #343941 !important; }
html[data-mode="dark"] .layui-layer,
html[data-mode="dark"] .layui-layer-content,
html[data-mode="dark"] .layui-layer-dialog { background: #1e2025 !important; color: #d8dae0 !important; max-width: calc(100vw - 40px) !important; box-sizing: border-box !important; }
html[data-mode="dark"] .layui-layer-btn a { background: #23262c !important; border-color: #3a3f47 !important; color: #e8eaee !important; }
html[data-mode="dark"] .layui-layer-btn a.layui-layer-btn0 { background: #2f6fed !important; border-color: #2f6fed !important; color: #fff !important; }
html[data-mode="dark"] .layui-layer-title { background: #26292f !important; color: #e8eaee !important; }
html[data-mode="dark"] a { color: #5ea8ff !important; }
html[data-mode="dark"] h1,
html[data-mode="dark"] h2,
html[data-mode="dark"] h3,
html[data-mode="dark"] h4,
html[data-mode="dark"] .title { color: #e8eaee !important; }
html[data-mode="dark"] .bt-logs { background: #111318 !important; color: #c8ccd4 !important; }
ABAODARK
        ok "插件页深色已注入（site.css html[data-mode=dark]）"
    fi
    # mdserver 静态缓存源头清零：Flask SEND_FILE_MAX_AGE_DEFAULT 604800→0（浏览器不再缓存旧插件页/样式）
    if [ -f /www/server/mdserver-web/web/admin/__init__.py ]; then
        sed -i "s/SEND_FILE_MAX_AGE_DEFAULT'] = 604800/SEND_FILE_MAX_AGE_DEFAULT'] = 0/" /www/server/mdserver-web/web/admin/__init__.py
        ok "mdserver 静态缓存已清零（Cache-Control: no-cache）"
    fi
    # ABao CLI：ab/AB（等同宝塔 bt）——菜单循环保持，处理完自动回菜单，输入 0 或 q 退出
    if [ -f "$ABAO_DIR/panel-src/ab-cli.sh" ]; then
        /bin/cp -f "$ABAO_DIR/panel-src/ab-cli.sh" /usr/local/bin/ab
        /bin/cp -f "$ABAO_DIR/panel-src/ab-cli.sh" /usr/local/bin/AB
        chmod 755 /usr/local/bin/ab /usr/local/bin/AB
    else
        ln -sf /etc/rc.d/init.d/mw /usr/local/bin/ab 2>/dev/null || true
        ln -sf /etc/rc.d/init.d/mw /usr/local/bin/AB 2>/dev/null || true
    fi
    ok "CLI 命令已就绪：ab/AB（阿宝面板命令，等同宝塔 bt；菜单循环，0 或 q 退出）"
    # ABao 高端默认站页面：新建站点自动生成（模板 web/static/abao/site-default.html + site.py 补丁）
    if [ -f "$ABAO_DIR/panel-src/abao-site-default.html" ]; then
        mkdir -p /www/server/mdserver-web/web/static/abao
        /bin/cp -f "$ABAO_DIR/panel-src/abao-site-default.html" /www/server/mdserver-web/web/static/abao/site-default.html
        chmod 644 /www/server/mdserver-web/web/static/abao/site-default.html
        # site.py createRootDir 补丁：autoInit 时读模板（替换 {{DOMAIN}}）替代写死 'Work has started!!!'
        python3 - <<'PYEOF'
import os
site_py = "/www/server/mdserver-web/web/utils/site.py"
src = open(site_py, encoding="utf-8").read()
old = """        if autoInit:
            mw.writeFile(path + '/index.html', 'Work has started!!!')
            mw.execShell('chmod -R 755 ' + path)"""
new = """        if autoInit:
            tpl = mw.getServerDir() + '/mdserver-web/web/static/abao/site-default.html'
            html = 'Work has started!!!'
            if os.path.exists(tpl):
                try:
                    site_name = os.path.basename(path.rstrip('/'))
                    with open(tpl, encoding='utf-8') as f:
                        html = f.read().replace('{{DOMAIN}}', site_name)
                except Exception:
                    pass
            mw.writeFile(path + '/index.html', html)
            mw.execShell('chmod -R 755 ' + path)"""
if old in src and "site-default.html" not in src:
    open(site_py, "w", encoding="utf-8").write(src.replace(old, new))
    print("patched")
else:
    print("skip")
PYEOF
        # 增强：目录已存在但缺 index.html 时也补写默认页（避免空目录 403，幂等）
        /www/server/mdserver-web/bin/python3 - <<'PYEOF2'
p = "/www/server/mdserver-web/web/utils/site.py"
s = open(p, encoding="utf-8").read()
if "ABao: 空目录自动补默认页" in s:
    print("skip-createdir-enh")
else:
    old = "    def createRootDir(self, path):\n        autoInit = False\n        if not os.path.exists(path):\n            autoInit = True\n            os.makedirs(path)\n        if not mw.isAppleSystem():\n"
    new = "    def createRootDir(self, path):\n        autoInit = False\n        if not os.path.exists(path):\n            autoInit = True\n            os.makedirs(path)\n        # ABao: 空目录自动补默认页（目录存在但缺 index.html 也补写，避免 403）\n        if not os.path.exists(path + '/index.html'):\n            autoInit = True\n        if not mw.isAppleSystem():\n"
    if old in s:
        s = s.replace(old, new, 1)
        open(p, "w", encoding="utf-8").write(s)
        print("patched-createdir-enh")
    else:
        print("anchor-not-found")
PYEOF2
        # 反向代理切换支持：补 enable-php-000.conf（内容同 00，仅 PHP_ENV=0 不解析 PHP）
        if [ -f /www/server/web_conf/php/conf/enable-php-00.conf ] && [ ! -f /www/server/web_conf/php/conf/enable-php-000.conf ]; then
            cp /www/server/web_conf/php/conf/enable-php-00.conf /www/server/web_conf/php/conf/enable-php-000.conf
        fi
        ok "ABao 高端默认站页面已部署（新建站点自动生成 + 空目录自动补页 + 反向代理 PHP conf）"
    else
        warn "缺少 panel-src/abao-site-default.html，新建站点将使用默认占位页"
    fi
    # ---------- ABao 一键部署网站程序（像宝塔一样：WordPress/Typecho/Z-BlogPHP/Nextcloud） ----------
    # 后端在 web/admin/site/site.py 追加 /site/deploy_list + /site/deploy_install（幂等：已含标记则跳过）
    if grep -q 'ABao 一键部署网站程序' /www/server/mdserver-web/web/admin/site/site.py; then
        ok "一键部署网站程序 patch 已存在，跳过"
    else
        cp /www/server/mdserver-web/web/admin/site/site.py /www/server/mdserver-web/web/admin/site/site.py.bak-deploy 2>/dev/null || true
        cat > /tmp/abao-deploy-append.py <<'DEPLOYEOF'
import re
import sqlite3 as _abao_sqlite3
import secrets as _abao_secrets
import shutil as _abao_shutil

ABAO_PROGRAMS = [
    {'key': 'wordpress', 'name': 'WordPress', 'version': '7.x',
     'desc': '全球最流行的开源博客与内容管理系统（CMS）', 'type': 'php', 'need_db': True,
     'url': 'https://cn.wordpress.org/latest-zh_CN.zip', 'home': 'https://cn.wordpress.org/'},
    {'key': 'typecho', 'name': 'Typecho', 'version': '1.2+',
     'desc': '轻量、优雅的开源博客程序', 'type': 'php', 'need_db': True,
     'url': 'https://gh-proxy.com/https://github.com/typecho/typecho/releases/latest/download/typecho.zip', 'home': 'https://typecho.org/'},
    {'key': 'zblogphp', 'name': 'Z-BlogPHP', 'version': '1.8',
     'desc': '小巧强大的 PHP 博客程序，支持插件与主题', 'type': 'php', 'need_db': True,
     'url': 'https://gh-proxy.com/https://github.com/zblogcn/zblogphp/releases/download/v1.8.0.4020/zblogphp-1.8.0.4020.zip', 'home': 'https://www.zblogcn.com/'},
    {'key': 'nextcloud', 'name': 'Nextcloud', 'version': '35.x',
     'desc': '自托管云盘与协作平台（约 295MB，安装较慢）', 'type': 'php', 'need_db': True,
     'url': 'https://download.nextcloud.com/server/releases/latest.zip', 'home': 'https://nextcloud.com/'},
]

def _abao_get_mysql_root():
    try:
        conn = _abao_sqlite3.connect('/www/server/mysql-community/mysql.db')
        row = conn.execute('select * from config where id=1').fetchone()
        conn.close()
        if row:
            return row[1]
    except Exception:
        pass
    return ''

def _abao_create_database(db_name, db_user, db_pwd):
    if not re.match(r'^[a-zA-Z0-9_]+$', db_name) or not re.match(r'^[a-zA-Z0-9_]+$', db_user):
        return '数据库名/用户名只能包含字母、数字、下划线'
    if len(db_pwd) < 8 or len(db_pwd) > 32:
        return '数据库密码长度需为 8-32 位'
    root_pwd = _abao_get_mysql_root()
    if not root_pwd:
        return '无法读取 MySQL root 密码，数据库创建失败'
    sock = '/www/server/mysql-community/mysql.sock'
    mysql = '/www/server/mysql-community/bin/mysql'
    sql = ("CREATE DATABASE IF NOT EXISTS {db} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
           "CREATE USER IF NOT EXISTS '{u}'@'localhost' IDENTIFIED BY '{p}';"
           "CREATE USER IF NOT EXISTS '{u}'@'127.0.0.1' IDENTIFIED BY '{p}';"
           "GRANT ALL PRIVILEGES ON {db}.* TO '{u}'@'localhost';"
           "GRANT ALL PRIVILEGES ON {db}.* TO '{u}'@'127.0.0.1';"
           "FLUSH PRIVILEGES;").format(db=db_name, u=db_user, p=db_pwd)
    cmd = mysql + " -uroot -p'" + root_pwd + "' -S " + sock + ' -e "' + sql + '"'
    ret = mw.execShell(cmd)
    if 'ERROR' in (ret[0] + ret[1]):
        return '数据库创建失败: ' + (ret[1] or ret[0])[:200]
    return ''

def _abao_download_extract(url, key, target_dir):
    tmp_dir = '/www/tmp/abao-deploy'
    try:
        os.makedirs(tmp_dir, exist_ok=True)
    except Exception:
        tmp_dir = '/tmp/abao-deploy'
        os.makedirs(tmp_dir, exist_ok=True)
    zipf = tmp_dir + '/' + key + '.zip'
    mw.execShell('curl -sL --connect-timeout 15 -m 600 -o ' + zipf + ' "' + url + '"')
    if not os.path.exists(zipf) or os.path.getsize(zipf) < 1000:
        return False, '下载源码失败（请检查网络或稍后重试）'
    ex_dir = tmp_dir + '/' + key
    _abao_shutil.rmtree(ex_dir, ignore_errors=True)
    os.makedirs(ex_dir, exist_ok=True)
    ret = mw.execShell('unzip -q -o ' + zipf + ' -d ' + ex_dir)
    if ret[0] != '' and 'error' in ret[0].lower() and 'warning' not in ret[0].lower():
        return False, '解压源码失败: ' + ret[0][:200]
    src = ex_dir
    items = [i for i in os.listdir(ex_dir) if not i.startswith('__MACOSX')]
    if len(items) == 1 and os.path.isdir(ex_dir + '/' + items[0]):
        src = ex_dir + '/' + items[0]
    try:
        os.remove(target_dir + '/index.html')
    except Exception:
        pass
    mw.execShell('cp -rf ' + src + '/* ' + target_dir + '/')
    mw.execShell('cp -rf ' + src + '/.[!.]* ' + target_dir + '/ 2>/dev/null; true')
    try:
        os.remove(zipf)
        _abao_shutil.rmtree(ex_dir, ignore_errors=True)
    except Exception:
        pass
    return True, ''

def _abao_write_wp_config(target_dir, db_name, db_user, db_pwd):
    sample = target_dir + '/wp-config-sample.php'
    if not os.path.exists(sample):
        return False
    content = mw.readFile(sample)
    content = re.sub(r"define\s*\(\s*'DB_NAME',\s*'[^']*'", "define('DB_NAME', '" + db_name + "'", content)
    content = re.sub(r"define\s*\(\s*'DB_USER',\s*'[^']*'", "define('DB_USER', '" + db_user + "'", content)
    content = re.sub(r"define\s*\(\s*'DB_PASSWORD',\s*'[^']*'", "define('DB_PASSWORD', '" + db_pwd + "'", content)
    content = re.sub(r"define\s*\(\s*'DB_HOST',\s*'[^']*'", "define('DB_HOST', '127.0.0.1:33206'", content)
    for salt in ['AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY',
                 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT']:
        content = re.sub(r"define\s*\(\s*'" + salt + r"',\s*'[^']*'",
                         "define('" + salt + "', '" + _abao_secrets.token_hex(32) + "'", content)
    mw.writeFile(target_dir + '/wp-config.php', content)
    return True

@blueprint.route('/deploy_list', endpoint='deploy_list', methods=['GET', 'POST'])
@panel_login_required
def deploy_list():
    data = []
    for p in ABAO_PROGRAMS:
        data.append({'key': p['key'], 'name': p['name'], 'version': p['version'],
                     'desc': p['desc'], 'type': p['type'], 'need_db': p['need_db'], 'home': p['home']})
    return mw.returnData(True, 'OK', data)

@blueprint.route('/deploy_install', endpoint='deploy_install', methods=['POST'])
@panel_login_required
def deploy_install():
    site_name = request.form.get('siteName', '')
    program = request.form.get('program', '')
    db_name = request.form.get('dbName', '')
    db_user = request.form.get('dbUser', '')
    db_pwd = request.form.get('dbPwd', '')
    if not site_name or not program:
        return mw.returnData(False, '参数缺失')
    prog = None
    for p in ABAO_PROGRAMS:
        if p['key'] == program:
            prog = p
            break
    if not prog:
        return mw.returnData(False, '不支持的程序: ' + program)
    root_dir = '/www/wwwroot/' + site_name
    if not os.path.isdir(root_dir):
        return mw.returnData(False, '站点目录不存在，请先创建站点')
    if prog['need_db']:
        if not db_name or not db_user or not db_pwd:
            return mw.returnData(False, prog['name'] + ' 需要数据库，请填写数据库名/账号/密码')
        err = _abao_create_database(db_name, db_user, db_pwd)
        if err:
            return mw.returnData(False, err)
    ok, msg = _abao_download_extract(prog['url'], prog['key'], root_dir)
    if not ok:
        return mw.returnData(False, msg)
    wp_hint = ''
    if prog['key'] == 'wordpress':
        if _abao_write_wp_config(root_dir, db_name, db_user, db_pwd):
            wp_hint = '（已自动写入数据库配置）'
    mw.execShell('chown -R www:www ' + root_dir + ' 2>/dev/null; chmod -R 755 ' + root_dir + ' 2>/dev/null; true')
    mw.writeLog('网站管理', '一键部署 {1} 到站点 {0}'.format(site_name, prog['name']))
    return mw.returnData(True, prog['name'] + ' 部署完成' + wp_hint + '，请访问站点完成安装向导', {'program': prog['name']})
DEPLOYEOF
        cat /tmp/abao-deploy-append.py >> /www/server/mdserver-web/web/admin/site/site.py
        rm -f /tmp/abao-deploy-append.py
        /www/server/mdserver-web/bin/python3 -m py_compile /www/server/mdserver-web/web/admin/site/site.py 2>/dev/null \
            && ok "一键部署网站程序已固化（WordPress/Typecho/Z-BlogPHP/Nextcloud）" \
            || warn "一键部署网站程序 patch 语法检查失败"
    fi
    # ---------- ABao 自定义站点名 + 短目录删除修复（B03-45） ----------
    # ① site/add 支持可选 name（站点显示名/conf 名/目录默认名，不强制用域名）
    # ② 自定义名时主域名自动并入域名列表（nginx server_name 保证可访问）
    # ③ domainlist 缺失防御（官方 KeyError）
    # ④ delete 按 sites.path 删除目录（站点名 != 目录名时也能删干净）
    cd /www/server/mdserver-web && ./bin/python3 - <<'SITENAME'
import re
n = 0
# ① admin/site.py add 读 name
pa = "/www/server/mdserver-web/web/admin/site/site.py"
s = open(pa, encoding="utf-8").read()
old_a = """    php_version = request.form.get('php_version', '')
    return MwSites.instance().add(webinfo, port, ps, path, version, php_version)"""
new_a = """    php_version = request.form.get('php_version', '')
    # ABao: 自定义站点名（可选，不填则用域名）
    name = request.form.get('name', '')
    return MwSites.instance().add(webinfo, port, ps, path, version, php_version, name)"""
if "ABao: 自定义站点名" not in s and old_a in s:
    s = s.replace(old_a, new_a, 1)
    open(pa, "w", encoding="utf-8").write(s); n += 1
# ② utils/site.py add 签名 + 自定义名逻辑
pu = "/www/server/mdserver-web/web/utils/site.py"
s = open(pu, encoding="utf-8").read()
old_sig = "def add(self, site_info, port, ps, path, version, php_version=''):"
new_sig = "def add(self, site_info, port, ps, path, version, php_version='', name=''):"
if old_sig in s and new_sig not in s:
    s = s.replace(old_sig, new_sig, 1); n += 1
old_name = """        site_info = json.loads(site_info)

        self.siteName = self.toPunycode(site_info['domain'].strip().split(':')[0]).strip()
        self.sitePath = self.toPunycodePath(self.getPath(path.replace(' ', '')))"""
new_name = """        site_info = json.loads(site_info)

        # ABao: 自定义站点名（可选；仅字母数字._-，非法回退域名）；自定义名时主域名并入域名列表保证 server_name 可访问
        custom_name = name.strip() if name and re.match(r'^[a-zA-Z0-9][a-zA-Z0-9._-]*$', name.strip()) else ''
        main_domain = site_info['domain'].strip().split(':')[0]
        if custom_name:
            self.siteName = self.toPunycode(custom_name).strip()
            if main_domain not in [d.strip().split(':')[0] for d in site_info.get('domainlist', [])]:
                site_info['domainlist'] = site_info.get('domainlist', []) + [main_domain]
        else:
            self.siteName = self.toPunycode(main_domain).strip()
        self.sitePath = self.toPunycodePath(self.getPath(path.replace(' ', '')))"""
if "ABao: 自定义站点名" not in s and old_name in s:
    s = s.replace(old_name, new_name, 1); n += 1
# ③ domainlist 缺失防御
old_dl = """        # 添加更多域名
        for domain in site_info['domainlist']:"""
new_dl = """        # 添加更多域名
        for domain in site_info.get('domainlist', []):"""
if old_dl in s and "ABao: domainlist 防御" not in s:
    s = s.replace(old_dl, new_dl, 1)
    s = s.replace(new_dl, "# ABao: domainlist 防御\n" + new_dl, 1); n += 1
# ④ delete 按 sites.path 删除自定义目录
old_del = """        if path == '1':
            web_root_path = mw.getWwwDir() + '/' + webname
            mw.execShell('rm -rf ' + web_root_path)"""
new_del = """        if path == '1':
            # ABao: 自定义目录站点按 sites.path 删除（站点名可能不等于目录名）
            web_root_path = info['path'] if info.get('path', '').startswith('/') else (mw.getWwwDir() + '/' + webname)
            if web_root_path and web_root_path != mw.getWwwDir():
                mw.execShell('rm -rf ' + web_root_path)"""
if old_del in s and "ABao: 自定义目录站点按 sites.path" not in s:
    s = s.replace(old_del, new_del, 1); n += 1
open(pu, "w", encoding="utf-8").write(s)
print("site-name-patch:", n)
SITENAME
    cd /www/server/mdserver-web && ./bin/python3 -m py_compile web/admin/site/site.py web/utils/site.py 2>/dev/null \
        && systemctl restart abao-mdserver >/dev/null 2>&1 || /etc/rc.d/init.d/mw restart >/dev/null 2>&1 || true \
        && ok "自定义站点名 + 短目录删除修复已固化（B03-45：站点名可选、根目录只填末级、删除按真实目录）" \
        || warn "自定义站点名 patch 语法检查失败"
    # ---------- ABao 文件删除修复（文件管理删除目录/文件失败） ----------
    # ① 回收站目录兜底创建 + ② 回收站不可用则真删 + ③ data 参数兼容 [{"name":..}] / [".."]
    cd /www/server/mdserver-web && ./bin/python3 - <<'PYEOF'
import re
p = "/www/server/mdserver-web/web/utils/file.py"
s = open(p, encoding="utf-8").read()
n = 0
# ① mvRecycleBin：回收站目录缺失时兜底创建，仍不可用则返回 False（触发真删）
old1 = "def mvRecycleBin(path):\n    rb_dir = mw.getRecycleBinDir()\n    try:\n"
if old1 in s and "os.makedirs(rb_dir, exist_ok=True)" not in s:
    s = s.replace(old1, "def mvRecycleBin(path):\n    rb_dir = mw.getRecycleBinDir()\n    if not rb_dir:\n        return False\n    try:\n        if not os.path.exists(rb_dir):\n            os.makedirs(rb_dir, exist_ok=True)\n        if not os.path.exists(rb_dir):\n            return False\n    except Exception:\n        return False\n")
    n += 1
# ② del 分支：回收站不可用 -> 直接真删（目录 + 文件）
pat2 = re.compile(r"if is_recycle:\s*mvRecycleBin\(topath\)\s*else:\s*shutil\.rmtree\(filename\)", re.S)
repl2 = "if is_recycle:\n                        if not mvRecycleBin(topath):\n                            # ABao: 回收站不可用则真删\n                            shutil.rmtree(topath)\n                    else:\n                        shutil.rmtree(filename)"
s, c = pat2.subn(repl2, s); n += c
pat2b = re.compile(r"if is_recycle:\s*mvRecycleBin\(topath\)\s*else:\s*os\.remove\(filename\)", re.S)
repl2b = "if is_recycle:\n                        if not mvRecycleBin(topath):\n                            if os.path.exists(topath):\n                                os.remove(topath)\n                    else:\n                        os.remove(filename)"
s, c = pat2b.subn(repl2b, s); n += c
# ③ data 归一化：兼容前端 [{"name":"x"}] 与官方 ["x"]
pat3 = re.compile(r"if recycle_bin == 'open':\n            is_recycle = True\n        data = json.loads\(data\)\n")
new3 = "if recycle_bin == 'open':\n            is_recycle = True\n        data = json.loads(data)\n        if data and isinstance(data[0], dict):\n            data = [d.get('name', '') for d in data]\n"
s, c = pat3.subn(new3, s); n += c
open(p, "w", encoding="utf-8").write(s)
print("file-del-patch:", n)
PYEOF
    ./bin/python3 -m py_compile /www/server/mdserver-web/web/utils/file.py 2>/dev/null || true
    # ABao systemd 守护（mdserver 崩溃/重启后自动拉起，等同宝塔守护）
    cat > /etc/systemd/system/abao-mdserver.service <<'UNIT'
[Unit]
Description=ABao mdserver-web backend
After=network.target

[Service]
Type=forking
PIDFile=/www/server/mdserver-web/logs/panel.pid
ExecStart=/www/server/mdserver-web/scripts/init.d/mw start
ExecStop=/www/server/mdserver-web/scripts/init.d/mw stop
Restart=always
RestartSec=8
User=root

[Install]
WantedBy=multi-user.target
UNIT
    systemctl daemon-reload >/dev/null 2>&1 && systemctl enable abao-mdserver >/dev/null 2>&1
    systemctl restart abao-mdserver >/dev/null 2>&1 || /etc/rc.d/init.d/mw restart >/dev/null 2>&1 || true
    sleep 5
    ok "文件删除修复补丁 + systemd 守护已生效（mdserver 崩溃自动拉起）"
    # ---------- ABao 建站前置目录 + 占位 conf（面板软件商店装 openresty 后建站必需） ----------
    # 缺失 enable-php-0.conf / rewrite 空文件会导致 nginx -t 失败 → 网站访问不了（ERR_CONNECTION_REFUSED）
    mkdir -p /www/server/web_conf/php/conf /www/server/web_conf/nginx/rewrite
    [ -f /www/server/web_conf/php/conf/enable-php-0.conf ] || echo 'set $PHP_ENV 0;' > /www/server/web_conf/php/conf/enable-php-0.conf
    # ABao nginx 守护：openresty 统一由 mdserver 原生 init.d 管理（/www/server/openresty/init.d/openresty）。
    # 注意：切勿创建独立 systemd service——曾与 mdserver 双实例抢端口，导致：
    #   ① 建站后新配置永远不生效（旧 master 占端口，init.d restart 杀不掉）→ 站点 404；
    #   ② systemd 每 8 秒尝试 bind 失败，error.log 刷屏（实测 14.5MB）。
    # 仅注册 init.d 开机自启；openresty 已装且无 master 时用 init.d 拉起（幂等）。
    if [ -f /www/server/openresty/init.d/openresty ]; then
        ln -sf /www/server/openresty/init.d/openresty /etc/init.d/openresty
        chmod +x /etc/init.d/openresty
        chkconfig --add openresty >/dev/null 2>&1 || true
        chkconfig openresty on >/dev/null 2>&1 || true
    fi
    rm -f /etc/systemd/system/abao-nginx.service
    systemctl daemon-reload >/dev/null 2>&1 || true
    if [ -f /www/server/openresty/nginx/sbin/nginx ] && ! pgrep -f 'openresty -c' >/dev/null 2>&1 && ! pgrep -f 'nginx: master' >/dev/null 2>&1; then
        /etc/init.d/openresty start >/dev/null 2>&1 || /www/server/openresty/init.d/openresty start >/dev/null 2>&1 || true
    fi
    ok "建站占位 conf + nginx 开机自启（mdserver init.d 管理）已就绪（新建站点访问正常）"
}

# ---------- 9b. 安装 Web 服务（openresty，站点访问必需） ----------
install_web_service() {
    if [ -f /www/server/openresty/nginx/sbin/nginx ]; then
        ok "openresty 已安装，跳过"
        return
    fi
    info "安装 Web 服务 openresty（站点访问必需，源码编译约 10-30 分钟）…"
    # 编译依赖（按包管理器）
    case "$PKG" in
        apt-get) $PKG install -y build-essential libpcre3-dev libssl-dev zlib1g-dev unzip 2>&1 | tail -2 ;;
        yum|dnf) $PKG install -y gcc make pcre-devel openssl-devel zlib-devel unzip 2>&1 | tail -2 ;;
    esac
    local PLUG=/www/server/mdserver-web/plugins/openresty
    [ -d "$PLUG/versions" ] || { warn "mdserver openresty 插件缺失，跳过 Web 服务安装"; return; }
    # 版本选择：RHEL7 系（CentOS7 等）gcc 4.8 与新版 lua-cjson 不兼容（C99 报错），固定 1.25.3；其余用 1.31.1
    local VER=1.31.1
    case "$OS_ID" in
        centos|rhel|rocky|almalinux|opencloudos|kylin|openeuler)
            [ "${OS_VER%%.*}" -le 7 ] 2>/dev/null && VER=1.25.3 ;;
    esac
    # 修补 brotli（git clone 常失败导致 configure 报错，非必需模块）与损坏的 openssl 缓存
    local f
    for f in "$PLUG/versions/1.31.1/install.sh" "$PLUG/versions/1.25.3/install.sh"; do
        [ -f "$f" ] || continue
        sed -i 's|^[[:space:]]*git clone https://github.com/wxx9248/ngx_brotli.git|# SKIP-BROTLI: \&|' "$f"
        sed -i 's|^[[:space:]]*cd ${openrestyDir} && git clone https://github.com/wxx9248/ngx_brotli.git|# SKIP-BROTLI: \&|' "$f"
        sed -i 's|^[[:space:]]*OPTIONS="${OPTIONS} --add-module=${openrestyDir}/ngx_brotli"|# SKIP-BROTLI: \&|' "$f"
    done
    rm -f /www/server/source/openresty/openssl-*.tar.gz   # 中断下载可能产生 0 字节损坏缓存
    # 编译安装
    cd "$PLUG" && bash install.sh install "$VER" > /tmp/abao-openresty-install.log 2>&1
    if [ ! -f /www/server/openresty/nginx/sbin/nginx ]; then
        warn "openresty 安装未完成（详见 tail -80 /tmp/abao-openresty-install.log）；站点 Web 访问可能不可用"
        return
    fi
    # 补齐 php 占位 + rewrite 空文件（站点 conf include 必需，缺失会导致 nginx -t 失败）
    mkdir -p /www/server/web_conf/php/conf /www/server/web_conf/nginx/rewrite
    [ -f /www/server/web_conf/php/conf/enable-php-0.conf ] || echo 'set $PHP_ENV 0;' > /www/server/web_conf/php/conf/enable-php-0.conf
    local d
    for d in $(ls /www/server/web_conf/nginx/vhost/*.conf 2>/dev/null | xargs -n1 basename | sed 's/\.conf$//'); do
        [ -f "/www/server/web_conf/nginx/rewrite/$d.conf" ] || echo "# ABao auto-created" > "/www/server/web_conf/nginx/rewrite/$d.conf"
    done
    # 校验并启动
    /www/server/openresty/nginx/sbin/nginx -t > /tmp/abao-nginx-t.log 2>&1 || { warn "nginx 配置校验失败：$(tail -5 /tmp/abao-nginx-t.log)"; return; }
    /etc/init.d/openresty start >/dev/null 2>&1 || /www/server/openresty/init.d/openresty start >/dev/null 2>&1 || true
    ok "openresty Web 服务已就绪（80 端口，站点可访问）"
}

# ---------- 10. 输出结果 ----------
get_public_ip() {
    local p
    # 腾讯云 metadata（内网可访问）→ ipify → 内网 IP 兜底
    p="$(curl -s --max-time 5 http://metadata.tencentyun.com/latest/meta-data/public-ipv4 2>/dev/null)"
    [ -n "$p" ] || p="$(curl -s --max-time 5 https://api.ipify.org 2>/dev/null)"
    [ -n "$p" ] || p="$(curl -s --max-time 5 https://ifconfig.me 2>/dev/null)"
    [ -n "$p" ] || p="$(hostname -I 2>/dev/null | awk '{print $1}')"
    echo "${p:-服务器IP}"
}
print_summary() {
    local ip md_user md_pass entry_code
    ip="$(get_public_ip)"
    entry_code="$(grep '^ABAO_ENTRY_CODE=' "$ABAO_DIR/.env" 2>/dev/null | cut -d= -f2)"
    # mdserver 账号密码：从安装日志提取（mdserver 装完会打印 |-username: / |-password:）
    md_user="$(grep -E '\|-username:' /tmp/mdserver-install.log 2>/dev/null | tail -1 | sed 's/.*username: *//' | tr -d '[:space:]')"
    md_pass="$(grep -E '\|-password:' /tmp/mdserver-install.log 2>/dev/null | tail -1 | sed 's/.*password: *//' | tr -d '[:space:]')"
    echo ""
    echo "================================================================"
    echo "   ABao 阿宝面板安装完成！"
    echo "----------------------------------------------------------------"
    if [ -n "$entry_code" ]; then
        echo "   面板地址:   http://${ip}:${APP_PORT}/${entry_code}"
        echo "   本地地址:   http://localhost:${APP_PORT}/${entry_code}"
        echo "   (随机码入口，直接访问 / 或 /abao/ 均 404/403；Coolify/mdserver 原登录入口已关闭)"
    else
        echo "   面板地址:   http://${ip}:${APP_PORT}/abao/index.html"
        echo "   本地地址:   http://localhost:${APP_PORT}/abao/index.html"
    fi
    echo ""
    if [ -f "$ABAO_DIR/.abao-credentials" ]; then
        echo "   --- 管理员账号（已存 $ABAO_DIR/.abao-credentials） ---"
        grep -E '管理员(邮箱|密码)' "$ABAO_DIR/.abao-credentials"
    else
        echo "   管理员邮箱: $([ -f "$ABAO_DIR/.env" ] && grep '^ROOT_USER_EMAIL=' "$ABAO_DIR/.env" | cut -d= -f2)"
        echo "   管理员密码: $([ -f "$ABAO_DIR/.env" ] && grep '^ROOT_USER_PASSWORD=' "$ABAO_DIR/.env" | cut -d= -f2)"
    fi
    echo ""
    echo "   --- 面板登录账号（重要：前端登录认 mdserver 账号） ---"
    if [ -n "$md_user" ] && [ -n "$md_pass" ]; then
        echo "   面板用户名: ${md_user}"
        echo "   面板密码:   ${md_pass}"
        echo "   （上述已写入 $ABAO_DIR/.abao-credentials）"
        {
            echo "面板用户名: ${md_user}"
            echo "面板密码:   ${md_pass}"
        } >> "$ABAO_DIR/.abao-credentials"
    else
        echo "   面板用户名/密码: 安装结束时 mdserver 脚本上方输出中的账号/密码（或运行 mw 查看）"
    fi
    echo ""
    echo "   自定义账号:    重装时可用环境变量 ABAO_ADMIN_EMAIL / ABAO_ADMIN_PASSWORD 指定"
    echo ""
    echo "   面板入口随机码: $entry_code（存 /data/coolify/source/.env ABAO_ENTRY_CODE；忘记可用 mw 或查看 .env）"
    echo ""
    echo "   CLI 命令:      ab 或 AB（等同宝塔 bt，管理面板信息/改密码/改端口等）"
    echo ""
    echo "   Web 服务:      面板「软件商店」→ 安装 openresty（网站/建站功能需要，后台实时查看日志）"
    echo ""
    echo "   常用命令:   mw                # mdserver 面板 CLI"
    echo "               docker ps         # 查看容器状态"
    echo "               tail -f /tmp/abao-install.log   # 安装日志"
    echo "================================================================"
}

main() {
    info "========== ABao 阿宝面板一键安装 v2.6.24（数据库备份闭环 + 权限固化） =========="
    require_root
    detect_os
    enable_ip_forward
    check_port "$APP_PORT"
    check_port "$MDSERVER_PORT"
    install_docker
    configure_docker_mirror
    fetch_source
    gen_env
    start_coolify
    deploy_frontend
    patch_nginx
    deploy_panel_api
    open_firewall
    install_mdserver
    inject_mysql_pwd
    setup_phpmyadmin_signon
    setup_openresty_reload
    setup_db_password_sync
    setup_site_deploy_fix
    setup_disk_fix
    setup_data_disk
    setup_obj_disk
    setup_disk_cache
    setup_obj_disk_route
    setup_site_delete_async
    ensure_backup_dir
    print_summary
    ok "全部完成！如需修改密码：面板「设置」中修改；数据库/Redis 密码见 $ABAO_DIR/.env"
}

# ---------- B03-49：phpMyAdmin signon 单点登录（数据库只能管理自己账号下的库） ----------
setup_phpmyadmin_signon() {
    local pma="/www/server/phpmyadmin/phpmyadmin_sjqemi"
    [ -d "$pma" ] || { info "phpMyAdmin 尚未安装（软件商店安装后重跑本段即可），跳过 signon 固化"; return; }
    info "固化 phpMyAdmin signon 隔离管理（B03-49，幂等）…"
    # 1) signon 跳板（面板「管理」→ signon.php → 容器 resolve → 专属账号会话）
    cp "$ABAO_DIR/panel-src/signon.php" "$pma/signon.php"
    chown www:www "$pma/signon.php" 2>/dev/null || true
    # 2) config.inc.php → auth_type=signon（幂等；移除 config 免密残留）
    local PYC="python3"
    command -v python3 >/dev/null 2>&1 || PYC="/www/server/mdserver-web/bin/python3"
    "$PYC" - <<'PYEOF'
import re, sys
p = '/www/server/phpmyadmin/phpmyadmin_sjqemi/config.inc.php'
s = open(p).read()
if "auth_type'] = 'signon'" in s:
    print('config.inc.php already signon')
    sys.exit(0)
s = re.sub(r"\$cfg\['Servers'\]\[\$i\]\['auth_type'\]\s*=\s*'config';",
           "$cfg['Servers'][$i]['auth_type'] = 'signon';", s)
s = re.sub(r"\n?\s*\$cfg\['Servers'\]\[\$i\]\['user'\]\s*=.*\n?", "\n", s)
s = re.sub(r"\n?\s*\$cfg\['Servers'\]\[\$i\]\['password'\]\s*=.*\n?", "\n", s)
if 'SignonSession' not in s:
    s = s.replace("$cfg['Servers'][$i]['auth_type'] = 'signon';",
                  "$cfg['Servers'][$i]['auth_type'] = 'signon';\n$cfg['Servers'][$i]['SignonSession'] = 'SignonSession';\n$cfg['Servers'][$i]['SignonURL'] = '/phpmyadmin/signon.php';")
open(p, 'w').write(s)
print('config.inc.php -> signon OK')
PYEOF
    # 3) 888 站点走 PHP 82 执行链（phpMyAdmin 是 PHP 程序，需 fastcgi 转发）
    if ! grep -q 'enable-php-82' /www/server/web_conf/nginx/vhost/phpmyadmin.conf 2>/dev/null; then
        sed -i 's|enable-php-0.conf|enable-php-82.conf|' /www/server/web_conf/nginx/vhost/phpmyadmin.conf 2>/dev/null || true
    fi
    [ -f /www/server/web_conf/php/conf/enable-php-82.conf ] || cat > /www/server/web_conf/php/conf/enable-php-82.conf <<'EOF'
location ~ [^/]\.php(/|$)
{
    try_files $uri =404;
    fastcgi_pass unix:/var/opt/remi/php82/run/php-fpm/www.sock;
    fastcgi_index index.php;
    include /www/server/openresty/nginx/conf/fastcgi_params;
    include /www/server/web_conf/php/pathinfo.conf;
}
EOF
    /www/server/openresty/nginx/sbin/nginx -t >/dev/null 2>&1 && /www/server/openresty/nginx/sbin/nginx -s reload >/dev/null 2>&1 || true
    ok "phpMyAdmin signon 已固化（仅能管理自己账号下的库）"
}
# ---------- B03-50a：openresty pid 文件修复（建站后新 vhost 立即生效，否则 808 等端口站 404） ----------
setup_openresty_reload() {
    local ng="/www/server/openresty/nginx"
    [ -f "$ng/sbin/nginx" ] || { info "openresty 尚未安装（软件商店安装后重跑本段即可），跳过 reload 固化"; return; }
    info "固化 openresty reload 链路（B03-50，幂等）…"
    # logs/nginx.pid 为空会导致 mdserver 建站后的 `nginx -s reload` 报 invalid PID 静默失败，
    # 新 vhost 永远不加载（表现为按端口访问 404、access/error 日志全空）。
    local master_pid
    master_pid="$(pgrep -f '/www/server/openresty/bin/openresty -c' | head -1)"
    if [ -n "$master_pid" ]; then
        echo "$master_pid" > "$ng/logs/nginx.pid"
    fi
    if [ -s "$ng/logs/nginx.pid" ]; then
        "$ng/sbin/nginx" -t >/dev/null 2>&1 && "$ng/sbin/nginx" -s reload >/dev/null 2>&1 \
            && ok "openresty pid 已修正并重载（建站 vhost 即时生效）" \
            || warn "openresty reload 失败，请手动检查 $ng/logs/nginx.pid"
    else
        warn "未找到 openresty master 进程，跳过（openresty 未运行）"
    fi
}

# ---------- B03-50b：一键部署建库密码同步（WP/Typecho/Nextcloud/Z-Blog 配置 → 面板记录文件） ----------
setup_db_password_sync() {
    [ -d /www/server/mdserver-web/scripts ] || { info "mdserver 未安装，跳过密码同步固化"; return; }
    info "固化一键部署数据库密码同步（B03-50，幂等）…"
    cat > /www/server/mdserver-web/scripts/abao_dbsync.py <<'ABAODBSYNC'
#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""ABao dbsync：扫描站点 CMS 配置，把一键部署建库密码同步进面板记录文件（幂等，不覆盖已有记录）
覆盖：WordPress wp-config.php / Typecho config.inc.php / Nextcloud config/config.php / Z-Blog zb_users/c_option.php
容器面板记录文件：/var/www/html/storage/app/panel/host-db-passwords.json
"""
import json, os, re, glob, subprocess, sys

PANEL_FILE = '/var/www/html/storage/app/panel/host-db-passwords.json'
WWWROOT = '/www/wwwroot'

def extract_wp(path):
    try:
        s = open(path, encoding='utf-8', errors='ignore').read()
    except Exception:
        return None
    m = re.search(r"define\(\s*['\"]DB_NAME['\"]\s*,\s*['\"]([^'\"]+)['\"]", s)
    p = re.search(r"define\(\s*['\"]DB_PASSWORD['\"]\s*,\s*['\"]([^'\"]+)['\"]", s)
    if m and p:
        return m.group(1).strip(), p.group(1).strip()
    return None

def extract_typecho(path):
    try:
        s = open(path, encoding='utf-8', errors='ignore').read()
    except Exception:
        return None
    db = re.search(r"['\"]database['\"]\s*=>\s*['\"]([^'\"]+)['\"]", s)
    pw = re.search(r"['\"]password['\"]\s*=>\s*['\"]([^'\"]+)['\"]", s)
    if db and pw:
        return db.group(1).strip(), pw.group(1).strip()
    return None

def extract_nextcloud(path):
    try:
        s = open(path, encoding='utf-8', errors='ignore').read()
    except Exception:
        return None
    db = re.search(r"['\"]dbname['\"]\s*=>\s*['\"]([^'\"]+)['\"]", s)
    pw = re.search(r"['\"]dbpassword['\"]\s*=>\s*['\"]([^'\"]+)['\"]", s)
    if db and pw:
        return db.group(1).strip(), pw.group(1).strip()
    return None

def extract_zblog(path):
    try:
        s = open(path, encoding='utf-8', errors='ignore').read()
    except Exception:
        return None
    db = re.search(r"['\"]ZC_DATABASE_NAME['\"]\s*=>\s*['\"]([^'\"]+)['\"]", s)
    pw = re.search(r"['\"]ZC_DATABASE_PASSWORD['\"]\s*=>\s*['\"]([^'\"]+)['\"]", s)
    if db and pw:
        return db.group(1).strip(), pw.group(1).strip()
    return None

def main():
    found = {}
    for d in glob.glob(WWWROOT + '/*'):
        if not os.path.isdir(d):
            continue
        for probe, fn in [
            (os.path.join(d, 'wp-config.php'), extract_wp),
            (os.path.join(d, 'config.inc.php'), extract_typecho),
            (os.path.join(d, 'config', 'config.php'), extract_nextcloud),
            (os.path.join(d, 'zb_users', 'c_option.php'), extract_zblog),
        ]:
            if os.path.isfile(probe):
                r = fn(probe)
                if r and r[0] and r[1]:
                    found[r[0]] = r[1]
    try:
        cur_raw = subprocess.check_output(
            ['docker', 'exec', 'coolify', 'sh', '-c', 'cat %s 2>/dev/null || echo {}' % PANEL_FILE],
            timeout=15).decode('utf-8', 'ignore') or '{}'
        cur = json.loads(cur_raw) if cur_raw.strip() else {}
    except Exception:
        cur = {}
    # ABao B03-52: 兼容历史 []（PHP json_decode('{}',true) 覆盖导致），统一按 dict 处理
    if not isinstance(cur, dict):
        cur = {}
    changed = 0
    for db, pwd in found.items():
        if db not in cur:
            cur[db] = pwd
            changed += 1
    if changed > 0:
        payload = json.dumps(cur, ensure_ascii=False)
        proc = subprocess.Popen(
            ['docker', 'exec', '-i', 'coolify', 'sh', '-c', 'umask 077; cat > %s' % PANEL_FILE],
            stdin=subprocess.PIPE, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        proc.communicate(payload.encode('utf-8'))
    print('dbsync: total=%d new=%d' % (len(found), changed), file=sys.stderr)

if __name__ == '__main__':
    main()
ABAODBSYNC
    chmod 755 /www/server/mdserver-web/scripts/abao_dbsync.py
    /www/server/mdserver-web/bin/python3 -m py_compile /www/server/mdserver-web/scripts/abao_dbsync.py 2>/dev/null \
        || python3 -c "import ast; ast.parse(open('/www/server/mdserver-web/scripts/abao_dbsync.py').read())" \
        || warn "abao_dbsync.py 语法检查失败"
    # crontab 幂等：每分钟同步一次（覆盖后续新部署的网站）
    (crontab -l 2>/dev/null | grep -v 'abao_dbsync'; echo '*/1 * * * * /usr/bin/python3 /www/server/mdserver-web/scripts/abao_dbsync.py > /dev/null 2>&1') | crontab -
    # 立即执行一次，把现有站点的库密码补进面板记录文件
    /usr/bin/python3 /www/server/mdserver-web/scripts/abao_dbsync.py >/dev/null 2>&1 || \
        /www/server/mdserver-web/bin/python3 /www/server/mdserver-web/scripts/abao_dbsync.py >/dev/null 2>&1 || true
    ok "数据库密码同步已固化（一键部署库密码面板可显示，B03-50）"
}

# ---------- B03-51：一键部署目录修正 + 删站防御（自定义目录名站点部署失败/删站 500） ----------
setup_site_deploy_fix() {
    [ -d /www/server/mdserver-web/web ] || { info "mdserver 未安装，跳过站点部署修复固化"; return; }
    info "固化站点一键部署/删站修复（B03-51，幂等）…"
    local PYC="python3"
    command -v python3 >/dev/null 2>&1 || PYC="/www/server/mdserver-web/bin/python3"
    "$PYC" - <<'PYEOF'
import sys
# 1) deploy_install：站点根目录按 sites.path（站点名可能不等于自定义目录名，如 siteName=ccbbc.ccc、目录=bbcc）
p1 = '/www/server/mdserver-web/web/admin/site/site.py'
s = open(p1, encoding='utf-8').read()
old1 = """    # 站点根目录
    root_dir = '/www/wwwroot/' + site_name
    if not os.path.isdir(root_dir):
        return mw.returnData(False, '站点目录不存在，请先创建站点')"""
new1 = """    # 站点根目录（ABao: 按 sites.path，站点名可能不等于自定义目录名）
    site_row = thisdb.getSitesByName(site_name)
    root_dir = site_row['path'] if site_row else '/www/wwwroot/' + site_name
    if not os.path.isdir(root_dir):
        return mw.returnData(False, '站点目录不存在，请先创建站点')"""
if old1 in s:
    s = s.replace(old1, new1, 1)
    open(p1, 'w', encoding='utf-8').write(s)
    print('deploy_install root_dir -> sites.path OK')
elif 'site_row = thisdb.getSitesByName' in s:
    print('deploy_install already patched')
else:
    print('WARN deploy_install anchor not found (skip)')

# 2) delete：site_id 不存在时防御（不再 500，返回友好提示；前端传了无效 id 或站点已被删）
p2 = '/www/server/mdserver-web/web/utils/site.py'
s = open(p2, encoding='utf-8').read()
old2 = """    def delete(self, site_id, path):
        info = thisdb.getSitesById(site_id)
        webname = info['name']"""
new2 = """    def delete(self, site_id, path):
        info = thisdb.getSitesById(site_id)
        if not info:
            return mw.returnData(False, '站点不存在或已被删除')
        webname = info['name']"""
if old2 in s:
    s = s.replace(old2, new2, 1)
    open(p2, 'w', encoding='utf-8').write(s)
    print('delete None-guard OK')
elif "if not info:" in s and '站点不存在或已被删除' in s:
    print('delete already patched')
else:
    print('WARN delete anchor not found (skip)')
PYEOF
    /www/server/mdserver-web/bin/python3 -m py_compile \
        /www/server/mdserver-web/web/admin/site/site.py \
        /www/server/mdserver-web/web/utils/site.py 2>/dev/null \
        && { systemctl restart abao-mdserver.service >/dev/null 2>&1 || service mdserver-web restart >/dev/null 2>&1 || true; \
             ok "站点一键部署/删站修复已固化（B03-51）"; } \
        || warn "site.py 语法检查失败，请手动核对（部署/删站修复未生效）"
}

# ---------- B03-52a/53：磁盘容量显示（本地块设备保留；对象存储 cosfs/fuse 单独显示为「对象存储」+ du 真实用量；overlay/网络挂载排除） ----------
setup_disk_fix() {
    [ -f /www/server/mdserver-web/web/utils/system/main.py ] || { info "mdserver 未安装，跳过磁盘显示修复固化"; return; }
    info "固化磁盘容量显示（B03-52/53，幂等）…"
    local PYC="python3"
    command -v python3 >/dev/null 2>&1 || PYC="/www/server/mdserver-web/bin/python3"
    "$PYC" - <<'PYEOF'
import sys
p = '/www/server/mdserver-web/web/utils/system/main.py'
s = open(p, encoding='utf-8').read()
old = """        disk = tmp.split()
        if len(disk) < 5:
            continue
        if disk[1].find('M') != -1:
            continue
        if disk[1].find('K') != -1:
            continue
        if len(disk[5].split('/')) > 4:
            continue
        if disk[5] in cuts:
            continue"""
new = """        disk = tmp.split()
        if len(disk) < 5:
            continue
        # ABao B03-52/53: 本地块设备 /dev/* 保留；cosfs/fuse 对象存储单独保留显示（du 统计真实用量）；overlay/网络挂载等伪磁盘排除
        is_obj = ('cosfs' in disk[0] or 'fuse' in disk[0])
        if not disk[0].startswith('/dev/') and not is_obj:
            continue
        if disk[1].find('M') != -1:
            continue
        if disk[1].find('K') != -1:
            continue
        if len(disk[5].split('/')) > 4:
            continue
        if disk[5] in cuts:
            continue"""
if old in s:
    s = s.replace(old, new, 1)
    open(p, 'w', encoding='utf-8').write(s)
    print('disk base OK')
elif "is_obj = ('cosfs' in disk[0]" in s:
    print('disk base already patched')
else:
    print('WARN disk base anchor not found (skip)')
# 2) 对象存储行：容量=df 值、已用=du 实际占用、type=对象存储
old2 = """        arr = {}
        arr['path'] = disk[5]
        tmp1 = [disk[1], disk[2], disk[3], disk[4]]
        arr['size'] = tmp1
        arr['inodes'] = [inodes[1], inodes[2], inodes[3], inodes[4]]

        if current_os == 'darwin':
            arr['type'] = tmp_disk_type
        else:
            arr['type'] = getDiskType(disk[0])
        diskInfo.append(arr)"""
new2 = """        arr = {}
        arr['path'] = disk[5]
        tmp1 = [disk[1], disk[2], disk[3], disk[4]]
        if is_obj:
            # 对象存储 df 无真实用量（cosfs 报 256T/0），用 du 实际占用字节填充
            used_b = mw.execShell('du -sb %s 2>/dev/null' % disk[5])[0].split('\\t')[0].strip()
            try:
                used_b = int(used_b)
            except Exception:
                used_b = 0
            total_b = 256 * 1024 ** 4
            if total_b > 0:
                used_h = used_b / (1024 ** 3)
                total_h = total_b / (1024 ** 3)
                pct = round(used_b * 100.0 / total_b, 1)
                tmp1 = ['%.1fT' % (total_h / 1024), '%.2fG' % used_h, '%.2fG' % (total_h - used_h), '%.1f%%' % pct]
        arr['size'] = tmp1
        arr['inodes'] = [inodes[1], inodes[2], inodes[3], inodes[4]]

        if current_os == 'darwin':
            arr['type'] = tmp_disk_type
        elif is_obj:
            arr['type'] = '对象存储'
        else:
            arr['type'] = getDiskType(disk[0])
        diskInfo.append(arr)"""
if old2 in s:
    s = s.replace(old2, new2, 1)
    open(p, 'w', encoding='utf-8').write(s)
    print('obj storage display OK')
elif "arr['type'] = '对象存储'" in s:
    print('obj storage display already patched')
else:
    print('WARN obj storage anchor not found (skip)')
PYEOF
    /www/server/mdserver-web/bin/python3 -m py_compile /www/server/mdserver-web/web/utils/system/main.py 2>/dev/null \
        && { systemctl restart abao-mdserver.service >/dev/null 2>&1 || service mdserver-web restart >/dev/null 2>&1 || true; \
             ok "磁盘容量显示已固化（本地盘 + 对象存储 /host-data 用量，B03-52/53）"; } \
        || warn "main.py 语法检查失败（磁盘显示未生效）"
}

# ---------- B03-52b/53：数据盘检测与安装目录迁移（本地数据盘优先；无本地盘但有对象存储 cosfs → 网站目录 wwwroot 迁到对象存储；软件/数据库保留系统盘） ----------
setup_data_disk() {
    info "检测数据盘（本地块设备优先；否则对象存储挂载用于网站数据；均无则用系统盘）…"
    local data_mnt="" best_size=0 mnt="" fs="" size="" is_obj_mnt=""
    while read -r fs size _ _ _ mnt; do
        [ -n "$fs" ] || continue
        local obj=""
        case "$fs" in *cosfs*|*fuse*) obj=1 ;; esac
        case "$fs" in /dev/*) ;; *) [ -n "$obj" ] || continue ;; esac
        [ -n "$mnt" ] || continue
        [ "$mnt" = "/" ] && continue
        case "$mnt" in /boot*|/dev*|/run*|/proc|/sys) continue ;; esac
        if [ -z "$obj" ]; then
            local num=0 unit=""
            num="$(echo "$size" | sed 's/[^0-9.]//g')"
            unit="$(echo "$size" | sed 's/[0-9.]//g')"
            case "$unit" in
                T) num=$(awk "BEGIN{print $num*1024}") ;;
                G) num=$(awk "BEGIN{print $num}") ;;
                M) num=$(awk "BEGIN{print $num/1024}") ;;
                *) continue ;;
            esac
            if awk "BEGIN{exit !($num>$best_size)}"; then
                best_size=$num; data_mnt="$mnt"
            fi
        else
            # 对象存储（cosfs/fuse）：df 容量 256T 无意义，仅当无本地数据盘时作为网站目录候选
            [ -z "$data_mnt" ] && data_mnt="$mnt" && is_obj_mnt=1
        fi
    done < <(df -h -P 2>/dev/null | tail -n +2)
    if [ -z "$data_mnt" ]; then
        ok "未检测到数据盘，网站/软件安装继续使用系统盘（/www）"
        return 0
    fi
    if [ -n "$is_obj_mnt" ]; then
        info "未检测到本地数据盘，检测到对象存储挂载：$data_mnt。将网站目录 /www/wwwroot 迁移到对象存储（软件/MySQL 保留系统盘，数据库不适合对象存储）…"
        if [ -L /www/wwwroot ]; then
            ok "/www/wwwroot 已是指向对象存储的软链（$(readlink /www/wwwroot)），跳过迁移"
            return 0
        fi
        [ -d "$data_mnt" ] || { warn "对象存储挂载点 $data_mnt 不存在，跳过迁移"; return 0; }
        mkdir -p "$data_mnt/www/wwwroot"
        if [ -d /www/wwwroot ]; then
            cp -a /www/wwwroot/. "$data_mnt/www/wwwroot/" 2>/dev/null || rsync -a /www/wwwroot/ "$data_mnt/www/wwwroot/" 2>/dev/null || { warn "网站目录迁移失败，跳过"; return 0; }
            mv /www/wwwroot /www/wwwroot.orig.abao 2>/dev/null || true
        fi
        ln -s "$data_mnt/www/wwwroot" /www/wwwroot
        chown -R www:www "$data_mnt/www/wwwroot" 2>/dev/null || true
        chmod 755 "$data_mnt/www/wwwroot" 2>/dev/null || true  # B03-56: chown 失败兜底，保证 www 可删站/删文件
        ok "网站目录 /www/wwwroot 已迁移到对象存储 $data_mnt/www/wwwroot（软链），新建网站默认落对象存储；软件/MySQL 留在系统盘"
        return 0
    fi
    info "检测到本地数据盘挂载点：$data_mnt（约 ${best_size}G），将 /www 数据迁移到数据盘…"
    if [ -L /www ]; then
        ok "/www 已是指向数据盘的软链（$(readlink /www)），跳过迁移"
        return 0
    fi
    if [ ! -d "$data_mnt" ]; then
        warn "数据盘挂载点 $data_mnt 不存在，跳过迁移（请先挂载磁盘）"
        return 0
    fi
    # 迁移：mkdir + mv（保留原 /www 为空占位 → 软链），mdserver 所有路径（/www/wwwroot 等）不变
    mkdir -p "$data_mnt/www"
    if [ -d /www ]; then
        # 停相关服务再迁移更稳；此处仅拷贝+软链（不破坏运行中进程句柄）
        cp -a /www/. "$data_mnt/www/" 2>/dev/null || rsync -a /www/ "$data_mnt/www/" 2>/dev/null || { warn "/www 迁移失败，跳过"; return 0; }
        mv /www /www.orig.abao 2>/dev/null || true
    fi
    ln -s "$data_mnt/www" /www
    chown -R www:www "$data_mnt/www" 2>/dev/null || true
    ok "/www 已迁移到数据盘 $data_mnt/www（软链），后续网站/软件安装默认落在数据盘"
}

# ---------- B03-56：对象存储真实容量配置（cosfs df 报 256T 无意义；面板按真实配额显示，如 100G） ----------
setup_obj_disk() {
    local obj_mnt="" fs="" mnt=""
    while read -r fs _ _ _ _ mnt; do
        case "$fs" in *cosfs*|*fuse*) obj_mnt="$mnt"; break ;; esac
    done < <(df -h -P 2>/dev/null | tail -n +2)
    [ -n "$obj_mnt" ] || { info "未检测到对象存储挂载，跳过容量配置（面板对象存储将显示 cosfs 默认 256T，可在挂载后重跑本脚本配置）"; return 0; }
    local conf="/www/server/mdserver-web/data/obj_disk.conf"
    if [ -f "$conf" ]; then
        ok "对象存储容量配置已存在：$(cat "$conf") GB（$conf）"
        return 0
    fi
    info "检测到对象存储挂载：$obj_mnt。cosfs 默认显示 256T，无法反映真实配额。"
    local gb=""
    printf "请输入对象存储真实容量（GB，回车默认 100）：" 
    read -r gb
    case "$gb" in ''|*[!0-9.]*) gb=100 ;; esac
    echo "$gb" > "$conf"
    ok "对象存储容量已配置：${gb} GB（$conf，面板磁盘分区将显示真实容量）"
}

# ---------- B03-55：disk_info 对象存储 du 缓存（对象存储全量 du 遍历 >20s，前端 20s 超时导致磁盘区空白/系统盘 --/--） ----------
setup_disk_cache() {
    [ -f /www/server/mdserver-web/web/utils/system/main.py ] || { info "mdserver 未安装，跳过 du 缓存固化"; return; }
    info "固化 disk_info 对象存储 du 缓存（B03-55，幂等）…"
    local PYC="python3"
    command -v python3 >/dev/null 2>&1 || PYC="/www/server/mdserver-web/bin/python3"
    "$PYC" - <<'PYEOF'
import sys
p = '/www/server/mdserver-web/web/utils/system/main.py'
s = open(p, encoding='utf-8').read()
if '_duUsageCached' in s:
    print('du cache already patched')
    sys.exit(0)
# 1) 在 getDiskInfo 前插入 _duUsageCached（600s 缓存；du 结果写 /tmp/abao_du_cache.json）
anchor = 'def getDiskInfo():'
func = '''def _duUsageCached(path, ttl=600):
    # ABao B03-55: 对象存储 du 遍历慢（>20s），结果缓存 ttl 秒，避免每次 disk_info 都全量 du
    import json as _json, time as _time
    cache_file = '/tmp/abao_du_cache.json'
    try:
        if os.path.exists(cache_file):
            cache = _json.load(open(cache_file))
        else:
            cache = {}
    except Exception:
        cache = {}
    now = _time.time()
    hit = cache.get(path)
    if hit and now - hit.get('t', 0) < ttl:
        return hit['b']
    b = mw.execShell('du -sb %s 2>/dev/null' % path)[0].split('\\t')[0].strip()
    try:
        b = int(b)
    except Exception:
        b = 0
    cache[path] = {'t': now, 'b': b}
    try:
        _json.dump(cache, open(cache_file, 'w'))
    except Exception:
        pass
    return b

'''
if anchor in s:
    s = s.replace(anchor, func + anchor, 1)
    print('cache func inserted')
else:
    print('WARN getDiskInfo anchor not found (skip func)')
# 2) du 直调替换为缓存调用（兼容 B03-52/53 已打过的 du 版）
old_du = """            used_b = mw.execShell('du -sb %s 2>/dev/null' % disk[5])[0].split('\\t')[0].strip()
            try:
                used_b = int(used_b)
            except Exception:
                used_b = 0"""
new_du = """            used_b = _duUsageCached(disk[5])"""
if old_du in s:
    s = s.replace(old_du, new_du, 1)
    print('du call replaced')
elif '_duUsageCached(disk[5])' in s:
    print('du call already replaced')
else:
    print('WARN du call anchor not found (skip)')
# 3) B03-56：对象存储容量读配置（obj_disk.conf，GB），无配置回退 256T；<1T 显示 G
if 'def _objTotalBytes' not in s:
    anchor2 = 'def _duUsageCached(path, ttl=600):'
    func2 = '''def _objTotalBytes():
    # ABao B03-56: 对象存储真实容量（GB）读 /www/server/mdserver-web/data/obj_disk.conf；无配置回退 cosfs 上限 256T
    try:
        cf = '/www/server/mdserver-web/data/obj_disk.conf'
        if os.path.exists(cf):
            gb = float(open(cf).read().strip())
            if gb > 0:
                return int(gb * 1024 ** 3)
    except Exception:
        pass
    return 256 * 1024 ** 4

'''
    if anchor2 in s:
        s = s.replace(anchor2, func2 + anchor2, 1)
        print('objTotalBytes func inserted')
    else:
        print('WARN duUsage anchor not found (skip objTotalBytes)')
old_tb = """            total_b = 256 * 1024 ** 4  # cosfs df 上限 256T"""
new_tb = """            total_b = _objTotalBytes()  # B03-56: 容量读配置（GB），无配置回退 256T"""
if old_tb in s:
    s = s.replace(old_tb, new_tb, 1)
    print('total_b source replaced')
elif '_objTotalBytes()' in s:
    print('total_b already uses config')
else:
    print('WARN total_b anchor not found (skip)')
old_fmt = """                tmp1 = ['%.1fT' % (total_h / 1024), '%.2fG' % used_h, '%.2fG' % (total_h - used_h), '%.1f%%' % pct]"""
new_fmt = """                if total_b >= 1024 ** 4:
                    size_txt = '%.1fT' % (total_h / 1024)
                else:
                    size_txt = '%.0fG' % total_h
                tmp1 = [size_txt, '%.2fG' % used_h, '%.2fG' % (total_h - used_h), '%.1f%%' % pct]"""
if old_fmt in s:
    s = s.replace(old_fmt, new_fmt, 1)
    print('format replaced (G for <1T)')
elif 'size_txt' in s:
    print('format already G-capable')
else:
    print('WARN format anchor not found (skip)')
open(p, 'w', encoding='utf-8').write(s)
PYEOF
    /www/server/mdserver-web/bin/python3 -m py_compile /www/server/mdserver-web/web/utils/system/main.py 2>/dev/null \
        && { systemctl restart abao-mdserver.service >/dev/null 2>&1 || service mdserver-web restart >/dev/null 2>&1 || true; \
             ok "disk_info du 缓存已固化（对象存储用量 600s 缓存，磁盘区不再空白，B03-55）"; } \
        || warn "main.py 语法检查失败（du 缓存未生效）"
}

# ---------- B03-57：对象存储容量设置入口路由（面板「设置→对象存储容量」可视化配置任意容量，如 50/60/100/200G） ----------
setup_obj_disk_route() {
    [ -f /www/server/mdserver-web/web/admin/setting/setting.py ] || { info "mdserver 未安装，跳过对象存储容量设置路由固化"; return; }
    info "固化对象存储容量设置路由（/setting/obj_disk，B03-57，幂等）…"
    /www/server/mdserver-web/bin/python3 - <<'PYEOF'
import sys
p = '/www/server/mdserver-web/web/admin/setting/setting.py'
s = open(p, encoding='utf-8').read()
if '# ABao B03-57' in s:
    print('obj_disk route already patched')
    sys.exit(0)
anchor = '# 设置安全入口'
route = '''# ABao B03-57: 对象存储容量配置（面板设置入口；GET/空提交读取，POST 写入 obj_disk.conf，支持任意容量如 50/60/100/200）
@blueprint.route('/obj_disk', endpoint='obj_disk', methods=['GET', 'POST'])
@panel_login_required
def obj_disk():
    conf = '/www/server/mdserver-web/data/obj_disk.conf'
    gb = str(request.form.get('gb', '')).strip()
    if not gb:
        cur = ''
        if os.path.exists(conf):
            cur = open(conf).read().strip()
        return mw.returnData(True, 'OK', {'gb': cur, 'conf': conf})
    try:
        g = float(gb)
    except Exception:
        return mw.returnData(False, '容量格式错误，请输入数字（GB）')
    if g <= 0:
        return mw.returnData(False, '容量必须大于 0')
    try:
        open(conf, 'w').write(str(gb))
    except Exception as e:
        return mw.returnData(False, '写入配置失败: %s' % str(e))
    try:
        if os.path.exists('/tmp/abao_du_cache.json'):
            os.remove('/tmp/abao_du_cache.json')
    except Exception:
        pass
    return mw.returnData(True, '对象存储容量已更新为 %s GB' % gb)


'''
if anchor in s:
    s = s.replace(anchor, route + anchor, 1)
    open(p, 'w', encoding='utf-8').write(s)
    print('obj_disk route inserted')
else:
    print('WARN anchor not found')
PYEOF
    /www/server/mdserver-web/bin/python3 -m py_compile /www/server/mdserver-web/web/admin/setting/setting.py 2>/dev/null \
        && { systemctl restart abao-mdserver.service >/dev/null 2>&1 || service mdserver-web restart >/dev/null 2>&1 || true; \
             ok "对象存储容量设置路由已固化（面板设置可配置任意容量，B03-57）"; } \
        || warn "setting.py 语法检查失败（obj_disk 路由未生效）"
}

# ---------- B03-58：站点删除异步化 + PHP 版本接口兜底（对象存储 cosfs rm 卡 D 状态阻塞接口） ----------
setup_site_delete_async() {
    [ -f /www/server/mdserver-web/web/utils/site.py ] || { info "mdserver 未安装，跳过站点删除异步固化"; return; }
    info "固化站点删除异步删除 + PHP 版本兜底（B03-58，幂等）…"
    /www/server/mdserver-web/bin/python3 - <<'PYEOF'
import sys
p = '/www/server/mdserver-web/web/utils/site.py'
s = open(p, encoding='utf-8').read()
changed = False
# 1) delete() 目录删除改后台异步（cosfs rm 卡 D 状态会阻塞面板接口）
if 'ABao B03-58' not in s:
    old = """            if web_root_path and web_root_path != mw.getWwwDir():
                mw.execShell('rm -rf ' + web_root_path)"""
    new = """            if web_root_path and web_root_path != mw.getWwwDir():
                # ABao B03-58: 对象存储 cosfs 目录 rm 可能卡 D 状态阻塞接口（面板无响应）；
                # 改后台异步删除：接口秒回、站点立即消失，目录后台慢慢清（不阻塞面板）
                try:
                    mw.execShell('nohup rm -rf "%s" >/dev/null 2>&1 &' % web_root_path, timeout=5)
                except Exception:
                    pass"""
    if old in s:
        s = s.replace(old, new, 1)
        changed = True
        print('delete async patched')
    else:
        print('WARN delete anchor not found')
# 2) getSitePhpVersion None 兜底（vhost conf 缺失 re.search(None) 500）
if 'ABao B03-58: vhost' not in s:
    old2 = """        conf = mw.readFile(self.getHostConf(siteName))
        rep = r"enable-php-(.*)\\.conf"
        find_php_cnf = re.search(rep, conf)"""
    new2 = """        conf = mw.readFile(self.getHostConf(siteName))
        if not conf:
            # ABao B03-58: vhost 配置缺失时兜底返回 00（原实现 re.search(None) 直接 500，列表页崩溃）
            return {'phpversion': '00'}
        rep = r"enable-php-(.*)\\.conf"
        find_php_cnf = re.search(rep, conf)"""
    if old2 in s:
        s = s.replace(old2, new2, 1)
        changed = True
        print('phpversion guard patched')
    else:
        print('WARN phpversion anchor not found')
if changed:
    open(p, 'w', encoding='utf-8').write(s)
    print('site.py written')
PYEOF
    /www/server/mdserver-web/bin/python3 -m py_compile /www/server/mdserver-web/web/utils/site.py 2>/dev/null \
        && { systemctl restart abao-mdserver.service >/dev/null 2>&1 || service mdserver-web restart >/dev/null 2>&1 || true; \
             ok "站点删除异步化已固化（对象存储大目录删除不阻塞面板，B03-58）"; } \
        || warn "site.py 语法检查失败（删除异步未生效）"
}

# ---------- B03-43c：宿主 MySQL 密码注入（面板数据库管理直连用） ----------
inject_mysql_pwd() {
    # MySQL 未安装则跳过（安装后需重跑：bash "$ABAO_DIR/abao-tools.sh" fixmysql 或按日志命令手动注入）
    local sock="/www/server/mysql-community/mysql.sock"
    local my="/www/server/mysql-community/bin/mysql"
    local dbf="/www/server/mysql-community/mysql.db"
    [ -S "$sock" ] || { info "MySQL 尚未安装，跳过面板数据库直连密码注入（安装 MySQL 后按二开日志 B03-43c 手动注入一次即可）"; return; }
    info "注入 MySQL 密码到 panel-api（B03-43c 面板数据库管理直连）…"
    local pwd
    pwd="$(python3 -c "import sqlite3;c=sqlite3.connect('$dbf');print(c.execute('select value from config where id=1').fetchone()[0])" 2>/dev/null)"
    [ -n "$pwd" ] || { warn "无法读取 MySQL root 密码（sqlite 读取失败），跳过注入"; return; }
    docker exec coolify sed -i "s|'mysql_password' => env('PANEL_MYSQL_PASSWORD', ''),|'mysql_password' => '$pwd',|" /var/www/html/config/panel-api.php
    docker exec coolify grep -c "$pwd" /var/www/html/config/panel-api.php >/dev/null 2>&1 \
        && ok "MySQL 密码已注入 panel-api" \
        || warn "MySQL 密码注入失败，请手动检查 config/panel-api.php"
    docker exec coolify php artisan config:clear >/dev/null 2>&1 || true
}

# ---------- B03-43d：备份持久目录权限（面板数据库备份用，容器 www-data 需可写） ----------
ensure_backup_dir() {
    info "准备数据库备份持久目录…"
    mkdir -p /data/coolify/backups
    chown -R 9999:9999 /data/coolify/backups
    docker exec coolify mkdir -p /var/www/html/storage/app/backups >/dev/null 2>&1 || true
    docker exec coolify chown -R www-data:www-data /var/www/html/storage/app/backups >/dev/null 2>&1 || true
    ok "备份目录已就绪（/data/coolify/backups，持久挂载，容器重建不丢失）"
}

main "$@"
