#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""ABao 安装脚本用：给 Coolify routes/web.php 打 ABao panel-api 补丁（幂等）
用法: patch_routes.py <web.php路径> <seg_a.php路径> <seg_b.php路径>
兼容 Python 2 / 3。
"""
from __future__ import print_function
import io, sys

def read(path):
    with io.open(path, "rb") as f:
        return f.read().decode("utf-8")

def write(path, text):
    with io.open(path, "wb") as f:
        f.write(text.encode("utf-8"))

def ensure_new_routes(src, seg_a, seg_b):
    """已打补丁但缺新增路由时，按锚点增量补齐（幂等）。返回 (src, changed)。"""
    changed = False
    # B03-49：公共 resolve 路由（seg_a 内新增行）
    if "host/phpmyadmin/resolve" not in src:
        line = [l for l in seg_a.split("\n") if "pma-resolve" in l]
        if line:
            anchor = "->name('panel-api.login');"
            if anchor in src:
                src = src.replace(anchor, anchor + "\n" + line[0], 1)
                changed = True
    # B03-49：auth 组 pma-token 路由（seg_b 内新增行）
    if "host-databases.pma-token" not in src:
        line = [l for l in seg_b.split("\n") if "pma-token" in l]
        if line:
            anchor = "->name('host-databases.password');"
            if anchor in src:
                src = src.replace(anchor, anchor + "\n" + line[0], 1)
                changed = True
    return src, changed

def main():
    if len(sys.argv) < 4:
        print("usage: patch_routes.py <web.php> <seg_a.php> <seg_b.php>")
        return 2
    web_path, seg_a_path, seg_b_path = sys.argv[1], sys.argv[2], sys.argv[3]
    src = read(web_path)
    if "panel-api/health" in src and "prefix('panel-api')" in src:
        # 已打补丁：仅补齐新增路由（幂等）
        src, changed = ensure_new_routes(src, read(seg_a_path), read(seg_b_path))
        if changed:
            write(web_path, src)
            print("patched-incremental")
            return 0
        print("already-patched")
        return 0
    seg_a = read(seg_a_path).rstrip("\n")
    seg_b = read(seg_b_path).rstrip("\n")
    marker = "Route::middleware(['auth', 'verified'])->group"
    idx = src.find(marker)
    if idx < 0:
        print("marker-not-found")
        return 1
    # 1) seg_a 插到 marker 之前（公共 health/login）
    src = src[:idx] + seg_a + "\n\n" + src[idx:]
    # 2) 在 auth 组开头的 { 之后插入 seg_b（panel-api 全组）
    idx2 = src.find(marker)
    brace = src.find("{", idx2)
    nl = src.find("\n", brace)
    src = src[: nl + 1] + seg_b + "\n" + src[nl + 1 :]
    write(web_path, src)
    print("patched")
    return 0

if __name__ == "__main__":
    sys.exit(main())
