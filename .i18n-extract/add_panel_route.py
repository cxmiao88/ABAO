import io

# 1) Home.php render -> panel layout
p1 = r'G:\xianmu\juqing\baoUIIT\app\Livewire\Panel\Home.php'
s = io.open(p1, encoding='utf-8').read()
old = """    public function render()
    {
        return view('livewire.panel.home');
    }"""
new = """    public function render()
    {
        $server = $this->servers->firstWhere('uuid', $this->selectedServerUuid) ?? $this->servers->first();

        return view('livewire.panel.home')
            ->layout('layouts.panel', [
                'serverInfo' => [
                    'ip' => $server?->ip ?? 'localhost',
                    'os' => 'Linux',
                ],
            ]);
    }"""
assert old in s
s = s.replace(old, new, 1)
io.open(p1, 'w', encoding='utf-8', newline='\n').write(s)
print('Home.php render updated')

# 2) web.php: add panel route inside auth group (after settings routes, before tags)
p2 = r'G:\xianmu\juqing\baoUIIT\routes\web.php'
w = io.open(p2, encoding='utf-8').read()
anchor = """    Route::get('/profile', ProfileIndex::class)->name('profile');"""
panel_block = """    Route::get('/profile', ProfileIndex::class)->name('profile');

    Route::prefix('panel')->group(function () {
        Route::get('/', \\App\\Livewire\\Panel\\Home::class)->name('panel.home');
    });
"""
if "Route::prefix('panel')" not in w:
    assert anchor in w
    w = w.replace(anchor, panel_block, 1)
    io.open(p2, 'w', encoding='utf-8', newline='\n').write(w)
    print('panel route added')
else:
    print('panel route already present')
