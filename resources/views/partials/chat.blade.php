@props([
    'chat',
    'question' => '',
    'suggestions' => [],
])

@php
    $filteredSuggestions = collect($suggestions)
        ->filter(fn($s) => trim($s) !== trim($question))
        ->values();

    /** @var \App\Models\Chat $chat */
    $message = $chat->getLastUserMessage();
    $chatInput = data_get($message, 'meta.input', '');
    $isBlock = strlen($chatInput) < 10 || str_starts_with($chatInput, '00000000');
    $chatContext = $isBlock
        ? __('Block') . ' #' . $chatInput
        : __('Transaction') . ' ' . Str::limit($chatInput, 12, '...');
@endphp

<section id="chat-container" class="chat-body w-full flex flex-col flex-grow min-h-0">
    <!-- Chat header with context and New Chat button -->
    <div id="chat-header" class="flex-shrink-0 flex items-center justify-between p-2 border-b border-gray-200">
        <div class="flex items-center gap-2">
            <i data-lucide="{{ $isBlock ? 'box' : 'arrow-right-left' }}" class="w-4 h-4 text-gray-500"></i>
            <span class="text-sm font-medium text-gray-700">{{ $chatContext }}</span>
        </div>
        <a href="{{ route('home.index') }}" class="flex items-center gap-1 text-sm text-orange-600 hover:text-orange-700">
            <i data-lucide="plus" class="w-4 h-4"></i>
            {{ __('New Chat') }}
        </a>
    </div>

    <!-- Scrollable messages area -->
    <div id="chat-messages-scroll" class="flex-grow overflow-y-auto p-2 relative">
        <div id="chat-message-groups">
            @foreach($chat->messageGroups() as $group)
                <x-chat.message-group
                    :userMsg="$group['userMsg']"
                    :assistantMsg="$group['assistantMsg']"
                    :owned="tracking_id() === $chat->tracking_id"
                />
            @endforeach
        </div>
    </div>

    <!-- Scroll to bottom button -->
    <div
        id="scroll-to-bottom-btn"
        x-data="{ show: false }"
        x-init="
            const scrollEl = document.getElementById('chat-messages-scroll');
            if (scrollEl) {
                scrollEl.addEventListener('scroll', () => {
                    const threshold = 100;
                    show = scrollEl.scrollHeight - scrollEl.scrollTop - scrollEl.clientHeight > threshold;
                });
            }
        "
        x-show="show"
        x-cloak
        x-transition
        class="absolute bottom-24 right-4 z-10"
    >
        <button
            type="button"
            @click="scrollChatToBottom()"
            class="p-2 bg-white border border-gray-300 rounded-full shadow-lg hover:bg-gray-50 transition-colors"
            title="{{ __('Scroll to bottom') }}"
        >
            <i data-lucide="chevron-down" class="w-5 h-5 text-gray-600"></i>
        </button>
    </div>

    @if (tracking_id() === $chat->tracking_id)
        <!-- Chat actions (Share, Mempool, Raw data) -->
        <div class="flex-shrink-0 border-t border-gray-200 px-2">
            <x-chat.raw-data-toggle-button :chat="$chat" />
        </div>

        <!-- Sticky form at bottom -->
        <div id="chat-message-form-container" class="flex-shrink-0 border-t border-gray-200 bg-inherit p-2">
            <x-chat.message-form :chat="$chat"/>

            <x-chat.follow-up-suggestions
                :input="data_get($message['meta'], 'input')"
                :question="data_get($message['meta'], 'question', '')"
                :suggestions="$suggestions"
                :message="$message"
            />
        </div>
    @endif
</section>
