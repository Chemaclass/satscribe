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

<section id="chat-container" class="chat-body w-full p-2">
    <div id="chat-message-groups">
        @foreach($chat->messageGroups() as $group)
            <x-chat.message-group
                :userMsg="$group['userMsg']"
                :assistantMsg="$group['assistantMsg']"
            />
        @endforeach
    </div>

    @if (tracking_id() === $chat->tracking_id)
        <x-chat.message-form :chat="$chat"/>

        <x-chat.follow-up-suggestions
            :input="data_get($message['meta'], 'input')"
            :question="data_get($message['meta'], 'question', '')"
            :suggestions="$suggestions"
            :message="$message"
        />

        <x-chat.raw-data-toggle-button :chat="$chat" />
    @endif
</section>
