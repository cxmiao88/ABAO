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

for p, v in [(r"G:\xianmu\juqing\baoUIIT\lang\en.json", "View all"), (r"G:\xianmu\juqing\baoUIIT\lang\zh-cn.json", "查看全部")]:
    d = json.load(open(p, encoding="utf-8"))
    if "dash_view_all" not in d:
        d["dash_view_all"] = v
        dump(p, d)
        print(p.split("\\")[-1], "added =", v)
    else:
        print(p.split("\\")[-1], "exists =", d["dash_view_all"])
