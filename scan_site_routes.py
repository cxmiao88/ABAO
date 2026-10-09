import os, re

root = '/www/server/mdserver-web/web/admin/site'
for f in sorted(os.listdir(root)):
    if not f.endswith('.py'):
        continue
    p = os.path.join(root, f)
    s = open(p, encoding='utf-8', errors='ignore').read()
    # 找 route 注册的各种写法
    routes = re.findall(r"route\(['\"]([^'\"]+)['\"]", s)
    if routes:
        print(f, '->', routes[:15])
