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

add = {
    "sel_choose_resource": ("Choose a resource", "选择要部署的资源"),
    "sel_git_source": ("Git source", "Git 源码"),
    "sel_docker_source": ("Docker source", "Docker 来源"),
}
for p, lang in [(r"G:\xianmu\juqing\baoUIIT\lang\en.json", 0), (r"G:\xianmu\juqing\baoUIIT\lang\zh-cn.json", 1)]:
    d = json.load(open(p, encoding="utf-8"))
    for k, (en, zh) in add.items():
        if k not in d:
            d[k] = en if lang == 0 else zh
    dump(p, d)
    print(p.split("\\")[-1], "updated")

p = r"G:\xianmu\juqing\baoUIIT\resources\views\livewire\project\new\select.blade.php"
b = open(p, encoding="utf-8").read()
pairs = [
    ('<x-application.settings-section title="Choose a resource" flush>',
     '<x-application.settings-section :title="__(\'sel_choose_resource\')" flush>'),
    ("<h2>Applications</h2>", "<h2>{{ __('sel_applications') }}</h2>"),
    ("<h2>Databases</h2>", "<h2>{{ __('sel_databases') }}</h2>"),
    ("<h2>Services</h2>", "<h2>{{ __('sel_services') }}</h2>"),
    ("Git source", "{{ __('sel_git_source') }}"),
    ("Docker source", "{{ __('sel_docker_source') }}"),
]
miss = 0
for old, new in pairs:
    if old not in b:
        print("MISS:", old[:60])
        miss += 1
    b = b.replace(old, new)
open(p, "w", encoding="utf-8").write(b)
print("blade done, misses:", miss)
