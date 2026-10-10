#!/bin/bash
# ============================================================================
#  ABao 面板 CLI（阿宝面板命令，等同宝塔 bt）
#  特性：菜单循环保持 —— 处理完一项自动回到菜单，输入 0 或 q 退出
#  用法：ab / AB（不分大小写），然后按菜单编号操作
# ============================================================================
while true; do
    cd /www/server/mdserver-web 2>/dev/null && python3 panel_tools.py cli
    echo ""
    echo -e "\033[1;36m[ABao]\033[0m 回车返回菜单继续操作；输入 0 或 q 退出："
    read -r k
    case "$k" in
        0|q|Q) echo "[ABao] 已退出"; break ;;
        *) continue ;;
    esac
done
