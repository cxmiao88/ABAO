#!/usr/bin/env bash
# ============================================================================
#  ABao 阿宝面板 - 一键安装脚本
#  ---------------------------------------------------------------------------
#  用法（root 用户执行）：
#    curl -fsSL https://raw.githubusercontent.com/cxmiao88/ABAO/main/install.sh | bash
#
#  功能：环境检测 → Docker 安装 → 拉取 ABao 源码 → 生成安全配置 →
#        启动 Coolify(8000) → 部署前端面板 → 安装 mdserver-web(48700) →
#        输出访问地址与初始账号。
#  特性：零凭据（仓库内不含任何默认密码），安装时交互设置/自动生成。
#  支持：Ubuntu / Debian / CentOS / Rocky / AlmaLinux（x86_64 / arm64）
# ============================================================================
set -euo pipefail

# ---------- 全局常量 ----------
ABAO_REPO="https://github.com/cxmiao88/ABAO.git"
ABAO_BRANCH="main"
ABAO_DIR="/data/coolify/source"          # Coolify/ABao 源码目录
FRONTEND_ZIP_URL="https://github.com/cxmiao88/ABAO/releases/latest/download/abao-frontend.zip"
APP_PORT="${APP_PORT:-8000}"
MDSERVER_PORT="${MDSERVER_PORT:-48700}"

# 颜色输出
C_INFO='\033[1;36m'; C_OK='\033[1;32m'; C_WARN='\033[1;33m'; C_ERR='\033[1;31m'; C_END='\033[0m'
info()  { echo -e "${C_INFO}[ABao]${C_END} $*"; }
ok()    { echo -e "${C_OK}[ OK ]${C_END} $*"; }
warn()  { echo -e "${C_WARN}[WARN]${C_END} $*"; }
err()   { echo -e "${C_ERR}[ERR ]${C_END} $*" >&2; }
die()   { err "$*"; exit 1; }

# ---------- 1. 前置检查 ----------
require_root() {
    [ "$(id -u)" -eq 0 ] || die "请使用 root 用户执行：sudo -i 后重新运行本脚本"
}
detect_os() {
    if   [ -f /etc/os-release ]; then . /etc/os-release; OS_ID="$ID"; OS_VER="$VERSION_ID"
    elif [ -f /etc/redhat-release ]; then OS_ID="rhel"
    else die "无法识别操作系统，请手动安装 Docker 后重试"
    fi
    case "$OS_ID" in
        ubuntu|debian|centos|rhel|rocky|almalinux|kylin|openeuler) : ;;
        *) warn "系统 $OS_ID 未在官方支持列表，继续尝试（建议 Ubuntu 22.04+/Debian 12+）" ;;
    esac
    ARCH="$(uname -m)"; case "$ARCH" in x86_64) ARCH="amd64" ;; aarch64) ARCH="arm64" ;; esac
    MEM_MB="$(awk '/MemTotal/{print int($2/1024)}' /proc/meminfo)"
    [ "$MEM_MB" -ge 2048 ] || warn "内存仅 ${MEM_MB}MB，推荐 ≥2GB（Coolify + mdserver 需要）"
    info "系统：$OS_ID $OS_VER ($ARCH)  内存：${MEM_MB}MB"
}
check_port() {
    local p="$1"
    if command -v ss >/dev/null 2>&1; then
        ss -ltn | awk '{print $4}' | grep -qE "[:.]${p}$" && die "端口 $p 已被占用，请先释放或修改配置"
    elif command -v netstat >/dev/null 2>&1; then
        netstat -ltn | awk '{print $4}' | grep -qE "[:.]${p}$" && die "端口 $p 已被占用，请先释放或修改配置"
    fi
}

# ---------- 2. Docker 安装 ----------
install_docker() {
    if command -v docker >/dev/null 2>&1; then
        docker info >/dev/null 2>&1 || die "Docker 已安装但守护进程不可用，请检查（systemctl start docker）"
        ok "Docker 已安装：$(docker --version)"
    else
        info "正在安装 Docker…"
        if command -v curl >/dev/null 2>&1; then
            curl -fsSL https://get.docker.com | sh
        else
            wget -qO- https://get.docker.com | sh
        fi
        systemctl enable --now docker 2>/dev/null || true
        command -v docker >/dev/null 2>&1 || die "Docker 安装失败，请手动安装后重试"
        ok "Docker 安装完成：$(docker --version)"
    fi
    if ! docker compose version >/dev/null 2>&1; then
        info "正在安装 Docker Compose 插件…"
        mkdir -p /usr/local/lib/docker/cli-plugins
        curl -fsSL "https://github.com/docker/compose/releases/latest/download/docker-compose-${OS_ID}-$(uname -m)" -o /usr/local/lib/docker/cli-plugins/docker-compose 2>/dev/null \
          || curl -fsSL "https://github.com/docker/compose/releases/latest/download/docker-compose-linux-${ARCH}" -o /usr/local/lib/docker/cli-plugins/docker-compose
        chmod +x /usr/local/lib/docker/cli-plugins/docker-compose
        docker compose version >/dev/null 2>&1 || die "Docker Compose 安装失败"
    fi
    ok "Docker Compose：$(docker compose version --short)"
}

