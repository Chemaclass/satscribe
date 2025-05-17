@php
    use Illuminate\Support\Str;
@endphp

@props(['message', 'loop', 'suggestions' => []])

@if(isset($message['meta']['force_refresh']) && $message['meta']['force_refresh'])
    <div class="alert-warning" role="alert">
        ⚠️ This transaction is unconfirmed. You might want to refresh later to get the latest status.
    </div>
@endif

<div
    id="{{ $loop->last ? 'last-message' : '' }}"
    class="section rounded p-4 shadow-sm mb-4"
>
    <h2 class="text-2xl font-bold mb-2 flex items-center msg-{{$message['role']}}">
        <i data-lucide="{{ $message['role'] === 'assistant' ? 'bot' : 'user' }}" class="w-6 h-6 mr-2"></i>
        {{ ucfirst($message['role']) }}
    </h2>

    <div class="prose dark:prose-invert">
        {!! Str::markdown($message['content']) !!}
    </div>

    @if($loop->last && $message['role'] === 'assistant' && client_ip() === $message->creator_ip)
        <x-chat.follow-up-suggestions
            :input="data_get($message['meta'], 'input')"
            :question="data_get($message['meta'], 'question', '')"
            :suggestions="$suggestions"
            :message="$message"
        />

        <x-chat.raw-data-toggle-button
            :id="data_get($message->chat->getFirstAssistantMessage(), 'id')"
            :input="data_get($message['meta'], 'input')"
            :question="data_get($message['meta'], 'question', '')"
            :createdAt="data_get($message, 'created_at')"
        />
    @endif
</div>
