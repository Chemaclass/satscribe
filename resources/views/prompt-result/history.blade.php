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
                        $entryId = 'entry-' . $desc->id;
                    @endphp

                    <li class="description-entry mb-8">
                        <div class="description-header font-medium mb-1">
                            <strong>{{ ucfirst($desc->type) }}:</strong>
                            @if ($mempoolUrl)
                                <a href="{{ $mempoolUrl }}" target="_blank" rel="noopener" class="mempool-link">
                                    {{ $desc->input }}
                                </a>
                            @else
                                {{ $desc->input }}
                            @endif
                        </div>

                        @if (!empty($desc->question))
                            <div class="mb-2 text-sm italic text-orange-700 bg-orange-50 px-3 py-2 rounded">
                                <strong>Question:</strong> {{ $desc->question }}
                            </div>
                        @endif
                        <div class="description-body relative">
                            <div id="{{ $entryId }}" class="markdown-content collapsed-response overflow-hidden max-h-[6.5rem] transition-all duration-300">
                                {!! Str::markdown($desc->ai_response) !!}
                            </div>
                        </div>
                        <div class="description-meta mt-2 flex justify-between items-center text-sm text-gray-500">
                            <span>{{ $desc->created_at->diffForHumans() }}</span>
                            <button type="button"
                                    class="toggle-raw-button text-blue-600 hover:underline"
                                    data-target="raw-{{ $desc->id }}">
                                Show raw data
                            </button>
                        </div>
                        <pre id="raw-{{ $desc->id }}"
                             class="mt-2 bg-gray-100 text-xs p-3 rounded overflow-auto max-h-64 hidden">
{{ json_encode($desc->raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}
                        </pre>
                        <div class="entry-divider h-px bg-gray-200 mt-6"></div>
                    </li>
                @endforeach
            </ul>

            <div class="pagination mt-8 flex justify-center gap-4">
                @if ($descriptions->onFirstPage())
                    <span class="px-4 py-2 bg-orange-100 text-orange-400 rounded-md cursor-not-allowed">« Previous</span>
                @else
                    <a href="{{ $descriptions->previousPageUrl() }}"
                       class="px-4 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600 transition">
                        « Previous
                    </a>
                @endif

                @if ($descriptions->hasMorePages())
                    <a href="{{ $descriptions->nextPageUrl() }}"
                       class="px-4 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600 transition">
                        Next »
                    </a>
                @else
                    <span class="px-4 py-2 bg-orange-100 text-orange-400 rounded-md cursor-not-allowed">Next »</span>
                @endif
            </div>
        @endif
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Toggle AI response collapse
            document.querySelectorAll('.toggle-response-button').forEach(button => {
                button.addEventListener('click', () => {
                    const targetId = button.dataset.target;
                    const target = document.getElementById(targetId);
                    const isCollapsed = target.classList.contains('collapsed-response');

                    target.classList.toggle('collapsed-response');
                    if (isCollapsed) {
                        target.classList.remove('max-h-[6.5rem]');
                        button.textContent = 'Show less';
                    } else {
                        target.classList.add('max-h-[6.5rem]');
                        button.textContent = 'Show more';
                    }
                });
            });

            // Toggle raw JSON display
            document.querySelectorAll('.toggle-raw-button').forEach(button => {
                button.addEventListener('click', () => {
                    const targetId = button.dataset.target;
                    const rawBlock = document.getElementById(targetId);
                    const isHidden = rawBlock.classList.contains('hidden');

                    rawBlock.classList.toggle('hidden');
                    button.textContent = isHidden ? 'Hide raw data' : 'Show raw data';
                });
            });
        });
    </script>
@endsection
