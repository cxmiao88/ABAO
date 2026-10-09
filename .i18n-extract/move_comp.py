import io, re
p = r'G:\xianmu\juqing\baoUIIT\resources\views\livewire\server\show.blade.php'
s = io.open(p, encoding='utf-8').read()
# remove component line from inside section
s2 = re.sub(r'\n\s*<livewire:server\.system-overview[^/]*/>', '', s, count=1)
print('removed:', s2 != s)
# insert after first settings-section close
anchor = '</x-application.settings-section>'
idx = s2.find(anchor)
print('anchor idx:', idx)
if idx > 0:
    comp = "\n\n<livewire:server.system-overview :server=\"$server\" :key=\"'system-overview-'.$server->uuid\" />\n"
    s3 = s2[:idx+len(anchor)] + comp + s2[idx+len(anchor):]
    io.open(p, 'w', encoding='utf-8', newline='\n').write(s3)
    s4 = io.open(p, encoding='utf-8').read()
    print('count:', s4.count('system-overview'))
