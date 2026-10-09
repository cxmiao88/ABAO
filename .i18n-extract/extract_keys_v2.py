#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""行级对齐提取 key -> 英文原文映射（v2, difflib）"""
import re, json, subprocess, sys, io, os, difflib

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

repo = r"G:\xianmu\juqing\baoUIIT"
files = [
    "resources/views/livewire/project/application/general.blade.php",
    "resources/views/livewire/project/application/heading.blade.php",
    "resources/views/livewire/project/application/source.blade.php",
]
key_re = re.compile(r"__\('application\.([a-z0-9_]+)'")

def clean_en(line):
    """从一行中提取可读的英文文本片段"""
    # 去掉 blade 标签与 php 表达式
    s = line
    s = re.sub(r"<x-[a-z0-9.\-]+\b", " ", s)          # 组件开始标签
    s = re.sub(r"<x-[a-z0-9.\-]+/>", " ", s)
    s = re.sub(r"<[^>]+>", " ", s)                    # 其他标签
    s = re.sub(r"\{\{.*?\}\}", " ", s)                # blade 输出
    s = re.sub(r"\{!!.*?!!\}", " ", s)
    s = re.sub(r"@[a-z]+(\([^)]*\))?", " ", s)        # blade 指令
    s = re.sub(r"\s+", " ", s).strip()
    return s

def extract_attrs(line):
    """从属性行提取 (attr, value) 列表，如 title=X helper=Y label=Z"""
    attrs = []
    for m in re.finditer(r'(title|helper|label|description|placeholder|buttonTitle|confirmationLabel|shortConfirmationLabel|step2ButtonText|step1ButtonText|confirmationText)\s*=\s*"([^"]*)"', line):
        attrs.append((m.group(1), m.group(2)))
    return attrs

mapping = {}
for f in files:
    head = subprocess.run(["git", "-C", repo, "show", f"HEAD:{f}"],
                          capture_output=True, text=True, encoding="utf-8", errors="replace").stdout.splitlines()
    with open(os.path.join(repo, f), encoding="utf-8") as fh:
        work = fh.read().splitlines()
    sm = difflib.SequenceMatcher(None, head, work, autojunk=False)
    for tag, i1, i2, j1, j2 in sm.get_opcodes():
        if tag != "replace":
            continue
        old_lines = head[i1:i2]
        new_lines = work[j1:j2]
        # 在 new_lines 中找 key
        new_text = "\n".join(new_lines)
        old_text = "\n".join(old_lines)
        keys = key_re.findall(new_text)
        if not keys:
            continue
        # 收集 old 文本中所有可读英文片段
        old_frags = []
        for ol in old_lines:
            for _, v in extract_attrs(ol):
                if v.strip():
                    old_frags.append(v.strip())
            c = clean_en(ol)
            # 提取引号内英文短语
            for m in re.finditer(r"'([^']{2,})'", ol):
                t = m.group(1).strip()
                if t and not re.match(r'^[a-z0-9_\-./]+$', t) and not re.search(r'[<>{$]', t):
                    old_frags.append(t)
            if c and len(c) > 2 and not re.match(r'^[a-z0-9_\-./]+$', c):
                old_frags.append(c)
        # 去重保序
        seen = set()
        uniq = []
        for x in old_frags:
            if x not in seen:
                seen.add(x)
                uniq.append(x)
        # 分配：key 数量与片段数量近似匹配时按序分配
        if len(keys) == 1:
            mapping[keys[0]] = uniq[0] if uniq else ""
        else:
            for idx, k in enumerate(keys):
                mapping[k] = uniq[idx] if idx < len(uniq) else ""

out_path = r"G:\xianmu\juqing\baoUIIT\.i18n-extract\keymap.json"
os.makedirs(os.path.dirname(out_path), exist_ok=True)
with open(out_path, "w", encoding="utf-8") as f:
    json.dump(mapping, f, ensure_ascii=False, indent=1)
print(f"total keys: {len(mapping)}")
for k, v in mapping.items():
    print(f"{k} => {v}")
