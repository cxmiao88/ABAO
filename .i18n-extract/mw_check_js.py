# -*- coding: utf-8 -*-
import sys
p = "/www/server/mdserver-web/web/static/app/index.js"
s = open(p, encoding="utf-8").read()
print("JS_LEN", len(s))
print("OPEN_CURLY", s.count("{"))
print("CLOSE_CURLY", s.count("}"))
print("OPEN_PAREN", s.count("("))
print("CLOSE_PAREN", s.count(")"))
# 检查是否有残留旧色值
import re
old = re.findall(r"#(?:888888|f7b851|52a9ff)|rgba\(255, 140|rgba\(30, 144", s)
print("OLD_COLORS_LEFT", len(old))
print("TV_USES", s.count("tv."))
