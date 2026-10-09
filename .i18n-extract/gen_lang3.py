#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Add application-module residual keys to en.json / zh-cn.json (text-level insert before "auth." section)."""
import json, sys, io

BASE = r"G:\xianmu\juqing\baoUIIT\lang"
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8")

EN = {
    "application.int_access": "Internal access",
    "application.int_hostname": "Internal hostname",
    "application.int_no_container": "No deployed container found",
    "application.int_loading": "Loading…",
    "application.int_network": "Docker network",
    "application.int_ports": "Exposed ports",
    "application.int_aliases": "Network aliases",
    "application.int_none": "None",
    "application.int_helper": "Internal hostnames are only reachable by resources connected to this Docker network.",
    "application.int_edit": "Edit networking",
    "application.cfg_title": "Configuration",
    "application.nav_force_start": "Force start",
    "application.nav_cancel_deployment": "Cancel deployment",
    "application.bk_show_title": "Backups",
    "application.table_filter": "Filter",
    "application.table_sort": "Sort",
    "application.table_reset_filters": "Reset filters",
    "application.links_open": "Open application links",
    "application.links_title": "Links",
    "application.links_git_repo": "Git Repository",
    "application.links_production": "Production",
    "application.links_none": "No links available",
    "application.up_new_version": "New version available",
    "application.up_no_upgrade": "No upgrade available",
    "application.up_upgrade_now": "Upgrade now",
    "application.up_updating_ellipsis": "Updating…",
    "application.up_upgrade_in_progress": "Upgrade in progress",
    "application.up_updating": "Updating",
    "application.up_update_available": "Update available",
    "application.ub_unsaved_changes": "You have changes that haven't been saved yet.",
    "application.ub_reset": "Reset",
    "application.ub_save_changes": "Save changes",
    "application.ub_enter": "Enter",
    "application.dns_title_for_server": "DNS entries for this server",
    "application.dns_entries": "DNS entries",
    "application.dns_manual_records": "Manual records",
    "application.dns_configure_cloudflare": "Configure DNS on Cloudflare",
    "application.dns_cloudflare_helper": "Opens Cloudflare Domain Connect for every domain on this resource, with A records prefilled to this server's IP. Authorize each change in Cloudflare.",
    "application.dns_domains": "Domains",
    "application.dns_server_ip": "Server IP (A record target)",
    "application.dns_unavailable": "Unavailable",
    "application.dns_server_ip_helper": "This IP is taken from the destination server.",
    "application.dns_cancel": "Cancel",
    "application.dns_open_cloudflare": "Open Cloudflare",
    "application.dns_hosts_helper": "Hosts that still need DNS at your provider (working domains are omitted). Create matching Type / Name / Value records so traffic reaches this server.",
    "application.dns_no_server_ip": "No server IP",
    "application.dns_no_server_ip_body": "Could not determine a public IP for this destination. Set the server IP (or instance public IPv4 for localhost) first.",
    "application.dns_nothing": "Nothing to configure",
    "application.dns_no_pending": "No pending DNS entries. All listed domains already resolve correctly, or no domains are configured yet.",
    "application.dns_recheck": "Use Recheck after changing DNS.",
    "application.dns_no_public_ip": "No public IP",
    "application.dns_type": "Type",
    "application.dns_name": "Name",
    "application.dns_value": "Value",
    "application.dns_action": "Action",
}

