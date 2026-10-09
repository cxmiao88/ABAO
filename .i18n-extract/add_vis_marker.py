import io, re
p = r'G:\xianmu\juqing\baoUIIT\resources\views\livewire\server\show.blade.php'
s = io.open(p, encoding='utf-8').read()
# insert visible marker right before the partial include
old = "@include('livewire.server.system-overview-partial', ['stats' => $serverStats])"
m = "SYSTEM-OVERVIEW-MARKER-VISIBLE-999"
if m not in s:
    new = "<div class=\"py-2 text-sm text-red-400\">" + m + "</div>\n                            " + old
    s = s.replace(old, new, 1)
    print('visible marker inserted:', m in s)
# also insert a top-of-file marker
m2 = "TOP-MARKER-555"
if m2 not in s:
    s = "{{-- " + m2 + " --}}\n" + s
    print('top marker inserted:', m2 in s)
io.open(p, 'w', encoding='utf-8', newline='\n').write(s)
