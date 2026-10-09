import re

p = '/www/server/mdserver-web/web/utils/plugin.py'
s = open(p, encoding='utf-8', errors='ignore').read()
# 找 type 分类名定义（可能是 dict）
for m in re.finditer(r'[\u4e00-\u9fff]+', s):
    t = m.group(0)
    if any(k in t for k in ['软件', '环境', '数据库', '备份', '监控', '安全', '工具', '应用', '插件']):
        print(t)