ZH = {
    "application.int_access": "内部访问",
    "application.int_hostname": "内部主机名",
    "application.int_no_container": "未找到已部署的容器",
    "application.int_loading": "加载中…",
    "application.int_network": "Docker 网络",
    "application.int_ports": "暴露的端口",
    "application.int_aliases": "网络别名",
    "application.int_none": "无",
    "application.int_helper": "内部主机名仅对连接到该 Docker 网络的资源可达。",
    "application.int_edit": "编辑网络",
    "application.cfg_title": "配置",
    "application.nav_force_start": "强制启动",
    "application.nav_cancel_deployment": "取消部署",
    "application.bk_show_title": "备份",
    "application.table_filter": "筛选",
    "application.table_sort": "排序",
    "application.table_reset_filters": "重置筛选",
    "application.links_open": "打开应用链接",
    "application.links_title": "链接",
    "application.links_git_repo": "Git 仓库",
    "application.links_production": "生产环境",
    "application.links_none": "没有可用链接",
    "application.up_new_version": "有新版本可用",
    "application.up_no_upgrade": "无可用升级",
    "application.up_upgrade_now": "立即升级",
    "application.up_updating_ellipsis": "更新中…",
    "application.up_upgrade_in_progress": "升级进行中",
    "application.up_updating": "更新中",
    "application.up_update_available": "有可用更新",
    "application.ub_unsaved_changes": "您有尚未保存的更改。",
    "application.ub_reset": "重置",
    "application.ub_save_changes": "保存更改",
    "application.ub_enter": "回车",
    "application.dns_title_for_server": "此服务器的 DNS 记录",
    "application.dns_entries": "DNS 记录",
    "application.dns_manual_records": "手动记录",
    "application.dns_configure_cloudflare": "在 Cloudflare 上配置 DNS",
    "application.dns_cloudflare_helper": "为资源上的每个域名打开 Cloudflare Domain Connect，A 记录预填为服务器的 IP。请在 Cloudflare 中确认每项更改。",
    "application.dns_domains": "域名",
    "application.dns_server_ip": "服务器 IP（A 记录目标）",
    "application.dns_unavailable": "不可用",
    "application.dns_server_ip_helper": "此 IP 取自目标服务器。",
    "application.dns_cancel": "取消",
    "application.dns_open_cloudflare": "打开 Cloudflare",
    "application.dns_hosts_helper": "仍需在您的 DNS 提供商处配置解析的主机（已正常解析的域名会省略）。创建匹配的类型 / 名称 / 值记录，使流量到达此服务器。",
    "application.dns_no_server_ip": "无服务器 IP",
    "application.dns_no_server_ip_body": "无法确定此目标服务器的公网 IP。请先设置服务器 IP（localhost 可设置实例公网 IPv4）。",
    "application.dns_nothing": "无需配置",
    "application.dns_no_pending": "没有待处理的 DNS 记录。所有列出的域名均已正确解析，或尚未配置域名。",
    "application.dns_recheck": "更改 DNS 后请使用“重新检查”。",
    "application.dns_no_public_ip": "无公网 IP",
    "application.dns_type": "类型",
    "application.dns_name": "名称",
    "application.dns_value": "值",
    "application.dns_action": "操作",
}


def load(path):
    with open(path, encoding="utf-8") as f:
        return json.load(f)


def dump_text(path, data):
    """Write JSON with 4-space indent, preserving key order."""
    # build text manually: "key": value lines with 4-space indent
    lines = ["{"]
    keys = list(data.keys())
    for i, k in enumerate(keys):
        comma = "," if i < len(keys) - 1 else ""
        lines.append(f'    "{k}": {json.dumps(data[k], ensure_ascii=False)}{comma}')
    lines.append("}")
    text = "\n".join(lines) + "\n"
    with open(path, "w", encoding="utf-8") as f:
        f.write(text)


def merge_into(data, new):
    # insert before the "auth." block while keeping existing order:
    # rebuild: all existing keys except those starting with "auth.", then new keys, then auth. keys
    auth = {k: v for k, v in data.items() if k.startswith("auth.")}
    rest = {k: v for k, v in data.items() if not k.startswith("auth.")}
    merged = {}
    for k, v in rest.items():
        merged[k] = v
    for k, v in new.items():
        merged[k] = v
    for k, v in auth.items():
        merged[k] = v
    return merged


for name, lang in (("en.json", EN), ("zh-cn.json", ZH)):
    p = BASE + "\\" + name
    data = load(p)
    before = len(data)
    data = merge_into(data, lang)
    dump_text(p, data)
    after = len(data)
    print(f"{name}: {before} -> {after} keys")
