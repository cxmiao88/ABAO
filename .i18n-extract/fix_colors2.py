import io

files = [
    r'G:\xianmu\juqing\baoUIIT\resources\views\layouts\panel.blade.php',
    r'G:\xianmu\juqing\baoUIIT\resources\views\livewire\panel\home.blade.php',
]

repl = [
    # leftover exact fixes
    ('border-r border-neutral-200 bg-[#161616] dark:border-white/[0.06] dark:bg-[#141414]', 'border-r border-neutral-800 bg-[#161616]'),
    ('border-b border-neutral-200 px-4 py-4 dark:border-white/[0.06]', 'border-b border-neutral-800 px-4 py-4'),
    ("bg-violet-600/10 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300", "bg-violet-600/15 text-violet-300"),
    ('border-t border-neutral-200 p-2 dark:border-white/[0.06]', 'border-t border-neutral-800 p-2'),
    ('text-neutral-600 hover:bg-neutral-200/60 dark:text-neutral-400 dark:hover:bg-white/[0.05]', 'text-neutral-400 hover:bg-[#2a2a2a]'),
    ('border-b border-neutral-200 bg-[#161616] px-4 dark:border-white/[0.06] dark:bg-[#141414]', 'border-b border-neutral-800 bg-[#161616] px-4'),
    ('text-violet-600 dark:text-violet-400', 'text-violet-400'),
    ('border border-neutral-200 bg-[#222222] p-4 shadow-sm border-neutral-800', 'border border-neutral-800 bg-[#222222] p-4 shadow-sm'),
    ('hover:underline dark:text-violet-400', 'hover:underline text-violet-400'),
    ('text-neutral-800 dark:text-neutral-200', 'text-neutral-200'),
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
    print(p.split('\\')[-1], 'fixes:', cnt)
    for kw in ['bg-white', 'dark:bg-', 'dark:border', 'dark:text', 'border-neutral-200', 'text-neutral-800']:
        if kw in s:
            print('  leftover', kw, ':', s.count(kw))
