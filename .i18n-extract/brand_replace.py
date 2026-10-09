#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import json, re

for p in [r"G:\xianmu\juqing\baoUIIT\lang\en.json", r"G:\xianmu\juqing\baoUIIT\lang\zh-cn.json"]:
    d = json.load(open(p, encoding="utf-8"))
    pat = re.compile(r"\bCoolify\b")
    n = 0
    for k, v in d.items():
        nv = pat.sub("ABao", v)
        if nv != v:
            d[k] = nv
            n += 1
    lines = ["{"]
    ks = list(d.keys())
    for i, k in enumerate(ks):
        c = "," if i < len(ks) - 1 else ""
        lines.append('    "%s": %s%s' % (k, json.dumps(d[k], ensure_ascii=False), c))
    lines.append("}")
    open(p, "w", encoding="utf-8").write("\n".join(lines) + "\n")
    print(p, "replaced:", n)
