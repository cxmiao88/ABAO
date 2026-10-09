import os, re

root = '/www/server/mdserver-web/web/admin/site'
for f in sorted(os.listdir(root)):
    if not f.endswith('.py'):
        continue
    p = os.path.join(root, f)
    s = open(p, encoding='utf-8', errors='ignore').read()
    routes = re.findall(r"@\w+\.route\([^)]*['\"]([^'\"]+)['\"]", s, re.S)
    if not routes:
        routes = re.findall(r"\.add_url_rule\(['\"]([^'\"]+)['\"]", s)
    if routes:
        print(f, '->', routes[:14])
