import io, re

files = [
    r'G:\xianmu\juqing\baoUIIT\resources\views\layouts\panel.blade.php',
    r'G:\xianmu\juqing\baoUIIT\resources\views\livewire\panel\home.blade.php',
]

# (old, new) exact replacements, ordered specific->generic
repl = [
    # page bg / chrome
    ('bg-neutral-100 dark:bg-[#1a1a1a]', 'bg-[#1b1b1b]'),
    ('bg-white/70 dark:bg-[#141414]', 'bg-[#161616]'),
    ('dark:text-inherit text-black', 'text-neutral-100'),
    # aside / nav
    ('bg-white/70', 'bg-[#161616]'),
    ('text-neutral-600 hover:bg-neutral-200/60 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-white/[0.05] dark:hover:text-white',
     'text-neutral-400 hover:bg-[#2a2a2a] hover:text-white'),
    ('text-neutral-400 dark:text-neutral-600', 'text-neutral-500'),
    ('bg-neutral-200 px-1.5 py-0.5 text-[10px] text-neutral-500 dark:bg-white/[0.06] dark:text-neutral-500',
     'bg-[#2a2a2a] px-1.5 py-0.5 text-[10px] text-neutral-500'),
    # cards
    ('bg-white p-4 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.04]', 'bg-[#222222] p-4 shadow-sm border-neutral-800'),
    ('rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.04]', 'rounded-xl border border-neutral-800 bg-[#222222] p-4 shadow-sm'),
    ('border border-neutral-200/80 p-3 dark:border-white/[0.06]', 'border border-neutral-800 p-3'),
    # sub blocks / badges
    ('bg-neutral-100/80 p-3 text-[12px] text-neutral-600 dark:bg-white/[0.04] dark:text-neutral-400', 'bg-[#2a2a2a] p-3 text-[12px] text-neutral-400'),
    ('bg-neutral-200/70 px-2 py-1 text-[11px] font-medium dark:bg-white/[0.06]', 'bg-[#2a2a2a] px-2 py-1 text-[11px] font-medium'),
    ('bg-neutral-200/70 px-2 py-0.5 text-[11px] text-neutral-600 dark:bg-white/[0.06] dark:text-neutral-400', 'bg-[#2a2a2a] px-2 py-0.5 text-[11px] text-neutral-400'),
    ('rounded-lg border border-dashed border-neutral-300 p-4 text-center text-[12px] text-neutral-500 dark:border-white/[0.1] dark:text-neutral-400',
     'rounded-lg border border-dashed border-neutral-700 p-4 text-center text-[12px] text-neutral-400'),
    # progress track
    ('h-1.5 overflow-hidden rounded-full bg-neutral-100 dark:bg-white/[0.08]', 'h-1.5 overflow-hidden rounded-full bg-[#333333]'),
    ('h-2 overflow-hidden rounded-full bg-neutral-100 dark:bg-white/[0.08]', 'h-2 overflow-hidden rounded-full bg-[#333333]'),
    # icon chip neutral
    ('bg-neutral-200/70 text-neutral-600 dark:bg-white/[0.06] dark:text-neutral-300', 'bg-[#2a2a2a] text-neutral-300'),
    # server ring
    ("ring-1 ring-violet-500/40", "ring-1 ring-violet-500/60"),
    # texts
    ('text-neutral-900 dark:text-white', 'text-white'),
    ('text-neutral-700 dark:text-neutral-300', 'text-neutral-200'),
    ('text-neutral-600 dark:text-neutral-300', 'text-neutral-300'),
    ('text-neutral-500 dark:text-neutral-400', 'text-neutral-400'),
    ('text-neutral-400 dark:text-neutral-500', 'text-neutral-500'),
    ('text-neutral-300 dark:text-neutral-600', 'text-neutral-600'),
    ('text-neutral-500 dark:text-fg-dim', 'text-neutral-400'),
    # divider
    ('border-neutral-200 dark:border-white/[0.06]', 'border-neutral-800'),
]

for p in files:
    s = io.open(p, encoding='utf-8').read()
    cnt = 0
    for old, new in repl:
        if old in s:
            n = s.count(old)
            s = s.replace(old, new)
            cnt += n
    io.open(p, 'w', encoding='utf-8', newline='\n').write(s)
    print(p.split('\\')[-1], 'replacements:', cnt)
    # report leftovers
    for kw in ['bg-white', 'dark:bg-', 'dark:border', 'dark:text', 'border-neutral-200']:
        if kw in s:
            print('  leftover', kw, ':', s.count(kw))
