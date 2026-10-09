import re

p = '/www/server/mdserver-web/web/admin/__init__.py'
s = open(p, encoding='utf-8', errors='ignore').read()
# 打印模块扫描/注册逻辑
for line in s.splitlines():
    if re.search(r'module|blueprint|import_module|for |__all__|dir\(', line):
        print(line.strip()[:150])
