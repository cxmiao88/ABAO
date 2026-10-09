#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""收集 application 模块全部唯一 key，与 keymap 比对，输出缺失项"""
import re, os, json, sys, io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

root = r"G:\xianmu\juqing\baoUIIT\resources\views\livewire\project\application"
keys = set()
for dirpath, _, files in os.walk(root):
    for f in files:
        if f.endswith(".blade.php"):
            with open(os.path.join(dirpath, f), encoding="utf-8") as fh:
                keys.update(re.findall(r"__\('application\.([a-z0-9_]+)'", fh.read()))

keymap = {}
kp = r"G:\xianmu\juqing\baoUIIT\.i18n-extract\keymap.json"
if os.path.exists(kp):
    with open(kp, encoding="utf-8") as fh:
        keymap = json.load(fh)

missing = sorted(k for k in keys if k not in keymap)
print(f"total unique keys: {len(keys)}")
print(f"in keymap: {len(keymap)}")
print(f"MISSING ({len(missing)}): {' '.join(missing)}")
