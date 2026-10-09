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

ADD = {
    "nav.search": ("Search", "搜索"),
    "gs_placeholder": ("Search resources, paths, everything (type new for create)...", "搜索资源、路径、所有内容（输入 new 可创建）…"),
    "crumb_search": ("Search :title", "搜索 :title"),
    "project.unit_resource": ("resource", "个资源"),
    "project.unit_resources": ("resources", "个资源"),
    "project.in_prefix": ("in :project", ":project 中有"),
}

for p, lang in [(r"G:\xianmu\juqing\baoUIIT\lang\en.json", 0), (r"G:\xianmu\juqing\baoUIIT\lang\zh-cn.json", 1)]:
    d = json.load(open(p, encoding="utf-8"))
    changed = False
    for k, (en, zh) in ADD.items():
        if k not in d:
            d[k] = en if lang == 0 else zh
            changed = True
    if changed:
        dump(p, d)
        print(p.split("\\")[-1], "keys added:", len([k for k in ADD if k in d]))
