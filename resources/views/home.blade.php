@extends('layouts.base')

@section('content')
<section class="satscribe-home px-4 sm:px-6 lg:px-8 py-6">
    <x-home.header/>
    <x-home.form
        :search="old('search', $search ?? '')"
        :maxBitcoinBlockHeight="$maxBitcoinBlockHeight"
        :suggestedPromptsGrouped="$suggestedPromptsGrouped"
    />
    <div id="results-container"></div>
</section>
<x-paywall-modal/>
@endsection

@push('scripts')
<script>
function searchInputValidator(initial = '', initialMaxHeight = 10_000_000) {
    return {
        input: initial,
        valid: false,
        isHex64: false,
        isBlockHeight: false,
        isBlockHash: false,
        isSubmitting: false,
        maxBitcoinBlockHeight: initialMaxHeight,
        loadingMessage: '',
        loadingMessages: [
            "Just a sec — I'm working on your request and putting everything together for you!",
            "Hang on a moment while I sort this out for you — almost there!",
            "Give me a moment, I'm digging into your request and cooking up a response.",
            "One moment while I pull everything together — this will be worth the wait!",
            "Working on it! Just making sure I get you the best answer I can.",
            "Hold tight — I'm piecing things together and getting your reply ready.",
            "Crafting your answer — I'll be done in a flash.",
            "Almost done — just double-checking everything for you!",
            "Hang tight — I'm wrapping this up right now.",
            "Working my magic — your response is coming up shortly!",
            "Just a moment — pulling in all the right info for you.",
            "On it! I'm making sure every detail is spot-on.",
        ],

        async submitForm(form) {
            if (this.isSubmitting) return;

            document.getElementById('description-body-results')?.style.setProperty('display', 'none');
            this.loadingMessage = this.loadingMessages[Math.floor(Math.random() * this.loadingMessages.length)];
            this.isSubmitting = true;

            try {
                const formData = new FormData(form);

                const {data} = await axios.post(form.action, formData, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    validateStatus: function (status) {
                        return true;
                    }
                });

                if (data.maxBitcoinBlockHeight) {
                    this.maxBitcoinBlockHeight = data.maxBitcoinBlockHeight;
                }

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

                const searchInput = document.getElementById('search-input');
                if (searchInput && data.search?.text) {
                    searchInput.value = data.search.text;
                    this.input = data.search.text;
                    this.validate();
                }
            } catch (error) {
                console.error('submitForm error:', error.message);
            } finally {
                this.isSubmitting = false;
            }
        },

        get helperText() {
            if (!this.input.trim()) return 'Enter a valid TXID, block height, or block hash.';
            if (!this.valid) return 'Invalid format. Must be a TXID, block height, or block hash.';
            if (this.isBlockHash) return 'Valid block hash found.';
            if (this.isHex64) return 'Valid TXID found.';
            if (this.isBlockHeight) return 'Valid block height found.';
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
            this.isBlockHeight = /^\d+$/.test(trimmed) && height <= this.maxBitcoinBlockHeight;
            this.isBlockHash = this.isHex64 && trimmed.startsWith('00000000');
            this.valid = this.isHex64 || this.isBlockHeight || this.isBlockHash;
        },

        async fetchRandomBlock() {
            const maxHeight = this.maxBitcoinBlockHeight;
            const randomHeight = Math.floor(Math.random() * maxHeight);
            this.input = randomHeight.toString();

            // Sync DOM input
            const searchInput = document.getElementById('search-input');
            if (searchInput) searchInput.value = this.input;

            // Pick a random question
            const groups = window.suggestedPromptsGrouped || {};
            let possibleQuestions = [
                ...(groups['both'] || []),
                ...(groups['block'] || []),
            ];
            const questionInput = document.getElementById('question');
            if (questionInput && possibleQuestions.length > 0) {
                questionInput.value = possibleQuestions[Math.floor(Math.random() * possibleQuestions.length)];
            }

            // Random persona
            const personaSelect = document.getElementById('persona');
            if (personaSelect && personaSelect.options.length > 0) {
                personaSelect.selectedIndex = Math.floor(Math.random() * personaSelect.options.length);
            }

            this.validate();

            const form = document.querySelector('form');
            if (form) {
                await this.submitForm(form);
            }
        },
    };
}

function resubmit(searchValue, questionValue = '') {
    const form = document.getElementById('satscribe-form');
    if (!form) return;

    const searchInput = document.getElementById('search-input');
    const questionInput = document.querySelector('input[name="question"]');

    if (searchInput) searchInput.value = searchValue;
    if (questionInput) questionInput.value = questionValue;

    // Update Alpine state manually
    if (window.Alpine) {
        const component = Alpine.closestDataStack(form);
        if (component) {
            component.input = searchValue;
            component.validate?.();
        }
    }

    // Submit form programmatically
    form.dispatchEvent(new Event('submit', {bubbles: true}));
}

function resubmitWithRefresh(searchValue, questionValue = '') {
    const refreshCheckbox = document.getElementById('refresh');
    if (refreshCheckbox) refreshCheckbox.checked = true;
    resubmit(searchValue, questionValue);
}
</script>
@endpush
