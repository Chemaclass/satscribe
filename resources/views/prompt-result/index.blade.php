@extends('layouts.app')

@section('title', 'Satscribe – AI Bitcoin Describer')

@section('content')
    <h1>🧠 Understand Any Bitcoin Transaction or Block</h1>
    <p style="margin-bottom: 2rem; color: #4b5563; font-size: 0.95rem;">
        Paste a <strong>Bitcoin TXID</strong> or <strong>block height</strong>, and let Satscribe explain it with AI.
    </p>

    <form method="GET" action="{{ route('describe') }}">
        <div class="form-group">
            <label for="input" style="font-weight: 500;">Transaction ID or Block Height</label>
            <input
                type="text"
                id="input"
                name="input"
                value="{{ old('input', $input ?? '') }}"
                placeholder="e.g. 4b0d... or 840000"
                class="form-input"
                autofocus
                autocomplete="off"
                required
            >

            <label class="form-checkbox" for="refresh">
                <input
                    type="checkbox"
                    id="refresh"
                    name="refresh"
                    value="true"
                    {{ request('refresh') ? 'checked' : '' }}
                >
                <span>🔄 Force fresh result from blockchain + OpenAI</span>
            </label>

            <button type="submit" class="form-button">
                🚀 Describe It
            </button>

            @error('input')
            <div class="error">{{ $message }}</div>
            @enderror
        </div>
    </form>

    @if(isset($isFresh))
        <div class="info-message {{ $isFresh ? 'info-fresh' : 'info-cached' }}">
            {{ $isFresh ? '✨ Freshly generated using live blockchain data and OpenAI.' : '💾 Loaded from previous analysis stored in the database.' }}
        </div>
    @endif

    @isset($result)
        <div class="section">
            <h2>🧠 AI Summary</h2>
            <div class="box">
                <p>{{ $result->ai_response }}</p>
            </div>
        </div>

        <div class="section">
            <h2>📦 Raw Blockchain Data</h2>
            <pre>{{ json_encode($result->raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    @endisset
@endsection
