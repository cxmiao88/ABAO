import os

root = '/www/server/mdserver-web'
skip = ('/lib/', '/node_modules/', '/.git', '/logs/')
hits = []
for base, dirs, files in os.walk(root):
    if any(s in base for s in skip):
        continue
    for f in files:
        if f.endswith('.py') or f.endswith('.html'):
            p = os.path.join(base, f)
            try:
                s = open(p, encoding='utf-8', errors='ignore').read()
            except Exception:
                continue
            if '常用软件' in s or '其他软件' in s or 'type_name' in s:
                hits.append(p)
for h in hits[:15]:
    print(h)
print('total:', len(hits))
