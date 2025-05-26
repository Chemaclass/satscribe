<footer class="text-center py-2 border-t border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400">
    <div class="flex justify-center items-center gap-2 flex-wrap">
        <span>&copy;{{ date('Y') }} Built by <a href="https://chemaclass.com/" target="_blank" class="hover:underline">Chema</a></span>
        <span class="hidden sm:inline">•</span>
        <a href="https://getalby.com/p/chemaclass" target="_blank" class="flex items-center gap-1 hover:underline">
            <i data-lucide="bitcoin" class="w-4 h-4 text-orange-500 dark:text-[--btc-orange-dark]"></i>
            {{ __('Leave a tip') }}
        </a>
        <span class="hidden sm:inline">•</span>
        <a href="https://github.com/Chemaclass/satscribe" target="_blank" class="flex items-center gap-1 hover:underline">
            <svg data-lucide="github" class="w-4 h-4"></svg>
            {{ __('GitHub') }}
        </a>
    </div>
</footer>
