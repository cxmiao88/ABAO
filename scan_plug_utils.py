import re

p = '/www/server/mdserver-web/web/utils/plugin.py'
s = open(p, encoding='utf-8', errors='ignore').read()
print('len:', len(s))
for line in s.splitlines():
    if re.search(r'type', line, re.I) and re.search(r'[\u4e00-\u9fff]', line):
        print(line.strip()[:200])
