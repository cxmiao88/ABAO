#!/usr/bin/env python3
# -*- coding: utf-8 -*-
p = r"G:\xianmu\juqing\baoUIIT\resources\views\livewire\settings-dropdown.blade.php"
s = open(p, encoding="utf-8").read()
repl = {
    'aria-label="First page" title="First page"': 'aria-label="{{ __(\'set_first_page\') }}" title="{{ __(\'set_first_page\') }}"',
    'aria-label="Previous page" title="Previous page"': 'aria-label="{{ __(\'set_prev_page\') }}" title="{{ __(\'set_prev_page\') }}"',
    'aria-label="Next page" title="Next page"': 'aria-label="{{ __(\'set_next_page\') }}" title="{{ __(\'set_next_page\') }}"',
    'aria-label="Last page" title="Last page"': 'aria-label="{{ __(\'set_last_page\') }}" title="{{ __(\'set_last_page\') }}"',
}
for k, v in repl.items():
    if k in s:
        s = s.replace(k, v)
        print("replaced:", k)
    else:
        print("NOT FOUND:", k)
open(p, "w", encoding="utf-8").write(s)
