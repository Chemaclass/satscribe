<div
    x-data="{ show: localStorage.getItem('hideInscriptionNotice') !== '1' }"
    x-show="show"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-4"
    class="fixed bottom-4 right-4 max-w-sm p-4 rounded-lg bg-white border border-gray-200 shadow-lg text-gray-700 text-sm z-50"
>
    <div class="flex items-start gap-3">
        <i data-lucide="info" class="w-5 h-5 text-orange-500 flex-shrink-0 mt-0.5"></i>
        <div class="flex-1">
            <p class="leading-relaxed">{!! __('home.disclaimer') !!}</p>
            <button
                type="button"
                class="mt-3 px-3 py-1.5 text-xs font-medium bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
                @click="localStorage.setItem('hideInscriptionNotice', '1'); show = false"
            >
                {{ __('Got it') }}
            </button>
        </div>
        <button
            type="button"
            class="text-gray-400 hover:text-gray-600 transition-colors"
            @click="localStorage.setItem('hideInscriptionNotice', '1'); show = false"
        >
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
</div>
