@extends('layouts.base')

@section('title', 'Satscribe History')

@section('content')
    <section id="history" class="sm:px-6 lg:px-8 px-4 py-6">
        {{-- Header --}}
        <header class="section-header">
            <div class="flex flex-col">
                <h1 class="text-3xl sm:text-4xl font-bold leading-tight">
                    History
                </h1>
                <p class="text-lg mt-2">
                    From detailed tx investigations to high-level block summaries.
                    <br class="hidden sm:block">
                    <small>
                        Dive into personal insights or
                        <a href="{{ route('history.index', ['all' => 1]) }}">browse the archive</a>
                        of the community. Discover how others interpret txs and blocks.
                    </small>
                </p>
            </div>
        </header>

        @if ($chats->isEmpty())
            <div class="mt-10 flex flex-col items-center text-center">
                <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                    <circle cx="24" cy="24" r="22" stroke-width="2" class="text-gray-300" />
                    <path d="M12 29c1.5-4 7-6 12-6s10.5 2 12 6" stroke-width="2" stroke-linecap="round" />
                    <circle cx="17" cy="21" r="2" fill="currentColor"/>
                    <circle cx="31" cy="21" r="2" fill="currentColor"/>
                </svg>
                <h2 class="text-xl font-semibold mb-2">No history yet!</h2>
                <p class="mb-4">Your history is empty — start your first Bitcoin analysis and be part of the collective knowledge.</p>
                <a href="{{ url('/') }}" class="form-button">
                    Start Satscribing
                </a>
            </div>
        @else
            <ul class="chat-list">
                @foreach($chats as $chat)
                    <x-history.item
                        :chat="$chat"
                        :owned="($chat->creator_ip === client_ip())"
                    />
                @endforeach
            </ul>
            <x-history.pagination :paginator="$chats"/>
        @endif
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggleDescription = (targetId) => {
                const body = document.querySelector(`.chat-body[data-target="${targetId}"]`);
                const content = document.getElementById(targetId);
                const button = document.querySelector(`.toggle-chat-btn[data-target="${targetId}"]`);

                if (!body || !content) return;

                const isCollapsed = body.classList.toggle('collapsed');
                content.classList.toggle('max-h-[8.5rem]', isCollapsed);

                if (button) {
                    const fullLabel = button.querySelector('.full-label');
                    const shortLabel = button.querySelector('.short-label');

                    if (fullLabel) fullLabel.textContent = isCollapsed ? 'Show full response' : 'Hide full response';
                    if (shortLabel) shortLabel.textContent = isCollapsed ? 'Full' : 'Hide';
                }
            };

            // Event delegation for better performance (especially on pagination)
            const historySection = document.getElementById('history');

            historySection.addEventListener('click', (event) => {
                const body = event.target.closest('.chat-body');
                const button = event.target.closest('.toggle-chat-btn');

                if (button) {
                    event.stopPropagation();
                    const targetId = button.dataset.target;
                    if (targetId) toggleDescription(targetId);
                } else if (body) {
                    const targetId = body.dataset.target;
                    if (targetId) toggleDescription(targetId);
                }
            });
        });
    </script>
@endsection
