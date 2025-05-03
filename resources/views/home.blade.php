@extends('layouts.base')

@section('content')
<section class="satscribe-home px-4 sm:px-6 lg:px-8 py-6">
    {{-- Header --}}
    <header class="section-header mb-6">
        <div class="flex flex-col max-w-2xl">
            <h1 class="text-2xl sm:text-3xl font-bold leading-tight">
                Understand any Bitcoin Transaction or Block
            </h1>
            <p class="subtitle text-base sm:text-lg text-gray-700 leading-relaxed">
                <strong>Satscribe</strong> helps you make sense of the Bitcoin blockchain. Just enter a transaction
                ID or block height to get clear, AI-generated insights. Whether you're auditing, learning, or just
                exploring, Satscribe gives you the story behind the sats.
            </p>
            <p class="subtitle mt-1 text-sm text-gray-600 leading-relaxed">
                Want to browse real-time Bitcoin blocks and transactions?
                <a href="https://mempool.space" target="_blank" rel="noopener" class="text-orange-500 hover:underline dark:text-orange-400">
                    Visit Mempool.space →
                </a>
            </p>
        </div>
    </header>

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
            <div class="hidden sm:flex w-1/3 h-60 items-center justify-center select-none" aria-hidden="true">
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
                            Generating response from the AI...
                        </p>
                    </div>
                </div>

                <div class="section rounded p-4 shadow-sm">
                    <h2 class="text-2xl font-bold mb-2 flex items-center">
                        <i data-lucide="box" class="w-6 h-6"></i> Raw Blockchain Data
                    </h2>
                    <div class="leading-relaxed prose dark:prose-invert">
                        <p class="flex items-center gap-2">
                            <i data-lucide="loader-2" class="w-5 h-5 animate-spin text-orange-400"></i>
                            Fetching blockchain data...
                        </p>
                    </div>
                </div>
            </section>
        </template>
    </div>

    {{-- Results Container --}}
    <div id="results-container">
        @isset($result)
            @include('partials.description-result')
        @endisset
    </div>
</section>

<x-paywall-modal/>

@endsection

@push('scripts')
<script>
function searchInputValidator(initial = '') {
    return {
        input: initial,
        valid: false,
        isHex64: false,
        isBlockHeight: false,
        isBlockHash: false,
        isSubmitting: false,

        async submitForm(form) {
            if (this.isSubmitting) return;

            document.getElementById('description-body-results')?.style.setProperty('display', 'none');
            this.isSubmitting = true;

            try {
                const formData = new FormData(form);

                const {data} = await axios.post(form.action, formData, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    validateStatus: function (status) {
                        return true; // Accept all HTTP status codes
                    }
                });

                if (data.status === 'rate_limited') {
                    window.dispatchEvent(new CustomEvent('rate-limit-reached', {
                        detail: {
                            invoice: data.invoice ?? {},
                            maxAttempts: data.maxAttempts
                        }
                    }));

                    document.dispatchEvent(new CustomEvent('invoice-created', {
                        detail: {
                            identifier: data.invoice.identifier,
                        }
                    }));

                    return;
                }

                const resultsContainer = document.getElementById('results-container');
                resultsContainer.innerHTML = data.html || data.error || '';

                window.refreshLucideIcons?.();

                const url = new URL(window.location);
                url.searchParams.set('search', formData.get('search'));
                window.history.pushState({}, '', url);

            } catch (error) {
                console.error('submitForm error:', error.message);
            } finally {
                this.isSubmitting = false;
            }
        },

        get helperText() {
            if (!this.input.trim()) return 'Enter a valid TXID (64 hex chars), block height (number), or block hash.';
            if (!this.valid) return 'Invalid format. Must be a TXID, block height, or block hash.';
            if (this.isBlockHash) return 'Valid block hash found.';
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
            const height = parseInt(trimmed, 10);

            this.isHex64 = /^[a-fA-F0-9]{64}$/.test(trimmed);
            this.isBlockHeight = /^\d+$/.test(trimmed) && height <= {{ $maxBitcoinBlockHeight ?? 10_000_000 }};
            this.isBlockHash = this.isHex64 && trimmed.startsWith('00000000');
            this.valid = this.isHex64 || this.isBlockHeight || this.isBlockHash;
        }
    };
}
</script>
@endpush
