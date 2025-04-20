@extends('layouts.base')

@section('content')
    <section class="satscribe-section px-4 sm:px-6 lg:px-8 py-6">

        {{-- Header Section --}}
        <header class="section-header mb-6">
            <div class="flex flex-col max-w-2xl">
                <h1 class="text-2xl sm:text-3xl font-bold leading-tight">
                    Understand any Bitcoin Transaction or Block
                </h1>
                <p class="subtitle text-base sm:text-lg text-gray-700">
                    Paste a <strong>Bitcoin TXID</strong> or <strong>block height</strong>, and let Satscribe explain it.
                </p>
            </div>
        </header>

        {{-- Form + Animated Icons --}}
        <div class="flex flex-col sm:flex-row items-start gap-6">
            <form method="GET" action="{{ route('home') }}" class="describe-form w-full max-w-2xl" aria-labelledby="form-heading">
                <fieldset>
                    <legend id="form-heading" class="sr-only">Describe Bitcoin Data</legend>
                    <div class="form-section mb-6" x-data="txidValidator()">
                        <label for="search" class="block text-sm font-medium text-gray-900 mb-1">
                            Transaction ID or Block Height
                        </label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            x-model="input"
                            placeholder="e.g. 4b0d... or 840000"
                            class="form-input w-full"
                            autocomplete="off"
                            required
                            autofocus
                            @input="validate()"
                            aria-describedby="searchHelp"
                        >
                        <small
                            id="searchHelp"
                            class="text-sm mt-1 block"
                            :class="input.length === 0 ? 'text-gray-600' : (valid ? 'text-gray-600' : 'text-red-600')"
                        >
                            <template x-if="input.length === 0">
                                <span>
                                    Enter a valid TXID (64 hex chars) or block height (number).
                                </span>
                            </template>
                            <template x-if="input.length > 0 && !valid">
                                <span class="text-red-600">
                                    Enter a valid TXID (64 hex chars) or block height (number).
                                </span>
                            </template>
                            <template x-if="valid && isHex64">
                                <span>
                                    Valid <span class="text-green-600 font-medium">TXID (64 hex chars) found</span>.
                                </span>
                            </template>
                            <template x-if="valid && isBlockHeight && !isHex64">
                                <span>
                                    Valid <span class="text-green-600 font-medium">block height (number) found</span>.
                                </span>
                            </template>
                        </small>
                        @error('search')
                        <div class="error mt-1 text-red-500 text-sm" role="alert">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- AI Question Group --}}
                    <div class="form-section mb-6">
                        <label for="question" class="block text-sm font-medium text-gray-900 mb-1">
                            Ask a Question (optional)
                        </label>
                        <input
                            type="text"
                            id="question"
                            name="question"
                            value="{{ old('question', $question ?? '') }}"
                            placeholder="{{ $questionPlaceholder ?? 'What is the total input value?' }}"
                            class="form-input w-full"
                            aria-describedby="questionHelp"
                            autocomplete="off"
                            maxlength="200"
                        >
                        <small id="questionHelp" class="text-gray-600 text-sm mt-1 block">
                            Ask the AI a specific question about this transaction or block.
                        </small>
                        @error('question')
                        <div class="error mt-1 text-red-500 text-sm" role="alert">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Submit Button + Info --}}
                    <div class="form-actions mt-4 flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                        <button type="submit" class="form-button w-full sm:w-auto" id="submit-button">
                            <i class="fas fa-spinner fa-spin" id="submit-spinner" style="display: none; margin-left: 0.5rem;"></i>
                            <span id="submit-text">Satscribe</span>
                            <span id="submit-icon"><i class="fas fa-bolt"></i></span>
                        </button>

                        {{-- Status badge, right next to button --}}
                        @isset($isFresh)
                            @if ($isFresh)
                                <div class="inline-flex items-center text-sm text-green-800 bg-green-100 border border-green-200 px-3 py-2 rounded-md shadow-sm">
                                    <i class="fa-solid fa-vial text-green-500 mr-1"></i>
                                    <strong class="mr-1">Fresh!</strong>
                                    <span>Generated using live blockchain data and AI ✨</span>
                                </div>
                            @else
                                <div class="inline-flex items-center text-sm text-yellow-800 bg-yellow-100 border border-yellow-200 px-3 py-2 rounded-md shadow-sm">
                                    <i class="fa-solid fa-clock-rotate-left text-yellow-600 mr-1"></i>
                                    <span>Loaded from previous analysis (cached)</span>
                                </div>
                            @endif
                        @endisset
                    </div>

                    {{-- Advanced Fields Toggle --}}
                    <div x-data="{ showAdvanced: false }" class="form-group mb-4">
                        <button
                            type="button"
                            class="text-sm font-medium text-orange-600 hover:text-orange-700 flex items-center gap-2 mt-4"
                            @click="showAdvanced = !showAdvanced"
                        >
                            <i class="fas fa-sliders-h text-orange-500"></i>
                            <span x-show="!showAdvanced">Show advanced fields ▾</span>
                            <span x-show="showAdvanced">Hide advanced fields ▴</span>
                        </button>

                        <div
                            x-show="showAdvanced"
                            x-cloak
                            x-transition
                            class="mt-1 bg-orange-50 border border-orange-200 rounded-lg px-4 py-3 space-y-2 shadow-sm"
                        >
                            {{-- Refresh Checkbox --}}
                            <div class="form-checkbox enhanced-checkbox">
                                <input
                                    type="checkbox"
                                    id="refresh"
                                    name="refresh"
                                    value="true"
                                    class="checkbox-input"
                                >
                                <label for="refresh" class="checkbox-label">
                                    Fetch the latest data from the blockchain<br>
                                    <small class="checkbox-help text-gray-600">(Skips cached descriptions and requests live data from the blockchain and OpenAI)</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </form>

            {{-- Animated Bitcoin Icon --}}
            <div class="hidden sm:flex w-1/3 h-48 items-center justify-center select-none" aria-hidden="true">
                <i class="fa-brands fa-bitcoin text-[120px] opacity-100 animate-bounce-wave" style="color: var(--btc-orange);"></i>
            </div>
        </div>

        {{-- AI + Blockchain Result --}}
        @isset($result)
            <section class="description-body mt-6 w-full max-w-3xl mx-auto space-y-6">
                @if($result->force_refresh)
                    <div class="alert-warning" role="alert">
                        ⚠️ This transaction is unconfirmed. You might want to refresh later to get the latest status.
                    </div>
                @endif

                <div class="section bg-orange-50 border border-orange-100 rounded p-4 shadow-sm">
                    <h2 class="text-lg font-semibold mb-2">🧠 AI Summary</h2>
                    <div class="box markdown-content text-gray-800 leading-relaxed">
                        {!! Str::markdown($result->ai_response) !!}
                    </div>
                </div>

                <div class="section">
                    <h2>📦 Raw Blockchain Data</h2>

                    <div class="code-block-collapsible">
                    <pre id="blockchain-data" class="code-block collapsed overflow-x-auto text-sm sm:text-base">
{{ json_encode($result->raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}
                    </pre>
                        <button type="button" id="toggle-raw" class="toggle-raw-button">Show more</button>
                    </div>
                </div>
            </section>
        @endisset

    </section>
@endsection

@push('scripts')
    <script>
        function txidValidator() {
            return {
                input: '',
                valid: false,
                isHex64: false,
                isBlockHeight: false,

                validate() {
                    const trimmed = this.input.trim();

                    // Validate 64-character hex
                    this.isHex64 = /^[a-fA-F0-9]{64}$/.test(trimmed);

                    // Validate numeric and check it's <= maxBlockHeight
                    const height = parseInt(trimmed, 10);
                    this.isBlockHeight = /^\d+$/.test(trimmed) && height <= {{$maxBitcoinBlockHeight??1_000_000}};

                    this.valid = this.isHex64 || this.isBlockHeight;
                }
            };
        }
    </script>
@endpush
