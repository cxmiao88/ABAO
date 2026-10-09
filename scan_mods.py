import os, re

root = '/www/server/mdserver-web/web'
for base, dirs, files in os.walk(root):
    if '/lib' in base or '__pycache__' in base:
        continue
    # 打印所有 admin 下的模块目录（一层）
    depth = base.replace(root, '').count(os.sep)
    if depth <= 2:
        print(base.replace(root, ''), '->', [f for f in files if f.endswith('.py')][:8])
