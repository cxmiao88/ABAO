# -*- coding: utf-8 -*-
"""Verify all blade-referenced application.* keys exist in both lang files."""
import re, glob, json, io, sys
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

en = json.load(open(r'G:\xianmu\juqing\baoUIIT\lang\en.json', encoding='utf-8'))
zh = json.load(open(r'G:\xianmu\juqing\baoUIIT\lang\zh-cn.json', encoding='utf-8'))
app_en = {k for k in en if k.startswith('application.')}
app_zh = {k for k in zh if k.startswith('application.')}
print('en application keys:', len(app_en), 'zh:', len(app_zh))
print('en==zh:', app_en == app_zh)

keys = set()
for f in glob.glob(r'G:\xianmu\juqing\baoUIIT\resources\views\**\*.blade.php', recursive=True):
    try:
        s = open(f, encoding='utf-8').read()
    except Exception:
        continue
    for m in re.finditer(r"__\('application\.([a-zA-Z0-9_]+)'\)", s):
        keys.add('application.' + m.group(1))
    for m in re.finditer(r'__\(\"application\.([a-zA-Z0-9_]+)\"\)', s):
        keys.add('application.' + m.group(1))

missing = sorted(keys - app_en)
print('referenced:', len(keys), 'missing:', len(missing))
for k in missing:
    print('MISS', k)
