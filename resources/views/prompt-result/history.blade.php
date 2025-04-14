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

                        <div class="description-body relative">
                            <div id="{{ $entryId }}" class="collapsed-response overflow-hidden max-h-[6.5rem] transition-all duration-300">
                                {!! Str::markdown($desc->ai_response) !!}
                            </div>

                            <button type="button"
                                    data-target="{{ $entryId }}"
                                    class="toggle-response-button mt-2 text-sm text-orange-600 hover:underline cursor-pointer">
                                Show more
                            </button>
                        </div>

                        <div class="description-meta text-sm text-gray-500 mt-2">
                            {{ $desc->created_at->diffForHumans() }}
                        </div>

                        <div class="entry-divider h-px bg-gray-200 mt-6"></div>
                    </li>
                @endforeach
            </ul>

            <div class="pagination mt-8">
                {{ $descriptions->links() }}
            </div>
        @endif
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
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
        });
    </script>
@endsection
