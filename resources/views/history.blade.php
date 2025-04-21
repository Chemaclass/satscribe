@extends('layouts.base')

@section('title', 'Satscribe History')

@section('content')
    <section class="sm:px-6 lg:px-8 px-4 py-6">
        {{-- Header --}}
        <header class="section-header">
            <div class="flex flex-col max-w-2xl">
                <h1 class="text-2xl sm:text-3xl font-bold leading-tight">History</h1>
                <p class="subtitle text-base sm:text-lg text-gray-700 dark:text-gray-300">
                    Browse your past Bitcoin transaction or block analyses.
                </p>
            </div>
        </header>

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

                <li class="description-item">
                    <div class="description-header font-medium mb-1">
                            <div class="w-full">
                                <strong>{{ ucfirst($desc->type) }}:</strong>
                                <p class="truncate max-w-full overflow-hidden text-ellipsis">
                                    @if ($mempoolUrl)
                                        <a href="{{ $mempoolUrl }}" target="_blank" rel="noopener" class="mempool-link">
                                            {{ $desc->input }}
                                        </a>
                                    @else
                                        {{ $desc->input }}
                                    @endif
                                </p>
                            </div>

                        </div>

                        @if (!empty($desc->question))
                        <div class="description-question">
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
                                    class="toggle-history-raw-btn text-blue-600 hover:underline"
                                    data-target="raw-{{ $desc->id }}"
                                    data-id="{{ $desc->id }}">
                                Show raw data
                            </button>
                        </div>
                    <pre id="raw-{{ $desc->id }}"
                         class="hidden bg-gray-100 text-xs p-3 rounded overflow-auto max-h-64 whitespace-pre-wrap"
                         data-loaded="false">
                        <span class="loading"></span>
                    </pre>
                </li>
                @endforeach
            </ul>

            <div class="pagination flex justify-center gap-4">
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
            document.querySelectorAll('.toggle-history-raw-btn').forEach(button => {
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
