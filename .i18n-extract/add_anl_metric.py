#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import json

def dump(p, d):
    keys = sorted(d.keys())
    lines = ["{"]
    for i, k in enumerate(keys):
        c = "," if i < len(keys) - 1 else ""
        lines.append('    "%s": %s%s' % (k, json.dumps(d[k], ensure_ascii=False), c))
    lines.append("}")
    open(p, "w", encoding="utf-8").write("\n".join(lines) + "\n")

for p, v in [
    (r"G:\xianmu\juqing\baoUIIT\lang\en.json", "Metric"),
    (r"G:\xianmu\juqing\baoUIIT\lang\zh-cn.json", "指标"),
]:
    d = json.load(open(p, encoding="utf-8"))
    if "anl_metric" not in d:
        d["anl_metric"] = v
        dump(p, d)
        print(p.split("\\")[-1], "added anl_metric =", v)
