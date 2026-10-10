#!/bin/bash
# ============================================================================
#  ABao 面板 CLI（阿宝面板命令，等同宝塔 bt）
#  特性：菜单循环保持 —— 处理完一项自动回到菜单，输入 0 或 q 退出
#  选项 10 显示 ABao 统一面板信息（8000 + 随机码 + 账号），不再显示 mdserver 48700
# ============================================================================
MW=/www/server/mdserver-web
ENV=/data/coolify/source/.env

get_public_ip() {
    local p
    p="$(curl -s --max-time 3 http://metadata.tencentyun.com/latest/meta-data/public-ipv4 2>/dev/null)"
    [ -n "$p" ] || p="$(curl -s --max-time 3 https://api.ipify.org 2>/dev/null)"
    [ -n "$p" ] || p="$(curl -s --max-time 3 https://ifconfig.me 2>/dev/null)"
    [ -n "$p" ] || p="$(hostname -I 2>/dev/null | awk '{print $1}')"
    echo "${p:-127.0.0.1}"
}

show_info() {
    local ip code user pass
    ip="$(get_public_ip)"
    code="$(grep '^ABAO_ENTRY_CODE=' "$ENV" 2>/dev/null | cut -d= -f2)"
    user="$(grep -E '\|-username:' /tmp/mdserver-install.log 2>/dev/null | tail -1 | sed 's/.*username: *//' | tr -d '[:space:]')"
    pass="$(grep -E '\|-password:' /tmp/mdserver-install.log 2>/dev/null | tail -1 | sed 's/.*password: *//' | tr -d '[:space:]')"
    if [ -z "$user" ] && [ -f "$MW/data/default.db" ]; then
        user="$(python3 -c "import sqlite3;c=sqlite3.connect('$MW/data/default.db');r=c.execute('select name from users limit 1').fetchone();print(r[0] if r else '')" 2>/dev/null)"
    fi
    echo "=================================================================="
    echo "  ABao PANEL DEFAULT INFO!"
    echo "=================================================================="
    echo "ABao-URL:   http://${ip}:8000/${code}"
    echo "本地地址:   http://localhost:8000/${code}"
    echo "|-username: ${user:-未知（运行 mw 查看）}"
    echo "|-password: ${pass:-见 /tmp/mdserver-install.log 或运行 mw}"
    echo "（Coolify/mdserver 原登录入口均已关闭，统一从随机码入口进入）"
    echo "=================================================================="
}

mwcli() { # 把编号/输入喂给 mdserver 原生 CLI（交互项），末尾自动 0 退出
    local input="$1"; shift
    { echo -e "$input"; printf '%s\n' "$@"; echo "0"; } | timeout 60 python3 "$MW/panel_tools.py" cli 2>&1
}

while true; do
    echo ""
    echo "========================ABao cli tools=========================="
    echo "(1) 重启面板服务                     (2) 停止面板服务"
    echo "(3) 启动面板服务                     (4) 重载面板服务"
    echo "(5) 修改面板端口(默认8000)           (6) 修改面板密码"
    echo "(7) 修改面板用户名                   (10) 查看面板默认信息"
    echo "(11) 关闭面板访问                    (12) 开启面板访问"
    echo "(13) 关闭BasicAuth认证               (14) 查看面板错误日志"
    echo "(0) 取消/退出"
    echo "========================================================================"
    read -rp "请输入命令编号：" k
    case "$k" in
        0|q|Q|exit) echo "[ABao] 已退出"; break ;;
        1)
            /etc/rc.d/init.d/mw restart >/dev/null 2>&1
            docker restart coolify >/dev/null 2>&1
            echo "[ABao] 面板服务已重启" ;;
        2)
            /etc/rc.d/init.d/mw stop >/dev/null 2>&1
            docker stop coolify >/dev/null 2>&1
            echo "[ABao] 面板服务已停止" ;;
        3)
            /etc/rc.d/init.d/mw start >/dev/null 2>&1
            docker start coolify >/dev/null 2>&1
            echo "[ABao] 面板服务已启动" ;;
        4)
            /etc/rc.d/init.d/mw reload >/dev/null 2>&1
            echo "[ABao] 面板服务已重载" ;;
        5)
            read -rp "请输入新的面板端口（默认8000，改后从新端口访问）：" newport
            [ -z "$newport" ] && newport=8000
            if [ "$newport" != "8000" ]; then
                sed -i "s/^APP_PORT=.*/APP_PORT=${newport}/" "$ENV" 2>/dev/null
                # compose 端口行动态替换（兼容 8000:8080 或 ${APP_PORT}:8080）
                sed -i -E "s/(\"?)\b8000\b(:8080)/\1${newport}\2/" /data/coolify/source/docker-compose.yml 2>/dev/null
                cd /data/coolify/source && docker compose up -d >/dev/null 2>&1
                firewall-cmd --permanent --add-port=${newport}/tcp >/dev/null 2>&1 && firewall-cmd --reload >/dev/null 2>&1
                echo "[ABao] 面板端口已改为 ${newport}（防火墙已放行，请用 http://IP:${newport}/随机码 访问）"
            else
                echo "[ABao] 端口未变化"
            fi ;;
        6)
            read -rsp "请输入新的面板密码：" newpwd; echo ""
            if [ -z "$newpwd" ]; then echo "[ABao] 密码不能为空"; else
                mwcli "11" "$newpwd" | grep -E 'username|password|密码|用户' || true
                echo "[ABao] 面板密码已修改"
            fi ;;
        7)
            read -rp "请输入新的面板用户名：" newname
            if [ -z "$newname" ]; then echo "[ABao] 用户名不能为空"; else
                mwcli "12" "$newname" | grep -E 'username|password|用户名|用户' || true
                echo "[ABao] 面板用户名已修改"
            fi ;;
        10) show_info ;;
        11) mwcli "14" | tail -5 ;;
        12) mwcli "15" | tail -5 ;;
        13) mwcli "20" | tail -5 ;;
        14)
            echo "--- ABao 安装日志 ---"
            tail -30 /tmp/abao-install.log 2>/dev/null || echo "（无日志）"
            echo "--- mdserver 错误日志 ---"
            tail -15 "$MW/logs/error.log" 2>/dev/null || echo "（无错误日志）" ;;
        *) echo "[ABao] 无效编号，请重新输入" ;;
    esac
    echo ""
    echo -e "\033[1;36m[ABao]\033[0m 回车返回菜单继续操作；输入 0 或 q 退出："
    read -r k2
    case "$k2" in
        0|q|Q) echo "[ABao] 已退出"; break ;;
        *) continue ;;
    esac
done
