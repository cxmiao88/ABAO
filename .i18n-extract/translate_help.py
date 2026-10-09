#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import json

def dump(p, d):
    keys = sorted(d.keys())
    lines = ["{"]
    for i, k in enumerate(keys):
        c = "," if i < len(keys) - 1 else ""
        lines.append('    "%s": %s%s' % (k, json.dumps(d[k], ensure_ascii=False), c))
    lines.append("}")
    open(p, "w", encoding="utf-8").write("\n".join(lines) + "\n")

KEYS = {
    "help_title": ("Tell us what you need", "告诉我们您需要什么"),
    "help_desc": ("Share a problem, question, or idea. Include enough context for us to understand what happened.", "分享一个问题、疑问或想法。请提供足够的上下文，以便我们了解发生了什么。"),
    "help_subject": ("Subject", "主题"),
    "help_subject_ph": ("A short summary of your feedback", "您的反馈的简短摘要"),
    "help_details": ("Details", "详情"),
    "help_details_ph": ("What were you trying to do, what happened, and what would you like to see instead?", "您当时想做什么、发生了什么，以及您希望看到什么不同的结果？"),
    "help_reply_note": ("Replies are sent to your account email.", "回复将发送到您的账户邮箱。"),
    "help_send": ("Send feedback", "发送反馈"),
}

for p, lang in [(r"G:\xianmu\juqing\baoUIIT\lang\en.json", 0), (r"G:\xianmu\juqing\baoUIIT\lang\zh-cn.json", 1)]:
    d = json.load(open(p, encoding="utf-8"))
    added = 0
    for k, (en, zh) in KEYS.items():
        if k not in d:
            d[k] = en if lang == 0 else zh
            added += 1
    if added:
        dump(p, d)
        print(p.split("\\")[-1], "added:", added)

p = r"G:\xianmu\juqing\baoUIIT\resources\views\livewire\help.blade.php"
c = open(p, encoding="utf-8").read()
pairs = [
    ("Tell us what you need", "{{ __('help_title') }}"),
    ("Share a problem, question, or idea. Include enough context for us to\n                understand what happened.",
     "{{ __('help_desc') }}"),
    ('<x-forms.input minlength="3" required id="subject" label="Subject"\n            placeholder="A short summary of your feedback" autofocus />',
     '<x-forms.input minlength="3" required id="subject" :label="__(\'help_subject\')"\n            :placeholder="__(\'help_subject_ph\')" autofocus />'),
    ('<x-forms.textarea minlength="10" maxlength="1000" required rows="8" id="description" label="Details"\n            class="font-sans" spellcheck\n            placeholder="What were you trying to do, what happened, and what would you like to see instead?" />',
     '<x-forms.textarea minlength="10" maxlength="1000" required rows="8" id="description" :label="__(\'help_details\')"\n            class="font-sans" spellcheck\n            :placeholder="__(\'help_details_ph\')" />'),
    ("Replies are sent to your account email.", "{{ __('help_reply_note') }}"),
    ("                Send feedback\n", "                {{ __('help_send') }}\n"),
]
miss = 0
for old, new in pairs:
    if old not in c:
        print("MISS:", old[:60].replace("\n", "\\n"))
        miss += 1
    c = c.replace(old, new)
open(p, "w", encoding="utf-8").write(c)
print("OK help, misses:", miss)
