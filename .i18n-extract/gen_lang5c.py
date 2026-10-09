#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import json

enp = r"G:\xianmu\juqing\baoUIIT\lang\en.json"
zhp = r"G:\xianmu\juqing\baoUIIT\lang\zh-cn.json"
en = json.load(open(enp, encoding="utf-8"))
zh = json.load(open(zhp, encoding="utf-8"))

EN = {
    "src_sources_title": "Sources", "src_sources": "Sources", "src_git_source_count": "个 Git 来源",
    "src_connected_to_team": "已连接到您的团队", "src_new_source": "New source",
    "src_new_github_app": "New GitHub App", "src_new_gitlab_app": "New GitLab App",
    "src_no_sources": "No sources yet",
    "src_no_sources_helper": "Connect a Git provider to deploy applications directly from your repositories.",
    "src_setup_incomplete": "Setup incomplete", "src_setup_required": "Setup required",
    "src_search_sources": "Search sources", "src_source_singular": "个来源", "src_source_plural": "个来源",
    "src_source": "Source", "src_provider": "Provider",
}
ZH = {
    "src_sources_title": "代码源", "src_sources": "代码源", "src_git_source_count": "个 Git 来源",
    "src_connected_to_team": "已连接到您的团队", "src_new_source": "新建来源",
    "src_new_github_app": "新建 GitHub App", "src_new_gitlab_app": "新建 GitLab App",
    "src_no_sources": "还没有代码源",
    "src_no_sources_helper": "连接 Git 提供商，直接从您的仓库部署应用。",
    "src_setup_incomplete": "设置未完成", "src_setup_required": "需要设置",
    "src_search_sources": "搜索代码源", "src_source_singular": "个来源", "src_source_plural": "个来源",
    "src_source": "来源", "src_provider": "提供商",
}


def dump(p, d):
    lines = ["{"]
    ks = list(d.keys())
    for i, k in enumerate(ks):
        c = "," if i < len(ks) - 1 else ""
        lines.append('    "%s": %s%s' % (k, json.dumps(d[k], ensure_ascii=False), c))
    lines.append("}")
    open(p, "w", encoding="utf-8").write("\n".join(lines) + "\n")


for p, lang in ((enp, EN), (zhp, ZH)):
    d = json.load(open(p, encoding="utf-8"))
    d.update(lang)
    dump(p, d)
    print(p, len(d))
