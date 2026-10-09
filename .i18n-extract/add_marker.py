import io, re
p = r'G:\xianmu\juqing\baoUIIT\resources\views\livewire\server\show.blade.php'
s = io.open(p, encoding='utf-8').read()
# add marker after connection section open
m1 = "MARKER-CONNECTION-77"
if m1 not in s:
    s = s.replace('<x-application.settings-section id="server-connection-section"', '<x-application.settings-section id="server-connection-section" data-marker="' + m1 + '"', 1)
# add marker after overview section open
m2 = "MARKER-OVERVIEW-88"
if m2 not in s:
    s = s.replace('<x-application.settings-section id="server-overview-section"', '<x-application.settings-section id="server-overview-section" data-marker="' + m2 + '"', 1)
io.open(p, 'w', encoding='utf-8', newline='\n').write(s)
print('markers added:', m1 in s, m2 in s)
