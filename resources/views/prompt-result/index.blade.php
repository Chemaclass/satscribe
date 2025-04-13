@extends('layouts.app')

@section('title', 'Satscribe – AI Bitcoin Describer')

@section('content')
    <h1>Understand Any Bitcoin Transaction or Block</h1>

    <form method="GET" action="{{ route('describe') }}">
        <input
            type="text"
            name="input"
            value="{{ old('input', $input ?? '') }}"
            placeholder="Enter TXID or block height..."
            required
        >
        <br>
        <button type="submit">Satscribe</button>
        @error('input') <div class="error">{{ $message }}</div> @enderror
    </form>
    @if ($refreshed ?? false)
        <p style="color: green;">ℹ️ Fresh result generated.</p>
    @endif
    @isset($result)
        <div class="section">
            <h2>🧠 AI Description</h2>
            <p>{{ $result->ai_response }}</p>
        </div>

        <div class="section">
            <h2>🔍 Raw Blockchain Data</h2>
            <pre>{{ json_encode($result->raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    @endisset
@endsection
