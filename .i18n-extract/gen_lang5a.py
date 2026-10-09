#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Append tags/storage/destination keys to en.json / zh-cn.json (insert before auth.)."""
import json, sys, io

BASE = r"G:\xianmu\juqing\baoUIIT\lang"
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8")

EN = {
    "tags.title": "Tags", "tags.empty_helper": "Group applications and services for bulk deploys",
    "tags.manage_helper": "Manage webhook deploys and resources for this tag",
    "tags.tags_count": "个标签", "tags.list_helper": "for bulk deploys and grouping",
    "tags.no_tags_yet": "No tags yet", "tags.no_tags_helper": "Open a resource and add a tag to start grouping related deployments.",
    "tags.search_tags": "Search tags", "tags.clear_search": "Clear search", "tags.sort": "Sort",
    "tags.table_view": "Table view", "tags.grid_view": "Grid view", "tags.open": "Open", "tags.team_tag": "Team tag",
    "tags.resources_count": "个资源", "tags.of": "共", "tags.tag": "Tag", "tags.resources": "Resources",
    "tags.applications": "Applications", "tags.services": "Services", "tags.no_matching": "No matching tags",
    "tags.no_matching_helper": "Try a different search.", "tags.name_asc": "Name A–Z", "tags.name_desc": "Name Z–A",
    "tags.most_resources": "Most resources", "tags.apps_and_services": "Applications & services",
    "tags.using_this_tag": "Using this tag", "tags.active_deployments": "Active deployments",
    "tags.queued_or_running": "Queued or running", "tags.webhook_helper": "Use this webhook to deploy every resource with this tag.",
    "tags.redeploy_all_q": "Redeploy all resources with this tag?", "tags.redeploy_all": "Redeploy all",
    "tags.redeploy_note1": "All resources with this tag will be redeployed.",
    "tags.redeploy_note2": "During redeploy resources will be temporarily unavailable.",
    "tags.confirm_label": "Please confirm the execution of the actions by entering the Tag Name below",
    "tags.tag_name": "Tag Name", "tags.redeploy_all_btn": "Redeploy All", "tags.webhook_url": "Deploy webhook URL",
    "tags.use_this_tag": "use this tag.", "tags.no_resources_use": "No resources use this tag",
    "tags.no_resources_use_helper": "Add this tag to an application or service to see it here.",
    "tags.application": "Application", "tags.service": "Service",
    "tags.active_deployments_helper": "Queued and running deployments for applications using this tag.",
    "tags.no_active_deployments": "No active deployments",
    "tags.no_active_deployments_helper": "Deployments will appear here while they are queued or running.",
    "tags.resource": "Resource", "tags.server": "Server", "tags.status": "Status",
    "st.title": "Storages", "st.s3_storage": "S3 Storage", "st.storage_destinations_count": "个存储目标",
    "st.for_backups": "用于备份", "st.new_s3_storage": "New S3 Storage", "st.new_storage": "New storage",
    "st.no_storage_yet": "No S3 storage yet",
    "st.no_storage_helper": "Add an S3-compatible destination to store backups outside your servers.",
    "st.s3_compatible": "S3-compatible storage", "st.connected": "Connected", "st.not_usable": "Not usable",
    "st.search": "Search S3 storages", "st.singular": "个存储", "st.plural": "个存储",
    "st.storage": "Storage", "st.description": "Description", "st.status": "Status", "st.s3_storages": "S3 storages",
    "dest.title": "Destinations", "dest.network_endpoints_count": "个网络端点", "dest.new": "New Destination",
    "dest.new_btn": "New destination", "dest.no_destinations": "No destinations yet",
    "dest.no_destinations_helper": "Add a Docker network endpoint to choose where your resources are deployed.",
    "dest.swarm": "Docker Swarm", "dest.standalone": "Standalone Docker", "dest.search": "Search destinations",
    "dest.singular": "个部署目标", "dest.plural": "个部署目标", "dest.destination": "Destination",
    "dest.server": "Server", "dest.type": "Type",
}

