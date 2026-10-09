import io, re, subprocess, os

# 1. pull container Show.php
os.system('wsl -d Ubuntu -- docker cp coolify:/var/www/html/app/Livewire/Server/Show.php /mnt/g/xianmu/juqing/baoUIIT/.i18n-extract/Show.cur.php')
p_show = r'G:\xianmu\juqing\baoUIIT\.i18n-extract\Show.cur.php'
s = io.open(p_show, encoding='utf-8').read()

# add public property after a known anchor
anchor = "    public function mount(string $server_uuid)"
prop = "    public ?array $serverStats = null;\n\n"
if 'serverStats' not in s:
    s = s.replace(anchor, prop + anchor, 1)
    print('property added:', 'serverStats' in s)

# add loading in mount after syncData line
anchor2 = "            $this->syncData();"
load = "            $this->syncData();\n            try {\n                $this->serverStats = $this->server->getSystemStats();\n            } catch (\\Throwable $e) {\n                $this->serverStats = null;\n            }"
if 'getSystemStats' not in s:
    s = s.replace(anchor2, load, 1)
    print('mount load added:', 'getSystemStats' in s)

io.open(p_show, 'w', encoding='utf-8', newline='\n').write(s)

# 2. edit show.blade.php: remove the moved component line (after connection section), insert @include inside overview section
p_blade = r'G:\xianmu\juqing\baoUIIT\resources\views\livewire\server\show.blade.php'
b = io.open(p_blade, encoding='utf-8').read()
# remove the standalone component line(s) we inserted earlier (matches line with system-overview)
b2 = re.sub(r'\n\s*<livewire:server\.system-overview[^/]*/>', '', b, count=1)
print('component line removed:', b2 != b)
# insert @include inside overview section, before the server_metadata @if block
anchor3 = "                        @if ($server->server_metadata)"
inc = "                        @include('livewire.server.system-overview-partial', ['stats' => $serverStats])\n\n" + anchor3
if 'system-overview-partial' not in b2:
    b2 = b2.replace(anchor3, inc, 1)
    print('include inserted:', 'system-overview-partial' in b2)
io.open(p_blade, 'w', encoding='utf-8', newline='\n').write(b2)

# 3. rename view to partial (keep file but make it a plain partial using $stats)
p_v = r'G:\xianmu\juqing\baoUIIT\resources\views\livewire\server\system-overview.blade.php'
v = io.open(p_v, encoding='utf-8').read()
# view uses $stats variable already; adjust the @if($stats) to work as partial
v2 = v.replace('@if ($stats)', '@if ($stats)')
io.open(p_v, 'w', encoding='utf-8', newline='\n').write(v2)
os.system('copy /Y "' + p_v + '" "' + r'G:\xianmu\juqing\baoUIIT\resources\views\livewire\server\system-overview-partial.blade.php' + '" >nul')
print('partial view created')
print('DONE')
