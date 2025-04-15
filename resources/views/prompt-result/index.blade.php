@extends('layouts.base')

@section('title', 'Satscribe – AI Bitcoin Describer')

@section('content')
    <section class="satscribe-section">
        <header class="section-header">
            <h1>Understand any Bitcoin Transaction or Block</h1>
            <p class="subtitle">
                Paste a <strong>Bitcoin TXID</strong> or <strong>block height</strong>, and let Satscribe explain it
                with AI.
            </p>
        </header>

        <form method="GET" action="{{ route('generate') }}" class="describe-form" aria-labelledby="form-heading">
            <fieldset>
                <legend id="form-heading" class="visually-hidden">Describe Bitcoin Data</legend>

                <div class="form-group">
                    <label for="q" class="form-label">Transaction ID or Block Height</label>
                    <input
                        type="text"
                        id="q"
                        name="q"
                        value="{{ old('q', $q ?? '') }}"
                        placeholder="e.g. 4b0d... or 840000"
                        class="form-input"
                        aria-describedby="qHelp"
                        autocomplete="off"
                        required
                        autofocus
                    >
                    <small id="qHelp" class="form-help">Enter a valid TXID (64 hex chars) or block height
                        (number).</small>

                    @error('q')
                    <div class="error" role="alert">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="question" class="form-label">Custom Question (optional)</label>
                    <input
                        type="text"
                        id="question"
                        name="question"
                        value="{{ old('question', $question ?? '') }}"
                        placeholder="e.g. What is the total input value?"
                        class="form-input"
                        aria-describedby="questionHelp"
                        autocomplete="off"
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

                <div class="form-actions">
                    <button type="submit" class="form-button" id="submit-button">
                        <i class="fas fa-spinner fa-spin" id="submit-spinner"
                           style="display: none; margin-left: 0.5rem;"></i>
                        <span id="submit-icon"><i class="fas fa-vial"></i></span>
                        <span id="submit-text">Generate Description</span>
                    </button>
                </div>
            </fieldset>
        </form>

        @if(isset($isFresh))
            <div
                class="info-message {{ $isFresh ? 'info-fresh' : 'info-cached' }}"
                role="status"
                aria-live="polite"
            >
                {{ $isFresh ? '✨ Freshly generated using live blockchain data and AI.' : '💾 Loaded from previous analysis stored in the database.' }}
            </div>
        @endif

        @isset($result)
            <section class="description-body">
                @if($result->force_refresh)
                    <div class="alert-warning" role="alert">
                        ⚠️ This transaction is unconfirmed. You might want to refresh later to get the latest status.
                    </div>
                @endif

                <div class="section">
                    <h2>🧠 AI Summary</h2>
                    <div class="box markdown-content">
                        {!! Str::markdown($result->ai_response) !!}
                    </div>
                </div>

                <div class="section">
                    <h2>📦 Raw Blockchain Data</h2>

                    <div class="code-block-collapsible">
                        <pre id="blockchain-data" class="code-block collapsed">
{{ json_encode($result->raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}
                        </pre>
                        <button type="button" id="toggle-raw" class="toggle-raw-button">Show more</button>
                    </div>
                </div>
            </section>
        @endisset
    </section>
@endsection
