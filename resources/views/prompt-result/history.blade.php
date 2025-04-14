@extends('layouts.base')

@section('title', 'Satscribe History – AI Bitcoin Describer')

@section('content')
    <section>
        <h2 class="text-2xl font-semibold text-gray-800 mb-6 flex items-center gap-2">
            <span>📜</span>
            <span>History</span>
        </h2>

        @if ($descriptions->isEmpty())
            <p>No descriptions found yet.</p>
        @else
            <ul class="description-list">
                @foreach($descriptions as $desc)
                    @php
                        $mempoolUrl = match ($desc->type) {
                            'transaction' => "https://mempool.space/tx/{$desc->input}",
                            'block' => "https://mempool.space/block/{$desc->input}",
                            default => null,
                        };
                    @endphp

                    <li class="description-entry">
                        <div class="description-header">
                            <strong>{{ ucfirst($desc->type) }}:</strong>
                            @if ($mempoolUrl)
                                <a href="{{ $mempoolUrl }}" target="_blank" rel="noopener" class="mempool-link">
                                    {{ $desc->input }}
                                </a>
                            @else
                                {{ $desc->input }}
                            @endif
                        </div>

                        <div class="description-body">
                            {!! Str::markdown($desc->ai_response) !!}
                        </div>

                        <div class="description-meta">
                            <small>{{ $desc->created_at->diffForHumans() }}</small>
                        </div>

                        <div class="entry-divider" style="height: 1px; background: #e5e7eb; margin-top: 1.5rem;"></div>
                    </li>
                @endforeach
            </ul>

            <div class="pagination">
                {{ $descriptions->links() }}
            </div>
        @endif
    </section>
@endsection