# ---------- 3. 拉取源码 ----------
fetch_source() {
    if [ -d "$ABAO_DIR/.git" ]; then
        info "检测到已有源码，拉取最新…"
        git -C "$ABAO_DIR" fetch origin "$ABAO_BRANCH" && git -C "$ABAO_DIR" reset --hard "origin/$ABAO_BRANCH"
    else
        info "克隆 ABao 源码到 $ABAO_DIR …"
        mkdir -p "$(dirname "$ABAO_DIR")"
        git clone -b "$ABAO_BRANCH" --depth 1 "$ABAO_REPO" "$ABAO_DIR"
    fi
    [ -f "$ABAO_DIR/docker-compose.prod.yml" ] || die "源码不完整：缺少 docker-compose.prod.yml"
    ok "源码就绪：$ABAO_DIR"
}

# ---------- 4. 生成 .env（交互式，零默认密码） ----------
gen_env() {
    local env_file="$ABAO_DIR/.env"
    [ -f "$env_file" ] && { warn "已存在 $env_file，跳过生成（如需重置请先删除该文件）"; return; }
    info "========== 初始化面板账号（仅本次显示，请妥善保存） =========="
    local admin_email admin_pass
    read -r -p "面板管理员邮箱 [默认 admin@abao.local]: " admin_email
    admin_email="${admin_email:-admin@abao.local}"
    read -r -s -p "面板管理员密码（留空则自动生成）: " admin_pass; echo
    if [ -z "$admin_pass" ]; then
        admin_pass="$(head -c 12 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 14)"
        warn "已自动生成密码：$admin_pass"
    fi
    local app_key="base64:$(head -c 32 /dev/urandom | base64)"
    local db_pass="$(head -c 18 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 20)"
    local redis_pass="$(head -c 18 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 20)"
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
    ok ".env 已生成（含随机数据库/Redis 密钥，仅存于服务器本地）"
}

# ---------- 5. 启动 Coolify ----------
start_coolify() {
    info "启动 Coolify 容器（Postgres + Redis + 应用）…"
    cd "$ABAO_DIR"
    docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
    info "等待服务就绪（最多 120 秒）…"
    local i=0
    until docker exec coolify php artisan --version >/dev/null 2>&1 && \
          [ "$(docker inspect -f '{{.State.Health.Status}}' coolify 2>/dev/null || echo starting)" = "healthy" ]; do
        i=$((i+5)); [ "$i" -ge 120 ] && warn "等待超时，请稍后手动检查：docker ps"
        sleep 5
    done
    # 首次初始化数据库 + 创建管理员（幂等）
    docker exec coolify php artisan migrate --force >/dev/null 2>&1 || warn "migrate 需手动确认（容器尚未就绪）"
    docker exec coolify php artisan db:seed --class=RootUserSeeder --force >/dev/null 2>&1 || true
    ok "Coolify 已启动（端口 ${APP_PORT}）"
}

# ---------- 6. 部署前端面板（Release 资产 dist） ----------
deploy_frontend() {
    info "下载 ABao 前端面板…"
    local tmp_zip="/tmp/abao-frontend.zip"
    curl -fsSL "$FRONTEND_ZIP_URL" -o "$tmp_zip" || die "前端面板下载失败（请确认 Release 已发布 abao-frontend.zip）"
    docker exec coolify rm -rf /var/www/html/public/abao
    mkdir -p /tmp/abao-frontend
    rm -rf /tmp/abao-frontend/*
    cd /tmp/abao-frontend
    unzip -q "$tmp_zip" || die "前端压缩包解压失败"
    # 兼容 zip 内可能有的顶层目录
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

# ---------- 8. 安装 mdserver-web ----------
install_mdserver() {
    if [ -f /www/server/mdserver-web/start.py ] || command -v mw >/dev/null 2>&1; then
        ok "mdserver-web 已安装，跳过"
        return
    fi
    info "安装 mdserver-web（宝塔式主机管理后端，端口 ${MDSERVER_PORT}）…"
    curl --insecure -fsSL https://cdn.jsdelivr.net/gh/midoks/mdserver-web@latest/scripts/install.sh | bash \
        || curl --insecure -fsSL https://raw.githubusercontent.com/midoks/mdserver-web/dev/scripts/install.sh | bash \
        || die "mdserver-web 安装失败"
    ok "mdserver-web 安装完成"
}

# ---------- 9. 输出结果 ----------
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
    echo "   管理员邮箱: $([ -f "$ABAO_DIR/.env" ] && grep '^ROOT_USER_EMAIL=' "$ABAO_DIR/.env" | cut -d= -f2)"
    echo "   管理员密码: $([ -f "$ABAO_DIR/.env" ] && grep '^ROOT_USER_PASSWORD=' "$ABAO_DIR/.env" | cut -d= -f2)"
    echo "   mdserver 账号: 安装结束时上方输出中的账号/密码（或运行 mw 查看）"
    echo ""
    echo "   常用命令:   mw                # mdserver 面板 CLI"
    echo "               docker ps         # 查看容器状态"
    echo "================================================================"
}

main() {
    require_root
    detect_os
    check_port "$APP_PORT"
    check_port "$MDSERVER_PORT"
    install_docker
    fetch_source
    gen_env
    start_coolify
    deploy_frontend
    patch_nginx
    install_mdserver
    print_summary
    ok "全部完成！如需修改密码：面板「设置」中修改；数据库/Redis 密码见 $ABAO_DIR/.env"
}
main "$@"
