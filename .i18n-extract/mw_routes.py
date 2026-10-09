# -*- coding: utf-8 -*-
import re, os
root = "/www/server/mdserver-web/web/admin"
pats = set()
for dp, dn, fn in os.walk(root):
    for f in fn:
        if not f.endswith(".py"):
            continue
        p = os.path.join(dp, f)
        try:
            s = open(p, encoding="utf-8", errors="ignore").read()
        except Exception:
            continue
        for m in re.finditer(r"Blueprint\(\s*['\"]([^'\"]+)['\"]\s*,\s*__name__\s*,\s*url_prefix\s*=\s*['\"]([^'\"]+)['\"]", s):
            pats.add((m.group(1), m.group(2)))
for name, pref in sorted(pats):
    print(name, "=>", pref)
print("TOTAL", len(pats))
