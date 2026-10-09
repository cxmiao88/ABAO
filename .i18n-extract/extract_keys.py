#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""从 git diff 提取 application.* key -> 英文原文 映射"""
import re, json, subprocess, sys, io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

repo = r"G:\xianmu\juqing\baoUIIT"
files = [
    "resources/views/livewire/project/application/general.blade.php",
    "resources/views/livewire/project/application/heading.blade.php",
    "resources/views/livewire/project/application/source.blade.php",
]
out = subprocess.run(["git", "-C", repo, "diff", "--", *files],
                     capture_output=True, text=True, encoding="utf-8", errors="replace")
diff = out.stdout

# 逐行解析：收集 '-' 行（英文原文）与 '+' 行（__('application.xxx')）
pairs = []  # (key, en_text)
hunk_removed = []
key_re = re.compile(r"__\('application\.([a-z0-9_]+)'")
en_clean_re = re.compile(r"[<>]|\{\{|\}\}")

lines = diff.splitlines()
i = 0
while i < len(lines):
    ln = lines[i]
    if ln.startswith("+++") or ln.startswith("---"):
        i += 1
        continue
    if ln.startswith("+"):
        keys = key_re.findall(ln)
        if keys:
            # 找到同一 hunk 内对应的删除行作为英文候选
            pass
        i += 1
        continue
    i += 1

# 更简单的策略：逐 hunk 处理
# 重建 hunk：记录每个 + 行所在的上下文与 - 行
hunks = []
cur = []
for ln in lines:
    if ln.startswith("@@") and cur:
        hunks.append(cur)
        cur = []
    if ln.startswith("@@") or ln.startswith("+") or ln.startswith("-") or ln.startswith(" "):
        cur.append(ln)
if cur:
    hunks.append(cur)

mapping = {}
for h in hunks:
    removed = []      # 英文文本候选
    context_removed = []
    # 先收集本 hunk 所有 - 行（保留顺序）
    for ln in h:
        if ln.startswith("-"):
            removed.append(ln[1:].strip())
    # 再扫描 + 行找 key
    idx = 0
    rem_iter = iter(removed)
    for ln in h:
        if ln.startswith("+"):
            for m in key_re.finditer(ln):
                key = m.group(1)
                if key not in mapping:
                    # 取下一个未消费的删除行（去标签、去 {{ }}）
                    try:
                        cand = next(rem_iter)
                    except StopIteration:
                        cand = ""
                    cand = re.sub(r"<[^>]*>", "", cand)
                    cand = cand.replace("{{", "").replace("}}", "").strip()
                    mapping[key] = cand
        elif ln.startswith("-"):
            pass

out_path = r"G:\xianmu\juqing\baoUIIT\.i18n-extract\keymap.json"
import os
os.makedirs(os.path.dirname(out_path), exist_ok=True)
with open(out_path, "w", encoding="utf-8") as f:
    json.dump(mapping, f, ensure_ascii=False, indent=1)
print(f"total keys: {len(mapping)}")
for k, v in mapping.items():
    print(f"{k} => {v}")
