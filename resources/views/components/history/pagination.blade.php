<div class="pagination flex justify-center items-center gap-4 mt-8">
    @if ($paginator->onFirstPage())
        <span
            class="px-6 py-3 text-white/60 font-semibold rounded-md cursor-not-allowed flex items-center gap-2"
        >
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            Previous
        </span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}"
           class="px-6 py-3  text-white font-semibold rounded-md  transition flex items-center gap-2"
        >
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            Previous
        </a>
    @endif

    <span class="text-base font-semibold text-gray-400">Page {{ $paginator->currentPage() }}</span>

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}"
           class="px-6 py-3  text-white font-semibold rounded-md transition flex items-center gap-2"
        >
            Next
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
        </a>
    @else
        <span
            class="px-6 py-3  text-white/60 font-semibold rounded-md cursor-not-allowed flex items-center gap-2"
        >
            Next
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
        </span>
    @endif
</div>
