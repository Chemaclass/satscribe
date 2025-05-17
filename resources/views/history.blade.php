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
            <ul class="description-list">
                @foreach($chats as $chat)
                    <x-history.item :chat="$chat"/>
                @endforeach
            </ul>
            <x-history.pagination :paginator="$chats"/>
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

                // Update labels inside the button, not the whole text content
                if (button) {
                    const fullLabel = button.querySelector('.full-label');
                    const shortLabel = button.querySelector('.short-label');

                    if (fullLabel) fullLabel.textContent = isNowCollapsed ? 'Show full response' : 'Hide full response';
                    if (shortLabel) shortLabel.textContent = isNowCollapsed ? 'Full' : 'Hide';
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
