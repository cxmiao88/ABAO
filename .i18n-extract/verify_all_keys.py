# -*- coding: utf-8 -*-
"""Verify ALL __() / __('...') keys referenced in views exist in both lang files."""
import re, glob, json, io, sys
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

en = json.load(open(r'G:\xianmu\juqing\baoUIIT\lang\en.json', encoding='utf-8'))
zh = json.load(open(r'G:\xianmu\juqing\baoUIIT\lang\zh-cn.json', encoding='utf-8'))

keys = set()
for f in glob.glob(r'G:\xianmu\juqing\baoUIIT\resources\views\**\*.blade.php', recursive=True):
    try:
        s = open(f, encoding='utf-8').read()
    except Exception:
        continue
    for m in re.finditer(r"__\(\s*'([a-zA-Z0-9_.]+)'\s*\)", s):
        keys.add(m.group(1))
    for m in re.finditer(r'__\(\s*"([a-zA-Z0-9_.]+)"\s*\)', s):
        keys.add(m.group(1))

# ignore non-language keys (bare function names are not __() keys)
missing = sorted(k for k in keys if k not in en)
print('referenced unique keys:', len(keys), 'missing:', len(missing))
for k in missing:
    print('MISS', k)
