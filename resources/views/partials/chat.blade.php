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
@endphp

<section id="chat-container" class="chat-body w-full flex flex-col flex-grow min-h-0">
    <!-- Chat header with New Chat button -->
    <div id="chat-header" class="flex-shrink-0 flex items-center justify-between p-2 border-b border-gray-200">
        <span class="text-sm text-gray-600">{{ __('Chat') }}</span>
        <a href="{{ route('home.index') }}" class="flex items-center gap-1 text-sm text-orange-600 hover:text-orange-700">
            <i data-lucide="plus" class="w-4 h-4"></i>
            {{ __('New Chat') }}
        </a>
    </div>

    <!-- Scrollable messages area -->
    <div id="chat-messages-scroll" class="flex-grow overflow-y-auto p-2">
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

    @if (tracking_id() === $chat->tracking_id)
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

        <div class="flex-shrink-0">
            <x-chat.raw-data-toggle-button :chat="$chat" />
        </div>
    @endif
</section>
