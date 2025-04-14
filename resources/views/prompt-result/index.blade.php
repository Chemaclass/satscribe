@extends('layouts.app')

@section('title', 'Satscribe – AI Bitcoin Describer')

@section('content')
    <section>
        <h1>🧠 Understand Any Bitcoin Transaction or Block</h1>
        <p class="subtitle">
            Paste a <strong>Bitcoin TXID</strong> or <strong>block height</strong>, and let Satscribe explain it with AI.
        </p>

        <form method="GET" action="{{ route('describe') }}" class="describe-form">
            <div class="form-group">
                <label for="q" class="form-label">Transaction ID or Block Height</label>
                <input
                    type="text"
                    id="q"
                    name="q"
                    value="{{ old('q', $q ?? '') }}"
                    placeholder="e.g. 4b0d... or 840000"
                    class="form-input"
                    autofocus
                    autocomplete="off"
                    required
                >
                @error('q')
                <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-checkbox">
                <label for="refresh">
                    <input
                        type="checkbox"
                        id="refresh"
                        name="refresh"
                        value="true"
                        {{ request('refresh') ? 'checked' : '' }}
                    >
                    🔄 Force fresh result from blockchain + OpenAI
                </label>
            </div>

            <button type="submit" class="form-button">🚀 Describe It</button>
        </form>
    </section>

    @if(isset($isFresh))
        <div class="info-message {{ $isFresh ? 'info-fresh' : 'info-cached' }}">
            {{ $isFresh ? '✨ Freshly generated using live blockchain data and AI.' : '💾 Loaded from previous analysis stored in the database.' }}
        </div>
    @endif

    @isset($result)
        @if($result->force_refresh)
            <div class="alert-warning">
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
            <pre class="code-block">{{ json_encode($result->raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    @endisset
@endsection