ZH = {
    "tags.title": "标签", "tags.empty_helper": "对应用和服务分组以进行批量部署",
    "tags.manage_helper": "管理此标签的 Webhook 部署和资源", "tags.tags_count": "个标签",
    "tags.list_helper": "用于批量部署和分组", "tags.no_tags_yet": "还没有标签",
    "tags.no_tags_helper": "打开一个资源并添加标签，开始对相关部署进行分组。", "tags.search_tags": "搜索标签",
    "tags.clear_search": "清除搜索", "tags.sort": "排序", "tags.table_view": "表格视图", "tags.grid_view": "网格视图",
    "tags.open": "打开", "tags.team_tag": "团队标签", "tags.resources_count": "个资源", "tags.of": "共",
    "tags.tag": "标签", "tags.resources": "资源", "tags.applications": "应用", "tags.services": "服务",
    "tags.no_matching": "没有匹配的标签", "tags.no_matching_helper": "请尝试其他搜索词。", "tags.name_asc": "名称 A–Z",
    "tags.name_desc": "名称 Z–A", "tags.most_resources": "资源最多", "tags.apps_and_services": "应用与服务",
    "tags.using_this_tag": "使用此标签", "tags.active_deployments": "进行中的部署", "tags.queued_or_running": "排队或运行中",
    "tags.webhook_helper": "使用此 Webhook 部署带有此标签的所有资源。", "tags.redeploy_all_q": "重新部署所有带有此标签的资源？",
    "tags.redeploy_all": "全部重新部署", "tags.redeploy_note1": "所有带有此标签的资源都将被重新部署。",
    "tags.redeploy_note2": "重新部署期间资源将暂时不可用。",
    "tags.confirm_label": "请在下方输入标签名称以确认执行操作", "tags.tag_name": "标签名称",
    "tags.redeploy_all_btn": "全部重新部署", "tags.webhook_url": "部署 Webhook URL", "tags.use_this_tag": "个资源使用此标签。",
    "tags.no_resources_use": "没有资源使用此标签",
    "tags.no_resources_use_helper": "将此标签添加到应用或服务，即可在此看到它。", "tags.application": "应用",
    "tags.service": "服务", "tags.active_deployments_helper": "使用此标签的应用的排队中和运行中部署。",
    "tags.no_active_deployments": "没有进行中的部署",
    "tags.no_active_deployments_helper": "部署在排队或运行时会显示在此处。", "tags.resource": "资源",
    "tags.server": "服务器", "tags.status": "状态",
    "st.title": "存储", "st.s3_storage": "S3 存储", "st.storage_destinations_count": "个存储目标",
    "st.for_backups": "用于备份", "st.new_s3_storage": "新建 S3 存储", "st.new_storage": "新建存储",
    "st.no_storage_yet": "还没有 S3 存储",
    "st.no_storage_helper": "添加 S3 兼容的目标，将备份存储到您的服务器之外。", "st.s3_compatible": "S3 兼容存储",
    "st.connected": "已连接", "st.not_usable": "不可用", "st.search": "搜索 S3 存储", "st.singular": "个存储",
    "st.plural": "个存储", "st.storage": "存储", "st.description": "描述", "st.status": "状态", "st.s3_storages": "S3 存储",
    "dest.title": "部署目标", "dest.network_endpoints_count": "个网络端点", "dest.new": "新建部署目标",
    "dest.new_btn": "新建部署目标", "dest.no_destinations": "还没有部署目标",
    "dest.no_destinations_helper": "添加 Docker 网络端点，选择您的资源部署位置。", "dest.swarm": "Docker Swarm",
    "dest.standalone": "独立 Docker", "dest.search": "搜索部署目标", "dest.singular": "个部署目标",
    "dest.plural": "个部署目标", "dest.destination": "部署目标", "dest.server": "服务器", "dest.type": "类型",
}


def load(path):
    with open(path, encoding="utf-8") as f:
        return json.load(f)


def dump_text(path, data):
    lines = ["{"]
    keys = list(data.keys())
    for i, k in enumerate(keys):
        comma = "," if i < len(keys) - 1 else ""
        lines.append(f'    "{k}": {json.dumps(data[k], ensure_ascii=False)}{comma}')
    lines.append("}")
    with open(path, "w", encoding="utf-8") as f:
        f.write("\n".join(lines) + "\n")


def merge_into(data, new):
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
    print(f"{name}: {before} -> {len(data)} keys")
