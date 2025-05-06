<div x-data="searchInputValidator('{{ old('search', $search ?? '') }}')"
     x-init="
            validate();
            $watch('isSubmitting', value => {
                if (value) {
                    window.refreshLucideIcons?.();
                }
            });
        "
>
    {{-- Form + Icon Side-by-Side --}}
    <div class="flex flex-col sm:flex-row sm:justify-between items-start sm:items-start gap-6 max-w-5xl">
        {{-- Left: Form --}}
        <div class="w-full sm:w-2/3">
            @include('partials.form-fields')
        </div>
        {{-- Right: Bitcoin Icon --}}
        <div class="hidden sm:flex w-1/3 h-45 items-center justify-center select-none" aria-hidden="true">
            <i data-lucide="bitcoin" class="w-[150px] h-[150px] animate-bounce-wave text-orange-500"
               style="color: var(--btc-orange);"></i>
        </div>
    </div>

    {{-- Loading State --}}
    <template x-if="isSubmitting">
        <section id="description-body-results" class="description-body w-full max-w-3xl mx-auto space-y-6">
            <div class="section rounded p-4 shadow-sm">
                <h2 class="text-2xl font-bold mb-2 flex items-center">
                    <i data-lucide="bot" class="w-6 h-6"></i> AI Summary
                </h2>
                <div class="leading-relaxed prose dark:prose-invert">
                    <p class="flex items-center gap-2">
                        <i data-lucide="loader-2" class="w-5 h-5 animate-spin text-orange-400"></i>
                        <span x-text="loadingMessage"></span>
                    </p>
                </div>
            </div>
        </section>
    </template>
</div>
