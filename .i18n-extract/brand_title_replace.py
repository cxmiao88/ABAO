#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os, re

ROOT = r"G:\xianmu\juqing\baoUIIT\resources\views"
n_files = 0
n_repl = 0
for dirpath, _dirs, files in os.walk(ROOT):
    for fn in files:
        if not fn.endswith(".blade.php"):
            continue
        p = os.path.join(dirpath, fn)
        c = open(p, encoding="utf-8").read()
        c2, cnt = re.subn(r"\| Coolify", "| ABao", c)
        if cnt:
            open(p, "w", encoding="utf-8").write(c2)
            n_files += 1
            n_repl += cnt
print("files:", n_files, "replacements:", n_repl)
