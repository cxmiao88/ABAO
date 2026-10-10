#!/bin/bash
# ============================================================================
#  ABao 面板 CLI（阿宝面板命令，等同宝塔 bt）
#  特性：菜单循环保持 —— 处理完一项自动回到菜单，输入 0 或 q 退出
#  完整 mdserver 命令全部保留（1-29 + 100-202），选项 10 显示 ABao 统一面板信息
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

while true; do
    echo ""
    echo "========================ABao cli tools=========================="
    echo "(1)    重启面板服务                     (2)    停止面板服务"
    echo "(3)    启动面板服务                     (4)    重载面板服务"
    echo "(5)    修改面板IP                       (6)    修改面板端口(mdserver 48700)"
    echo "(7)    关闭安全入口                     (10)   查看面板默认信息(ABao统一面板)"
    echo "(11)   修改面板密码                     (12)   修改面板用户名"
    echo "(13)   显示面板错误日志                 (14)   关闭面板访问"
    echo "(15)   开启面板访问                     (20)   关闭BasicAuth认证"
    echo "(21)   解除域名绑定                     (22)   解除面板SSL绑定"
    echo "(23)   开启IPV6支持                     (24)   关闭IPV6支持"
    echo "(25)   开启防火墙SSH端口                (26)   关闭二次验证"
    echo "(27)   查看防火墙信息                   (28)   自动识别防火墙端口到面板"
    echo "(29)   自动识别配置站点信息             (100)  开启PHP52显示"
    echo "(101)  关闭PHP52显示                    (200)  切换Linux系统软件源"
    echo "(201)  简单速度测试                     (202)  SSH终端管理"
    echo "(0)    取消"
    echo "========================================================================"
    read -rp "请输入命令编号：" k
    case "$k" in
        0|q|Q|exit) echo "[ABao] 已退出"; break ;;
        10) show_info ;;
        *)
            # 其余全部转 mdserver 原生 CLI（先喂编号，后续输入由用户直接交互）
            cd "$MW" 2>/dev/null
            { echo "$k"; cat; } | timeout 600 python3 panel_tools.py cli 2>&1
            ;;
    esac
    echo ""
    echo -e "\033[1;36m[ABao]\033[0m 回车返回菜单继续操作；输入 0 或 q 退出："
    read -r k2
    case "$k2" in
        0|q|Q) echo "[ABao] 已退出"; break ;;
        *) continue ;;
    esac
done
