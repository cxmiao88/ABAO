#!/usr/bin/env python3
import hashlib

pwd = '30ke7hbz'
print('md5(pwd):', hashlib.md5(pwd.encode()).hexdigest())
print('target  :', '9b071648daf087639046094929360471')

# 尝试带盐组合
for salt in ['', 'mw', 'mdserver', 'panel']:
    for combo in [salt + pwd, pwd + salt]:
        h = hashlib.md5(combo.encode()).hexdigest()
        if h == '9b071648daf087639046094929360471':
            print('MATCH:', repr(combo))
