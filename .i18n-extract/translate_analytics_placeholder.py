#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
p = r"G:\xianmu\juqing\baoUIIT\resources\views\livewire\analytics-placeholder.blade.php"
c = open(p, encoding="utf-8").read()
pairs = [
    ('<h1 class="min-w-0 text-[24px]! leading-7! font-semibold! tracking-tight!">Analytics</h1>',
     '<h1 class="min-w-0 text-[24px]! leading-7! font-semibold! tracking-tight!">{{ __(\'nav.analytics\') }}</h1>'),
    ("Request traffic across every application and server, reported by Sentinel.",
     "{{ __('anl_header_desc') }}"),
    ('title="Overview">', 'title="{{ __(\'anl_overview\') }}">'),
    ('title="Requests">', 'title="{{ __(\'anl_requests\') }}">'),
    ('title="Status codes">', 'title="{{ __(\'anl_status_codes\') }}">'),
    ('title="Top hosts" flush>', 'title="{{ __(\'anl_top_hosts\') }}" flush>'),
    ('title="Top applications" flush>', 'title="{{ __(\'anl_top_apps\') }}" flush>'),
    ('title="Top paths" flush>', 'title="{{ __(\'anl_top_paths\') }}" flush>'),
    ('title="Countries" flush>', 'title="{{ __(\'anl_countries\') }}" flush>'),
]
for old, new in pairs:
    if old not in c:
        print("MISS:", old[:50])
    c = c.replace(old, new)
open(p, "w", encoding="utf-8").write(c)
print("OK analytics-placeholder")
