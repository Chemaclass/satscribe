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

        @if ($chats->isEmpty())
            <p>Empty history.</p>
        @else
            <ul class="chat-list">
                @foreach($chats as $chat)
                    <x-history.item :chat="$chat"/>
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
