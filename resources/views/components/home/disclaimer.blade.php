<div
    x-data="{ show: localStorage.getItem('hideInscriptionNotice') !== '1' }"
    x-show="show"
    x-cloak
    class="mb-4 p-3 rounded-md bg-yellow-100 border border-yellow-200 text-yellow-800 text-sm flex justify-between items-start"
>
    <p>{!! __('home.disclaimer') !!}</p>
    <button
        type="button"
        class="ml-4 underline cursor-pointer"
        @click="localStorage.setItem('hideInscriptionNotice', '1'); show = false"
    >
        <i data-lucide="x" class="w-4 h-4"></i>
    </button>
</div>
