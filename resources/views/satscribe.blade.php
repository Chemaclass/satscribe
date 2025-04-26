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
        </div>
    </header>

    <div x-data="searchInputValidator('{{ old('search', $search ?? '') }}')" x-init="validate()">
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
            <section
                class="description-body w-full max-w-3xl mx-auto space-y-6"
                x-init="$nextTick(() => window.refreshLucideIcons?.())"
            >
                <div class="section rounded p-4 shadow-sm">
                    <h2 class="text-lg font-semibold mb-2 flex items-center gap-2">
                        <i data-lucide="bot" class="w-6 h-6"></i>AI Summary
                    </h2>
                    <div class="leading-relaxed flex items-center gap-2">
                        <i data-lucide="loader-2" class="w-5 h-5 animate-spin text-orange-400"></i>
                        Generating response from the AI...
                    </div>
                </div>

                <div class="section rounded p-4 shadow-sm">
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
        isSubmitting: false,

        async submitForm(form) {
            if (this.isSubmitting) return;

            document.getElementById('description-body-results')?.style.setProperty('display', 'none');
            this.isSubmitting = true;

            try {
                const formData = new FormData(form);

                const { data } = await axios.post(form.action, formData, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    validateStatus: function (status) {
                        return true; // Accept all HTTP status codes
                    }
                });

                if (data.status === 'rate_limited') {
                    console.log(data);
                    window.dispatchEvent(new CustomEvent('rate-limit-reached', {
                        detail: {
                            invoice: data.lnInvoice ?? {},
                            maxAttempts: data.maxAttempts
                        }
                    }));

                    document.dispatchEvent(new CustomEvent('invoice-created', {
                        detail: {
                            identifier: data.lnInvoice.identifier,
                        }
                    }));

                    return;
                }

                const resultsContainer = document.getElementById('results-container');
                resultsContainer.innerHTML = data.html || '';

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
