import json, sys

en_path = '/opt/coolify-src/lang/en.json'
zh_path = '/opt/coolify-src/lang/zh-cn.json'

with open(en_path, 'r', encoding='utf-8') as f:
    en = json.load(f)
with open(zh_path, 'r', encoding='utf-8') as f:
    zh = json.load(f)

print('base en=' + str(len(en)) + ' zh=' + str(len(zh)))

nav = {
    'nav.workspace': {'en': 'Workspace', 'zh': '工作区'},
    'nav.dashboard': {'en': 'Dashboard', 'zh': '仪表盘'},
    'nav.projects': {'en': 'Projects', 'zh': '项目'},
    'nav.analytics': {'en': 'Analytics', 'zh': '分析'},
    'nav.terminal': {'en': 'Terminal', 'zh': '终端'},
    'nav.infrastructure': {'en': 'Infrastructure', 'zh': '基础设施'},
    'nav.servers': {'en': 'Servers', 'zh': '服务器'},
    'nav.sources': {'en': 'Sources', 'zh': '代码源'},
    'nav.destinations': {'en': 'Destinations', 'zh': '部署目标'},
    'nav.registries': {'en': 'Registries', 'zh': '镜像仓库'},
    'nav.s3_storage': {'en': 'S3 Storage', 'zh': 'S3 存储'},
    'nav.shared_variables': {'en': 'Shared Variables', 'zh': '共享变量'},
    'nav.manage': {'en': 'Manage', 'zh': '管理'},
    'nav.team': {'en': 'Team', 'zh': '团队'},
    'nav.notifications': {'en': 'Notifications', 'zh': '通知'},
    'nav.keys_tokens': {'en': 'Keys & Tokens', 'zh': '密钥与令牌'},
    'nav.subscription': {'en': 'Subscription', 'zh': '订阅'},
    'nav.tags': {'en': 'Tags', 'zh': '标签'},
    'nav.settings': {'en': 'Settings', 'zh': '设置'},
    'nav.admin': {'en': 'Admin', 'zh': '管理后台'},
}

for k, v in nav.items():
    en[k] = v['en']
    zh[k] = v['zh']

en = dict(sorted(en.items()))
zh = dict(sorted(zh.items()))

with open(en_path, 'w', encoding='utf-8') as f:
    json.dump(en, f, ensure_ascii=False, indent=4)
    f.write('\n')
with open(zh_path, 'w', encoding='utf-8') as f:
    json.dump(zh, f, ensure_ascii=False, indent=4)
    f.write('\n')

print('merged en=' + str(len(en)) + ' zh=' + str(len(zh)))
