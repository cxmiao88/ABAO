#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os, re

ROOT = r"G:\xianmu\juqing\baoUIIT\resources\views"
hits = []
for dp, dn, fn in os.walk(ROOT):
    for f in fn:
        if not f.endswith(".blade.php"):
            continue
        p = os.path.join(dp, f)
        src = open(p, encoding="utf-8").read()
        for i, line in enumerate(src.splitlines(), 1):
            # 行内含 bg-white（含透明如 bg-white/70）且同行无 dark:bg 覆盖
            if re.search(r"\bbg-white\b", line) and "dark:bg" not in line:
                # 跳过注释/纯样式字符串等明显噪音
                hits.append((p, i, line.strip()[:130]))

for p, i, l in hits:
    print(f"{os.path.relpath(p, ROOT)}:{i}: {l}")
print("total:", len(hits))
