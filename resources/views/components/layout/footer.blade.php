<footer class="py-2 border-t border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-2 flex-wrap">
        <div class="flex items-center gap-2 flex-wrap">
            <span>&copy;{{ date('Y') }} Built by <a href="https://chemaclass.com/" target="_blank" class="hover:underline">Chema</a></span>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <a href="https://getalby.com/p/chemaclass" target="_blank" class="flex items-center gap-1 hover:underline">
                <i data-lucide="bitcoin" class="w-4 h-4 text-orange-500 dark:text-[--btc-orange-dark]"></i>
                {{ __("home.footer.support") }}
            </a>
        <span class="hidden sm:inline">•</span>
        <a href="https://github.com/Chemaclass/satscribe" target="_blank" class="flex items-center gap-1 hover:underline">
            <svg data-lucide="github" class="w-4 h-4"></svg>
            {{ __('GitHub') }}
        </a>
        @if(config('app.last_commit') !== 'unknown')
            <span class="hidden sm:inline">•</span>
            <div title="Released commit">{{ substr(config('app.last_commit'), 0, 7) }}</div>
        @endif
        <span class="hidden sm:inline">•</span>
        <select class="nav-link" onchange="const p=new URLSearchParams(window.location.search);p.set('lang', this.value);window.location.search=p.toString();">
            <option value="en" @selected(app()->getLocale()==='en')>EN</option>
            <option value="de" @selected(app()->getLocale()==='de')>DE</option>
            <option value="es" @selected(app()->getLocale()==='es')>ES</option>
        </select>
    </div>
</footer>
