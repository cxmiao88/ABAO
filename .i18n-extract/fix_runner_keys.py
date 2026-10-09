#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import json

enp = r"G:\xianmu\juqing\baoUIIT\lang\en.json"
zhp = r"G:\xianmu\juqing\baoUIIT\lang\zh-cn.json"

en = json.load(open(enp, encoding="utf-8"))
zh = json.load(open(zhp, encoding="utf-8"))

en["src.do_not_run_jobs"] = "Do not run workflow jobs"
en["src.run_jobs_on_build"] = "Run workflow jobs on build servers"
zh["src.do_not_run_jobs"] = "不运行工作流任务"
zh["src.run_jobs_on_build"] = "在构建服务器上运行工作流任务"


def dump(p, d):
    lines = ["{"]
    ks = list(d.keys())
    for i, k in enumerate(ks):
        c = "," if i < len(ks) - 1 else ""
        lines.append('    "%s": %s%s' % (k, json.dumps(d[k], ensure_ascii=False), c))
    lines.append("}")
    open(p, "w", encoding="utf-8").write("\n".join(lines) + "\n")


dump(enp, en)
dump(zhp, zh)
print("ok", len(en), len(zh))
