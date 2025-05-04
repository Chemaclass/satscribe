@extends('layouts.base')

@section('title', 'Satscribe History')

@section('content')
    <section id="history" class="sm:px-6 lg:px-8 px-4 py-6">
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
                                <p class="truncate overflow-hidden text-ellipsis">
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
                        {{-- Show Persona --}}
                        @if ($desc->persona)
                            <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ $desc->persona->label() }}
                            </div>
                        @endif
                        @if (!empty($desc->question))
                        <div class="description-question">
                            <strong>Question:</strong> {{ $desc->question }}
                        </div>
                        @endif
                        <div class="description-body relative collapsed" data-target="{{ $entryId }}">
                            <div id="{{ $entryId }}" class="prose markdown-content overflow-hidden max-h-[8.5rem] transition-all duration-300">
                                {!! Str::markdown($desc->ai_response) !!}
                            </div>
                        </div>
                        <div class="description-meta mt-2 flex justify-between items-center text-sm text-gray-500">
                            <span>{{ $desc->created_at->diffForHumans() }}</span>

                            <div class="flex gap-4 items-center">
                                <button type="button"
                                        class="toggle-description-btn link"
                                        data-target="{{ $entryId }}">
                                    Show full response
                                </button>
                                <button type="button"
                                        class="toggle-history-raw-btn link"
                                        data-target="raw-{{ $desc->id }}"
                                        data-id="{{ $desc->id }}">
                                    Show raw data
                                </button>
                            </div>
                        </div>
                    <pre id="raw-{{ $desc->id }}"
                         class="hidden bg-gray-100 text-xs p-3 rounded overflow-auto max-h-96 whitespace-pre-wrap"
                         data-loaded="false">
    <span class="loading">Loading...</span>
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
            function toggleDescription(targetId) {
                const body = document.querySelector(`.description-body[data-target="${targetId}"]`);
                const content = document.getElementById(targetId);
                const button = document.querySelector(`.toggle-description-btn[data-target="${targetId}"]`);

                body.classList.toggle('collapsed');
                const isNowCollapsed = body.classList.contains('collapsed');
                content.classList.toggle('max-h-[8.5rem]', isNowCollapsed);

                if (button) {
                    button.textContent = isNowCollapsed ? 'Show full response' : 'Hide full response';
                }
            }

            // Click on .description-body
            document.querySelectorAll('.description-body').forEach(body => {
                body.addEventListener('click', () => {
                    const targetId = body.dataset.target;
                    toggleDescription(targetId);
                });
            });

            // Click on button
            document.querySelectorAll('.toggle-description-btn').forEach(button => {
                button.addEventListener('click', (e) => {
                    e.stopPropagation(); // prevent also triggering the body click
                    const targetId = button.dataset.target;
                    toggleDescription(targetId);
                });
            });
        });
    </script>
@endsection
