#!/usr/bin/env python3
import sqlite3
import sys

conn = sqlite3.connect('/www/server/mdserver-web/data/panel.db')
cur = conn.cursor()
print("TABLES:", cur.execute("SELECT name FROM sqlite_master WHERE type='table'").fetchall())
try:
    cur.execute("SELECT * FROM users")
    print("USERS:", cur.fetchall())
except Exception as e:
    print("users err:", e)
try:
    cur.execute("PRAGMA table_info(users)")
    print("SCHEMA:", cur.fetchall())
except Exception as e:
    print("schema err:", e)
