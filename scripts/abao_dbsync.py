#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""ABao dbsync：扫描站点 CMS 配置，把一键部署建库密码同步进面板记录文件（幂等，不覆盖已有记录）
覆盖：WordPress wp-config.php / Typecho config.inc.php / Nextcloud config/config.php / Z-Blog zb_users/c_option.php
容器面板记录文件：/var/www/html/storage/app/panel/host-db-passwords.json
"""
import json, os, re, glob, subprocess, sys

PANEL_FILE = '/var/www/html/storage/app/panel/host-db-passwords.json'
WWWROOT = '/www/wwwroot'

def extract_wp(path):
    try:
        s = open(path, encoding='utf-8', errors='ignore').read()
    except Exception:
        return None
    m = re.search(r"define\(\s*['\"]DB_NAME['\"]\s*,\s*['\"]([^'\"]+)['\"]", s)
    p = re.search(r"define\(\s*['\"]DB_PASSWORD['\"]\s*,\s*['\"]([^'\"]+)['\"]", s)
    if m and p:
        return m.group(1).strip(), p.group(1).strip()
    return None

def extract_typecho(path):
    try:
        s = open(path, encoding='utf-8', errors='ignore').read()
    except Exception:
        return None
    db = re.search(r"['\"]database['\"]\s*=>\s*['\"]([^'\"]+)['\"]", s)
    pw = re.search(r"['\"]password['\"]\s*=>\s*['\"]([^'\"]+)['\"]", s)
    if db and pw:
        return db.group(1).strip(), pw.group(1).strip()
    return None

def extract_nextcloud(path):
    try:
        s = open(path, encoding='utf-8', errors='ignore').read()
    except Exception:
        return None
    db = re.search(r"['\"]dbname['\"]\s*=>\s*['\"]([^'\"]+)['\"]", s)
    pw = re.search(r"['\"]dbpassword['\"]\s*=>\s*['\"]([^'\"]+)['\"]", s)
    if db and pw:
        return db.group(1).strip(), pw.group(1).strip()
    return None

def extract_zblog(path):
    try:
        s = open(path, encoding='utf-8', errors='ignore').read()
    except Exception:
        return None
    db = re.search(r"['\"]ZC_DATABASE_NAME['\"]\s*=>\s*['\"]([^'\"]+)['\"]", s)
    pw = re.search(r"['\"]ZC_DATABASE_PASSWORD['\"]\s*=>\s*['\"]([^'\"]+)['\"]", s)
    if db and pw:
        return db.group(1).strip(), pw.group(1).strip()
    return None

def main():
    found = {}
    for d in glob.glob(WWWROOT + '/*'):
        if not os.path.isdir(d):
            continue
        for probe, fn in [
            (os.path.join(d, 'wp-config.php'), extract_wp),
            (os.path.join(d, 'config.inc.php'), extract_typecho),
            (os.path.join(d, 'config', 'config.php'), extract_nextcloud),
            (os.path.join(d, 'zb_users', 'c_option.php'), extract_zblog),
        ]:
            if os.path.isfile(probe):
                r = fn(probe)
                if r and r[0] and r[1]:
                    found[r[0]] = r[1]
    # 读容器现有记录
    try:
        cur_raw = subprocess.check_output(
            ['docker', 'exec', 'coolify', 'sh', '-c', 'cat %s 2>/dev/null || echo {}' % PANEL_FILE],
            timeout=15).decode('utf-8', 'ignore') or '{}'
        cur = json.loads(cur_raw) if cur_raw.strip() else {}
    except Exception:
        cur = {}
    changed = 0
    for db, pwd in found.items():
        if db not in cur:
            cur[db] = pwd
            changed += 1
    if changed > 0:
        payload = json.dumps(cur, ensure_ascii=False)
        proc = subprocess.Popen(
            ['docker', 'exec', '-i', 'coolify', 'sh', '-c', 'umask 077; cat > %s' % PANEL_FILE],
            stdin=subprocess.PIPE, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        proc.communicate(payload.encode('utf-8'))
    print('dbsync: total=%d new=%d' % (len(found), changed), file=sys.stderr)

if __name__ == '__main__':
    main()
