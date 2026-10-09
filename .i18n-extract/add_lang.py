import io, json

new_keys = {
    "en": {
        "dash_mode_baota": "Baota Mode",
        "dash_mode_classic": "Classic Mode",
        "dash_baota_stat_servers": "Servers",
        "dash_baota_stat_running": "Running",
        "dash_baota_stat_apps": "Applications",
        "dash_baota_stat_dbs": "Databases",
        "dash_baota_servers_subtitle": "Real-time status of your servers",
        "dash_baota_enter": "Enter",
        "dash_baota_quick_title": "Quick Actions",
        "dash_quick_projects": "Projects",
        "dash_quick_new_server": "Add Server",
        "dash_quick_store": "Store",
        "dash_quick_new_resource": "New Resource",
    },
    "zh-cn": {
        "dash_mode_baota": "宝塔模式",
        "dash_mode_classic": "原版模式",
        "dash_baota_stat_servers": "服务器",
        "dash_baota_stat_running": "运行中",
        "dash_baota_stat_apps": "应用",
        "dash_baota_stat_dbs": "数据库",
        "dash_baota_servers_subtitle": "服务器的实时运行状态",
        "dash_baota_enter": "进入",
        "dash_baota_quick_title": "快捷操作",
        "dash_quick_projects": "项目管理",
        "dash_quick_new_server": "添加服务器",
        "dash_quick_store": "访问商店",
        "dash_quick_new_resource": "创建资源",
    },
}

for lang, items in new_keys.items():
    p = rf"G:\xianmu\juqing\baoUIIT\lang\{lang}.json"
    data = json.load(io.open(p, encoding='utf-8'))
    added = 0
    for k, v in items.items():
        if k not in data:
            data[k] = v
            added += 1
    # write back sorted with ensure_ascii=False, keep original formatting (2-space indent)
    io.open(p, 'w', encoding='utf-8', newline='\n').write(json.dumps(data, ensure_ascii=False, indent=2) + "\n")
    print(lang, "added:", added)
