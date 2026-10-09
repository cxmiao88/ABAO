import io
p1 = r'G:\xianmu\juqing\baoUIIT\resources\views\livewire\dashboard.blade.php'
s = io.open(p1, encoding='utf-8').read()
s = s.replace('name="server" class="size-3.5"', 'name="servers" class="size-3.5"', 1)
io.open(p1, 'w', encoding='utf-8', newline='\n').write(s)
print('dash icons fixed:', 'name="servers" class="size-3.5"' in s)

p2 = r'G:\xianmu\juqing\baoUIIT\resources\views\livewire\dashboard\baota.blade.php'
b = io.open(p2, encoding='utf-8').read()
b = b.replace('name="cube" class="size-5"', 'name="grid" class="size-5"', 1)
b = b.replace('name="store" class="size-4.5"', 'name="grid" class="size-4.5"', 1)
io.open(p2, 'w', encoding='utf-8', newline='\n').write(b)
print('baota icons fixed:', 'cube' not in b, 'store' not in b)
