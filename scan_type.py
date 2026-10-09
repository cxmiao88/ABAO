import re, os, sys

root = '/www/server/mdserver-web'
hits = []
for base, dirs, files in os.walk(root):
    if '/node_modules' in base or '/.git' in base or '/logs' in base:
        continue
    for f in files:
        if f.endswith('.py'):
            p = os.path.join(base, f)
            try:
                s = open(p, encoding='utf-8', errors='ignore').read()
            except Exception:
                continue
            # 找 type 分类标签定义
            if 'type_name' in s or ('type' in s and '常用' in s):
                hits.append(p)
                print('HIT:', p)

print('total hits:', len(hits))
