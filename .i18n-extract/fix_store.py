import io, re
p = r'G:\xianmu\juqing\baoUIIT\resources\views\livewire\dashboard\baota.blade.php'
s = io.open(p, encoding='utf-8').read()

# 1. fix store link -> project resource create (store/template market)
old_store = """            <a href="{{ route('store.index') }}" {{ wireNavigate() }}
                class="flex items-center gap-3 rounded-xl border border-neutral-200 bg-white p-3 shadow-sm transition-all hover:-translate-y-px hover:border-neutral-300 hover:shadow-md dark:border-white/[0.08] dark:bg-white/[0.05] dark:hover:border-white/[0.14]">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-violet-500/10 text-violet-600 dark:text-violet-400">
                    <x-reicon name="grid" class="size-4.5" />
                </div>
                <span class="truncate text-[13px] font-medium text-neutral-700 dark:text-fg">{{ __('dash_quick_store') }}</span>
            </a>"""
new_store = """            @if ($firstProject = $projects->sortBy('name', SORT_NATURAL)->first())
                @if ($firstProject->environments->first())
                    <a href="{{ route('project.resource.create', ['project_uuid' => $firstProject->uuid, 'environment_uuid' => $firstProject->environments->first()->uuid]) }}" {{ wireNavigate() }}
                        class="flex items-center gap-3 rounded-xl border border-neutral-200 bg-white p-3 shadow-sm transition-all hover:-translate-y-px hover:border-neutral-300 hover:shadow-md dark:border-white/[0.08] dark:bg-white/[0.05] dark:hover:border-white/[0.14]">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-violet-500/10 text-violet-600 dark:text-violet-400">
                            <x-reicon name="grid" class="size-4.5" />
                        </div>
                        <span class="truncate text-[13px] font-medium text-neutral-700 dark:text-fg">{{ __('dash_quick_store') }}</span>
                    </a>
                @endif
            @endif"""
if old_store in s:
    s = s.replace(old_store, new_store, 1)
    print('store link fixed')
else:
    print('store block NOT found, trying regex...')
    s = re.sub(r'\n\s*<a href="\{\{ route\(\'store\.index\'\) \}\}"[\s\S]*?</a>', '\n' + new_store, s, count=1)
    print('regex replace done:', 'store.index' not in s)

# 2. remove the duplicate "创建资源" entry (the @if ($firstEnvironment = ...) block)
dup_old = """            @if ($firstEnvironment = $projects->sortBy('name', SORT_NATURAL)->first()?->environments->first())
                <a href="{{ route('project.resource.create', ['project_uuid' => $projects->sortBy('name', SORT_NATURAL)->first()->uuid, 'environment_uuid' => $firstEnvironment->uuid]) }}" {{ wireNavigate() }}
                    class="flex items-center gap-3 rounded-xl border border-neutral-200 bg-white p-3 shadow-sm transition-all hover:-translate-y-px hover:border-neutral-300 hover:shadow-md dark:border-white/[0.08] dark:bg-white/[0.05] dark:hover:border-white/[0.14]">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400">
                        <x-reicon name="plus" class="size-4.5" />
                    </div>
                    <span class="truncate text-[13px] font-medium text-neutral-700 dark:text-fg">{{ __('dash_quick_new_resource') }}</span>
                </a>
            @endif"""
if dup_old in s:
    s = s.replace(dup_old, '', 1)
    print('dup removed')
else:
    print('dup block NOT found')

io.open(p, 'w', encoding='utf-8', newline='\n').write(s)
print('store.index remaining:', 'store.index' in s)
