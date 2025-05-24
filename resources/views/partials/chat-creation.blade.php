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
    $assistantMsg = $chat->getLastAssistantMessage();
    $message = $chat->getFirstUserMessage();
@endphp

<section id="chat-container" class="chat-body w-full p-2">
    <div id="chat-message-groups">
        <x-chat.message-group
            :userMsg="$chat->getFirstUserMessage()"
            :assistantMsg="$assistantMsg"
        />
    </div>

    <x-chat.message-form :chat="$chat"/>

    @if (tracking_id() === $chat->tracking_id)
        <x-chat.follow-up-suggestions
            :input="data_get($message['meta'], 'input')"
            :question="data_get($message['meta'], 'question', '')"
            :suggestions="$suggestions"
            :message="$message"
        />
    @endif
    <x-chat.raw-data-toggle-button :chat="$chat" />
</section>
