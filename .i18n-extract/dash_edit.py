import io, re
p = r'G:\xianmu\juqing\baoUIIT\resources\views\livewire\dashboard.blade.php'
s = io.open(p, encoding='utf-8').read()

# 1. insert mode switcher after the @php block (after line with $hasTrafficAnalytics)
switcher = '''
    {{-- 仪表盘模式切换 --}}
    <div class="mb-2 flex min-w-0 items-center gap-2">
        <div class="inline-flex items-center rounded-lg border border-neutral-200 bg-white p-1 shadow-sm dark:border-white/[0.08] dark:bg-white/[0.04]">
            <button type="button" wire:click="switchDashboardMode('baota')"
                @class([
                    'inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-[13px] font-medium transition-colors',
                    'bg-emerald-600 text-white shadow-sm' => $dashboardMode === 'baota',
                    'text-neutral-500 hover:bg-neutral-100 dark:text-fg-dim dark:hover:bg-white/[0.06]' => $dashboardMode !== 'baota',
                ])>
                <x-reicon name="server" class="size-3.5" />
                {{ __('dash_mode_baota') }}
            </button>
            <button type="button" wire:click="switchDashboardMode('classic')"
                @class([
                    'inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-[13px] font-medium transition-colors',
                    'bg-neutral-800 text-white shadow-sm dark:bg-white dark:text-neutral-900' => $dashboardMode === 'classic',
                    'text-neutral-500 hover:bg-neutral-100 dark:text-fg-dim dark:hover:bg-white/[0.06]' => $dashboardMode !== 'classic',
                ])>
                <x-reicon name="grid" class="size-3.5" />
                {{ __('dash_mode_classic') }}
            </button>
        </div>
    </div>

    @if ($dashboardMode === 'baota')
        @include('livewire.dashboard.baota', [
            'servers' => $servers,
            'projects' => $projects,
            'dashboardServers' => $dashboardServers,
        ])
    @else
'''
if switcher not in s:
    anchor = "@endphp\n\n    <div class=\"flex min-w-0 flex-col gap-8\">"
    new_anchor = "@endphp" + switcher + "\n    <div class=\"flex min-w-0 flex-col gap-8\">"
    s = s.replace(anchor, new_anchor, 1)
    print('switcher inserted:', switcher[:40] in s)

# 2. close the @else branch: the original content ends with "</div>\n</div>"; append @endif before final </div>
# The file ends with:
#        </section>
#    </div>
# </div>
old_end = """        </section>
    </div>
</div>"""
new_end = """        </section>
        </div>
    @endif
</div>"""
if old_end in s:
    s = s.replace(old_end, new_end, 1)
    print('endif appended')
else:
    # fallback: find last </div> and insert
    print('old_end NOT found, checking structure...')
    print(repr(s[-120:]))

io.open(p, 'w', encoding='utf-8', newline='\n').write(s)
