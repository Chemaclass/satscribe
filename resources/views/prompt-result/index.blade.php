@extends('layouts.base')

@section('title', 'Satscribe – AI Bitcoin Describer')

@section('content')
    <section class="satscribe-section px-4 sm:px-6 lg:px-8 py-6">
        <header class="section-header">
            <h1>Understand any Bitcoin Transaction or Block</h1>
            <p class="subtitle">
                Paste a <strong>Bitcoin TXID</strong> or <strong>block height</strong>, and let Satscribe explain it
                with AI.
            </p>
        </header>

        <div class="flex flex-col lg:flex-row items-start">
            <form method="GET" action="{{ route('generate') }}" class="describe-form w-full max-w-2xl" aria-labelledby="form-heading">
            <fieldset>
                <legend id="form-heading" class="visually-hidden">Describe Bitcoin Data</legend>

                <div class="form-group">
                    <label for="search" class="form-label">Transaction ID or Block Height</label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="{{ old('search', $search ?? '') }}"
                        placeholder="e.g. 4b0d... or 840000"
                        class="form-input"
                        aria-describedby="searchHelp"
                        autocomplete="off"
                        required
                        autofocus
                    >
                    <small id="searchHelp" class="form-help">
                        Enter a valid TXID (64 hex chars) or block height (number).
                    </small>

                    @error('search')
                    <div class="error" role="alert">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <input
                        type="text"
                        id="question"
                        name="question"
                        value="{{ old('question', $question ?? '') }}"
                        placeholder="{{$questionPlaceholder ?? "What is the total input value?"}}"
                        class="form-input"
                        aria-describedby="questionHelp"
                        autocomplete="off"
                        maxlength="200"
                    >
                    <small id="questionHelp" class="form-help">
                        Ask the AI a specific question about this transaction or block.
                    </small>

                    @error('question')
                    <div class="error" role="alert">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-checkbox enhanced-checkbox">
                    <input
                        type="checkbox"
                        id="refresh"
                        name="refresh"
                        value="true"
                        class="checkbox-input"
                        {{ old('refresh', $refreshed ?? false) ? 'checked' : '' }}
                    >
                    <label for="refresh" class="checkbox-label">
                        Fetch the latest data from the blockchain<br>
                        <small class="checkbox-help">(Skips cached descriptions and requests live data from the blockchain and OpenAI)</small>
                    </label>
                </div>

                <div class="form-actions mt-4 flex flex-col sm:flex-row sm:items-center gap-2">
                    <button type="submit" class="form-button w-full sm:w-auto" id="submit-button">
                        <i class="fas fa-spinner fa-spin" id="submit-spinner"
                           style="display: none; margin-left: 0.5rem;"></i>
                        <span id="submit-icon"><i class="fas fa-vial"></i></span>
                        <span id="submit-text">Generate Description</span>
                    </button>
                @if(isset($isFresh))
                    <span
                        id="submit-btn-info-status"
                        class="info-message {{ $isFresh ? 'info-fresh' : 'info-cached' }}"
                        role="status"
                        aria-live="polite"
                    >
                        {{ $isFresh ? ' ✨ Freshly generated using live data and AI.' : 'Loaded from previous analysis.' }}
                    </span>
                @endif
                </div>
            </fieldset>
        </form>
            <div class="hidden lg:block w-1/3 h-32 relative select-none" aria-hidden="true">
                <div class="absolute bottom-0 right-0 flex justify-end items-end gap-1 pr-4">
                    <span class="text-3xl animate-bounce-wave delay-[0ms]"><i class="fa-solid fa-bitcoin-sign text-orange-500"></i></span>
                    <span class="text-3xl animate-bounce-wave delay-[150ms]"><i class="fa-solid fa-bitcoin-sign text-orange-500"></i></span>
                    <span class="text-3xl animate-bounce-wave delay-[300ms]"><i class="fa-solid fa-bitcoin-sign text-orange-500"></i></span>
                    <span class="text-3xl animate-bounce-wave delay-[450ms]"><i class="fa-solid fa-bitcoin-sign text-orange-500"></i></span>
                    <span class="text-3xl animate-bounce-wave delay-[600ms]"><i class="fa-solid fa-bitcoin-sign text-orange-500"></i></span>
                </div>
            </div>
        </div>

        @isset($result)
            <section class="description-body mt-6 w-full max-w-3xl">
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
