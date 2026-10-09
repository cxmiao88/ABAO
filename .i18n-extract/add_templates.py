#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import json, base64, datetime

BASE = r"G:\xianmu\juqing\baoUIIT"
EX = BASE + r"\.i18n-extract"

def b64(name):
    with open(EX + "\\" + name, encoding="utf-8") as f:
        return base64.b64encode(f.read().encode("utf-8")).decode("ascii")

today = datetime.date.today().isoformat() + "T00:00:00+08:00"

templates = {
    "dify": {
        "documentation": "https://docs.dify.ai",
        "slogan": "开源 LLM 应用开发平台：可视化编排 AI 工作流、RAG 知识库与智能体，快速从原型到生产。",
        "tags": ["ai", "llm", "rag", "agent", "workflow", "openai", "dify", "中文"],
        "category": "ai",
        "logo": "svgs/dify.svg",
        "minversion": "0.0.0",
        "template_last_updated_at": today,
        "port": "3000",
        "compose": b64("compose_dify.yaml"),
    },
    "oneapi": {
        "documentation": "https://github.com/songquanpeng/one-api",
        "slogan": "LLM API 管理与分发网关：统一 OpenAI、Claude、Gemini、DeepSeek、豆包等主流模型，支持密钥管理与二次分发。",
        "tags": ["ai", "llm", "api", "openai", "claude", "gemini", "deepseek", "gateway", "one-api", "中文"],
        "category": "ai,api",
        "logo": "svgs/oneapi.svg",
        "minversion": "0.0.0",
        "template_last_updated_at": today,
        "port": "3000",
        "compose": b64("compose_oneapi.yaml"),
    },
    "halo": {
        "documentation": "https://docs.halo.run",
        "slogan": "开源博客建站系统：现代化管理界面、丰富主题与插件生态，中文社区活跃，轻松搭建个人站点。",
        "tags": ["blog", "cms", "halo", "网站", "博客", "中文"],
        "category": "cms",
        "logo": "svgs/halo.svg",
        "minversion": "0.0.0",
        "template_last_updated_at": today,
        "port": "8090",
        "compose": b64("compose_halo.yaml"),
    },
}

for fname in ["service-templates-latest.json", "service-templates.json"]:
    p = BASE + "\\templates\\" + fname
    d = json.load(open(p, encoding="utf-8"))
    for k, v in templates.items():
        if k in d:
            print("SKIP(already exists):", fname, k)
            continue
        d[k] = v
        print("ADDED:", fname, k)
    keys = sorted(d.keys())
    lines = ["{"]
    for i, k in enumerate(keys):
        c = "," if i < len(keys) - 1 else ""
        lines.append('    "%s": %s%s' % (k, json.dumps(d[k], ensure_ascii=False), c))
    lines.append("}")
    open(p, "w", encoding="utf-8").write("\n".join(lines) + "\n")
    print("WROTE:", fname, len(keys), "templates")

# 校验 base64 可解码
for k in templates:
    raw = base64.b64decode(templates[k]["compose"]).decode("utf-8")
    print(k, "compose lines:", len(raw.splitlines()), "| starts:", raw.splitlines()[0])
