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
    (r"G:\xianmu\juqing\baoUIIT\lang\en.json", "Switch team"),
    (r"G:\xianmu\juqing\baoUIIT\lang\zh-cn.json", "切换团队"),
]:
    d = json.load(open(p, encoding="utf-8"))
    if "team_switch_title" not in d:
        d["team_switch_title"] = v
        dump(p, d)
        print(p.split("\\")[-1], "added team_switch_title =", v)
    else:
        print(p.split("\\")[-1], "exists", d["team_switch_title"])
