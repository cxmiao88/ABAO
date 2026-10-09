import re

p = '/www/server/mdserver-web/web/admin/plugins/__init__.py'
s = open(p, encoding='utf-8', errors='ignore').read()
print('len:', len(s))
# 打印 type 相关行
for line in s.splitlines():
    if re.search(r'type', line, re.I) and ('软件' in line or '= 0' in line or '= 1' in line or '= 2' in line or '= 3' in line or '= 4' in line or '= 5' in line or '= 6' in line or '= 7' in line or 'name' in line):
        print(line.strip()[:180])
