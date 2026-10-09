#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import json, sys, io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8")
e = json.load(open(r"G:\xianmu\juqing\baoUIIT\lang\en.json", encoding="utf-8"))
z = json.load(open(r"G:\xianmu\juqing\baoUIIT\lang\zh-cn.json", encoding="utf-8"))
se, sz = set(e.keys()), set(z.keys())
print("total en", len(e), "zh", len(z))
print("en==zh keys:", se == sz)
print("diff en-zh:", list(se - sz)[:5])
print("diff zh-en:", list(sz - se)[:5])
