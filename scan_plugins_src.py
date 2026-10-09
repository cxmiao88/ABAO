import os, re

root = '/www/server/mdserver-web'
skip = ('/lib/', '/lib64', '/node_modules/', '/.git', '/logs/')
targets = []
for base, dirs, files in os.walk(root):
    if any(s in base for s in skip):
        continue
    for f in files:
        if f.endswith('.py'):
            p = os.path.join(base, f)
            try:
                s = open(p, encoding='utf-8', errors='ignore').read()
            except Exception:
                continue
            if 'plugins/list' in s or 'def get_plugin_list' in s or 'plugin_list' in s:
                targets.append(p)

for p in targets:
    print('==', p)
    s = open(p, encoding='utf-8', errors='ignore').read()
    # 找 type 标签相关行
    for line in s.splitlines():
        if 'type' in line and ('软件' in line or 'type_name' in line or 'type_desc' in line or '默认' in line):
            print('   ', line.strip()[:150])
