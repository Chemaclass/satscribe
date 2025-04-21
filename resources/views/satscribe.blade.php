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

        {{-- Form + Icon Side-by-Side --}}
        <div class="flex flex-col sm:flex-row sm:justify-between items-start sm:items-start gap-6 max-w-5xl">
            {{-- Left: Form --}}
            <div class="w-full sm:w-2/3">
                <form
                    method="GET"
                    action="{{ route('home') }}"
                    aria-labelledby="form-heading"
                    x-data="searchInputValidator('{{ old('search', $search ?? '') }}')"
                    x-init="validate()"
                    @submit="isSubmitting = true"
                >
                    <fieldset>
                        <legend id="form-heading" class="sr-only">Describe Bitcoin Data</legend>

                        <div class="form-section mb-6" x-data="searchInputValidator('{{ old('search', $search ?? '') }}')" x-init="validate()">
                            <label for="search" class="block text-sm font-medium text-gray-900 mb-1">
                                Transaction ID or Block Height
                            </label>

                            <input
                                type="text"
                                id="search"
                                name="search"
                                x-model="input"
                                placeholder="e.g. 4b0d... or 840000 — default: latest block"
                                class="form-input w-full"
                                autocomplete="off"
                                :required="false"
                                autofocus
                                @input="validate()"
                                aria-describedby="searchHelp"
                            >

                            <small id="searchHelp" class="text-sm mt-1 block">
                            <span
                                x-text="helperText"
                                :class="helperClass"
                                x-cloak
                                class="transition-colors duration-200 ease-in-out"
                            ></span>
                            </small>

                            @error('search')
                            <div class="error mt-1 text-red-500 text-sm" role="alert">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Question --}}
                        <div class="form-section mb-6">
                            <label for="question" class="block text-sm font-medium text-gray-900 mb-1">
                                Ask a Question
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

                        {{-- Submit --}}
                        <input type="hidden" name="submitted" value="1">
                        <div class="form-actions mt-4 flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                            <button
                                type="submit"
                                class="form-button w-full sm:w-auto"
                                id="submit-button"
                                :disabled="isSubmitting"
                            >
                                <i data-lucide="loader-2" class="animate-spin mr-2" x-show="isSubmitting" x-cloak></i>
                                <span x-show="!isSubmitting" x-cloak id="submit-text">Satscribe</span>
                                <span x-show="!isSubmitting" x-cloak id="submit-icon" class="sm-2">
        <i data-lucide="zap" class="w-4 h-4"></i>
    </span>
                            </button>

                            {{-- Status --}}
                            @isset($isFresh)
                                @if ($isFresh)
                                    <div class="inline-flex items-center text-sm text-green-800 bg-green-100 border border-green-200 px-3 py-2 rounded-md shadow-sm">
                                        <i data-lucide="flask" class="text-green-500 mr-1"></i>
                                        <strong class="mr-1">Fresh!</strong>
                                        <span>Generation using live blockchain data ✨</span>
                                    </div>
                                @else
                                    <div class="inline-flex items-center text-sm text-yellow-800 bg-yellow-100 border border-yellow-200 px-3 py-2 rounded-md shadow-sm">
                                        <i data-lucide="history" class="text-yellow-600 mr-1"></i>
                                        <span>Loaded from previous analysis (cached)</span>
                                    </div>
                                @endif
                            @endisset
                        </div>

                        {{-- Advanced Toggle --}}
                        <div x-data="{ showAdvanced: false }" class="form-group mb-4">
                            <button
                                type="button"
                                class="text-sm font-medium text-orange-600 hover:text-orange-700 flex items-center gap-2 mt-4"
                                @click="showAdvanced = !showAdvanced"
                            >
                                <i data-lucide="sliders-horizontal" class="text-orange-500"></i>
                                <span x-show="!showAdvanced">Show advanced fields ▾</span>
                                <span x-show="showAdvanced">Hide advanced fields ▴</span>
                            </button>

                            <div
                                x-show="showAdvanced"
                                x-cloak
                                x-transition
                                class="mt-4 advanced-fields mt-1 rounded-lg px-4 py-3 space-y-4 shadow-sm"
                            >
                                {{-- Persona Selector --}}
                                <div>
                                    <label for="persona" class="persona-label block text-sm font-medium mb-1">
                                        AI Persona
                                    </label>

                                    <div class="relative">
                                        <select
                                            name="persona" id="persona"
                                            class="form-select persona-select w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-sm text-gray-800 dark:text-gray-100 rounded-md shadow-sm focus:ring-orange-400 focus:border-orange-400"
                                        >
                                            @foreach (\App\Enums\PromptPersona::cases() as $persona)
                                                <option value="{{ $persona->value }}"
                                                    {{ old('persona', $selected ?? 'educator') === $persona->value ? 'selected' : '' }}>
                                                    {{ $persona->label() }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <small class="checkbox-help text-gray-600 dark:text-gray-400 mt-1 block">
                                        Choose how you'd like the AI to explain things.
                                    </small>
                                </div>

                                {{-- Refresh Checkbox --}}
                                <div class="flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        id="refresh"
                                        name="refresh"
                                        value="true"
                                        class="checkbox-input mt-1 accent-orange-500 dark:accent-orange-400"
                                    >
                                    <label for="refresh" class="checkbox-label text-sm text-gray-800 dark:text-orange-200">
                                        <strong class="text-orange-700 dark:text-orange-400">Fetch the latest data from the blockchain</strong><br>
                                        <small class="checkbox-help text-gray-600 dark:text-gray-400">
                                            (Skips cached descriptions and requests live data from the blockchain and OpenAI)
                                        </small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>

            {{-- Right: Bitcoin Icon --}}
            <div class="hidden sm:flex w-1/3 h-60 items-center justify-center select-none" aria-hidden="true">
                <i
                    data-lucide="bitcoin"
                    class="w-[150px] h-[150px] animate-bounce-wave text-orange-500"
                    style="color: var(--btc-orange);"
                ></i>
            </div>
        </div>

        {{-- Loading State --}}
        <div x-show="isSubmitting" class="description-body mt-6 w-full max-w-3xl mx-auto space-y-6">
            <div class="section bg-orange-50 border border-orange-100 rounded p-4 shadow-sm">
                <h2 class="text-lg font-semibold mb-2">🧠 AI Summary</h2>
                <div class="text-gray-600 italic flex items-center gap-2">
                    <i data-lucide="loader-2" class="animate-spin text-orange-400"></i>
                    Generating AI summary...
                </div>
            </div>

            <div class="section">
                <h2>📦 Raw Blockchain Data</h2>
                <div class="text-gray-600 italic flex items-center gap-2">
                    <i data-lucide="loader-2" class="animate-spin text-orange-400"></i>
                    Fetching raw data from the blockchain...
                </div>
            </div>
        </div>

        {{-- Actual Result --}}
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
                        <button type="button" id="toggle-raw" class="toggle-history-raw-btn">Show more</button>
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
