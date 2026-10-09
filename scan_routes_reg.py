import os, re

# 看 web/admin 的路由注册机制
for p in ['/www/server/mdserver-web/web/admin/__init__.py', '/www/server/mdserver-web/web/__init__.py', '/www/server/mdserver-web/web/app.py']:
    if not os.path.exists(p):
        continue
    s = open(p, encoding='utf-8', errors='ignore').read()
    print('=====', p, 'len:', len(s))
    # 打印 url/route 相关
    for line in s.splitlines():
        if re.search(r'route|url_|Blueprint|register|prefix', line, re.I) and 'import' not in line:
            print('   ', line.strip()[:140])
