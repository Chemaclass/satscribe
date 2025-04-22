@extends('layouts.base')

@section('content')
    <section class="satscribe-section px-4 sm:px-6 lg:px-8 py-6">
        {{-- Header --}}
        <header class="section-header mb-6">
            <div class="flex flex-col max-w-2xl">
                <h1 class="text-2xl sm:text-3xl font-bold leading-tight">
                    Understand any Bitcoin Transaction or Block
                </h1>
                <p class="subtitle text-base sm:text-lg text-gray-700 leading-relaxed">
                    <strong>Satscribe</strong> helps you make sense of the Bitcoin blockchain. Just enter a transaction ID or block height to get clear, AI-generated insights. Whether you're auditing, learning, or just exploring, Satscribe gives you the story behind the sats.
                </p>
            </div>
        </header>

        <div x-data="searchInputValidator('{{ old('search', $search ?? '') }}')" x-init="validate()">
            {{-- Form + Icon Side-by-Side --}}
            <div class="flex flex-col sm:flex-row sm:justify-between items-start sm:items-start gap-6 max-w-5xl">
                {{-- Left: Form --}}
                <div class="w-full sm:w-2/3">
                    <form method="GET"
                          action="{{ route('home') }}"
                          aria-labelledby="form-heading"
                          @submit="isSubmitting = true"
                          @submit.prevent="
        document.getElementById('description-body-results')?.style.setProperty('display', 'none');
        isSubmitting = true;
        $nextTick(() => $el.submit());"
                    >
                        <fieldset>
                            <legend id="form-heading" class="sr-only">Describe Bitcoin Data</legend>
                            @include('partials.form-fields')
                        </fieldset>
                    </form>
                </div>

                {{-- Right: Bitcoin Icon --}}
                <div class="hidden sm:flex w-1/3 h-60 items-center justify-center select-none" aria-hidden="true">
                    <i data-lucide="bitcoin" class="w-[150px] h-[150px] animate-bounce-wave text-orange-500" style="color: var(--btc-orange);"></i>
                </div>
            </div>

            {{-- Loading State (client-only before reload) --}}
            <template x-if="isSubmitting">
                <section
                    class="description-body mt-6 w-full max-w-3xl mx-auto space-y-6"
                    x-init="$nextTick(() => window.refreshLucideIcons?.())"
                >
                    <div class="section rounded p-4 shadow-sm">
                        <h2 class="text-lg font-semibold mb-2 flex items-center gap-2">
                            <i data-lucide="bot" class="w-6 h-6"></i>AI Summary
                        </h2>
                        <div class="box text-gray-800 leading-relaxed flex items-center gap-2">
                            <i data-lucide="loader-2" class="w-5 h-5 animate-spin text-orange-400"></i>
                            Generating response from the AI...
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="text-lg font-semibold mb-2 flex items-center gap-2">
                            <i data-lucide="box" class="w-6 h-6"></i> Raw Blockchain Data
                        </h2>
                        <div class="code-block-collapsible flex items-center gap-2">
                            <i data-lucide="loader-2" class="w-5 h-5 animate-spin text-orange-400"></i>
                            Fetching blockchain data...
                        </div>
                    </div>
                </section>
            </template>
        </div>

        {{-- Real Results after reload --}}
        @isset($result)
            <section id="description-body-results" class="description-body mt-6 w-full max-w-3xl mx-auto space-y-6 ">
                @if($result->force_refresh)
                    <div class="alert-warning" role="alert">
                        ⚠️ This transaction is unconfirmed. You might want to refresh later to get the latest status.
                    </div>
                @endif

                <div class="section rounded p-4 shadow-sm">
                    <h2 class="text-lg font-semibold mb-2 flex items-center gap-2">
                        <i data-lucide="bot" class="w-6 h-6"></i>AI Summary
                    </h2>
                    <div class="box text-gray-800 leading-relaxed">
                        {!! Str::markdown($result->ai_response) !!}
                    </div>
                </div>

                <div class="section">
                    <h2 class="text-lg font-semibold mb-2 flex items-center gap-2">
                        <i data-lucide="box" class="w-6 h-6"></i> Raw Blockchain Data
                    </h2>
                    <div class="code-block-collapsible">
                        <pre id="blockchain-data" class="code-block collapsed overflow-x-auto text-sm sm:text-base">
{{ json_encode($result->raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}
</pre>
                    </div>
                </div>
            </section>
        @endisset
    </section>
@endsection

@push('scripts')
<script>
function searchInputValidator(initial = '') {
    return {
        input: initial,
        valid: false,
        isHex64: false,
        isBlockHeight: false,
        isSubmitting: false,

        get helperText() {
            if (!this.input.trim()) return 'Enter a valid TXID (64 hex chars) or block height (number).';
            if (!this.valid) return 'Invalid format. Must be a TXID or block height.';
            if (this.isHex64) return 'Valid TXID (64 hex chars) found.';
            if (this.isBlockHeight) return 'Valid block height (number) found.';
            return '';
        },

        get helperClass() {
            if (!this.input.trim()) return 'text-gray-600';
            return this.valid ? 'text-green-600 font-medium' : 'text-red-600';
        },

        validate() {
            const trimmed = this.input.trim();
            this.isHex64 = /^[a-fA-F0-9]{64}$/.test(trimmed);
            const height = parseInt(trimmed, 10);
            this.isBlockHeight = /^\d+$/.test(trimmed) && height <= {{ $maxBitcoinBlockHeight ?? 100_000_000 }};
            this.valid = this.isHex64 || this.isBlockHeight;
        }
    };
}
</script>
@endpush
