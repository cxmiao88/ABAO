#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Append shared-variables module keys to en.json / zh-cn.json (insert before auth.)."""
import json, sys, io

BASE = r"G:\xianmu\juqing\baoUIIT\lang"
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8")

EN = {
    "sv.title": "Shared Variables",
    "sv.team_wide": "Team wide",
    "sv.team_wide_helper": "Available to every resource owned by this team.",
    "sv.project_wide": "Project wide",
    "sv.project_wide_helper": "Shared by every environment inside a project.",
    "sv.env_wide": "Environment wide",
    "sv.env_wide_helper": "Reused by resources in one environment.",
    "sv.server_wide": "Server wide",
    "sv.server_wide_helper": "Available to resources deployed on one server.",
    "sv.overview": "Overview",
    "sv.team": "Team",
    "sv.projects": "Projects",
    "sv.environments": "Environments",
    "sv.servers": "Servers",
    "sv.headline": "Shared variables",
    "sv.headline_helper": "Reusable environment variables across resources",
    "sv.search": "Search",
    "sv.clear_search": "Clear search",
    "sv.list_view": "List view",
    "sv.grid_view": "Grid view",
    "sv.developer_view": "Developer view",
    "sv.normal_view": "Normal view",
    "sv.developer": "Developer",
    "sv.normal": "Normal",
    "sv.search_variables": "Search variables",
    "sv.sort": "Sort",
    "sv.alphabetical": "Alphabetical",
    "sv.creation_order": "Creation order",
    "sv.new_shared_variable": "New Shared Variable",
    "sv.add_variable": "Add variable",
    "sv.no_shared_variables": "No shared variables",
    "sv.no_shared_variables_helper": "Add a variable to make it available to resources in this scope.",
    "sv.name": "Name",
    "sv.scope": "Scope",
    "sv.value_comment": "Value / comment",
    "sv.comment": "Comment",
    "sv.multiline": "Multiline",
    "sv.builtin_readonly": "Built-in · Read-only",
    "sv.builtin_managed": "Built-in variable, managed by Coolify",
    "sv.variables_count": "个变量",
    "sv.team_variables": "Team variables",
    "sv.team_shared_variables": "Team shared variables",
    "sv.project_shared_variables": "Project shared variables",
    "sv.environment_shared_variables": "Environment shared variables",
    "sv.server_shared_variables": "Server shared variables",
    "sv.project_variables_title": "Project Variables",
    "sv.environment_variables_title": "Environment Variables",
    "sv.server_variables_title": "Server Variables",
    "sv.team_variables_title": "Team Variables",
    "sv.no_projects_yet": "No projects yet",
    "sv.no_projects_helper": "Create a project before adding project-wide variables.",
    "sv.no_environments_yet": "No environments yet",
    "sv.no_environments_helper": "Create a project environment before adding environment-wide variables.",
    "sv.project_environments": "Project environments",
    "sv.no_environments_in_project": "No environments in this project.",
    "sv.no_servers_yet": "No servers yet",
    "sv.no_servers_helper": "Add a server before creating server-wide variables.",
    "sv.ready": "Ready",
    "sv.validation_required": "Validation required",
}

ZH = {
    "sv.title": "共享变量",
    "sv.team_wide": "团队级",
    "sv.team_wide_helper": "对该团队拥有的每个资源可用。",
    "sv.project_wide": "项目级",
    "sv.project_wide_helper": "由项目内的每个环境共享。",
    "sv.env_wide": "环境级",
    "sv.env_wide_helper": "由同一环境中的资源复用。",
    "sv.server_wide": "服务器级",
    "sv.server_wide_helper": "对部署在同一服务器上的资源可用。",
    "sv.overview": "概览",
    "sv.team": "团队",
    "sv.projects": "项目",
    "sv.environments": "环境",
    "sv.servers": "服务器",
    "sv.headline": "共享变量",
    "sv.headline_helper": "跨资源可复用的环境变量",
    "sv.search": "搜索",
    "sv.clear_search": "清除搜索",
    "sv.list_view": "列表视图",
    "sv.grid_view": "网格视图",
    "sv.developer_view": "开发者视图",
    "sv.normal_view": "普通视图",
    "sv.developer": "开发者",
    "sv.normal": "普通",
    "sv.search_variables": "搜索变量",
    "sv.sort": "排序",
    "sv.alphabetical": "字母顺序",
    "sv.creation_order": "创建顺序",
    "sv.new_shared_variable": "新建共享变量",
    "sv.add_variable": "添加变量",
    "sv.no_shared_variables": "没有共享变量",
    "sv.no_shared_variables_helper": "添加变量以使其对该范围内的资源可用。",
    "sv.name": "名称",
    "sv.scope": "作用域",
    "sv.value_comment": "值 / 备注",
    "sv.comment": "备注",
    "sv.multiline": "多行",
    "sv.builtin_readonly": "内置 · 只读",
    "sv.builtin_managed": "由 Coolify 管理的内置变量",
    "sv.variables_count": "个变量",
    "sv.team_variables": "团队变量",
    "sv.team_shared_variables": "团队共享变量",
    "sv.project_shared_variables": "项目共享变量",
    "sv.environment_shared_variables": "环境共享变量",
    "sv.server_shared_variables": "服务器共享变量",
    "sv.project_variables_title": "项目变量",
    "sv.environment_variables_title": "环境变量",
    "sv.server_variables_title": "服务器变量",
    "sv.team_variables_title": "团队变量",
    "sv.no_projects_yet": "还没有项目",
    "sv.no_projects_helper": "请先创建项目，再添加项目级变量。",
    "sv.no_environments_yet": "还没有环境",
    "sv.no_environments_helper": "请先创建项目环境，再添加环境级变量。",
    "sv.project_environments": "项目环境",
    "sv.no_environments_in_project": "此项目中暂无环境。",
    "sv.no_servers_yet": "还没有服务器",
    "sv.no_servers_helper": "请先添加服务器，再创建服务器级变量。",
    "sv.ready": "就绪",
    "sv.validation_required": "需要验证",
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
