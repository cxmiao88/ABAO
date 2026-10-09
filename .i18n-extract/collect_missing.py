# -*- coding: utf-8 -*-
"""Collect application.* keys referenced in blade views that are missing from lang/en.json."""
import re, glob, json, io, sys

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

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

en = json.load(open(r'G:\xianmu\juqing\baoUIIT\lang\en.json', encoding='utf-8'))
existing = {k for k in en if k.startswith('application.')}
missing = sorted(keys - existing)
print('referenced:', len(keys), 'existing:', len(existing), 'missing:', len(missing))
for k in missing:
    print(k)
