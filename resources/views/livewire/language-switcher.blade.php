<div class="flex items-center gap-1">
    <button
        type="button"
        wire:click="setLocale('en')"
        class="px-2 py-1 text-xs rounded {{ $locale === 'en' ? 'bg-neutral-200 dark:bg-white/10 font-semibold' : 'text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300' }}"
        title="English"
    >
        EN
    </button>
    <button
        type="button"
        wire:click="setLocale('zh-cn')"
        class="px-2 py-1 text-xs rounded {{ $locale === 'zh-cn' ? 'bg-neutral-200 dark:bg-white/10 font-semibold' : 'text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300' }}"
        title="中文"
    >
        中文
    </button>
</div>
