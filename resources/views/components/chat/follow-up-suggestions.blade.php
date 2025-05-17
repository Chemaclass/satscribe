@props([
    'message',
    'question' => '',
    'suggestions' => [],
])

@php
    $filteredSuggestions = collect($suggestions)
        ->filter(fn($s) => trim($s) !== trim($question))
        ->values();

    /** @var \App\Models\Chat $chat */
    $chat = $message->chat;
@endphp

<div class="mt-6 follow-up-suggestions">
    <div class="mt-4">
        <p class="text-sm font-medium mb-2">Or try one of these</p>
        <div class="flex flex-wrap gap-2">
            @foreach($filteredSuggestions as $suggestion)
                <form
                    @submit.prevent="sendMessageToChat('{{ $chat->ulid }}', '{{ addslashes($suggestion) }}')"
                    class="inline"
                >
                    <button type="submit" class="suggested-question-prompt">
                        {{ $suggestion }}
                    </button>
                </form>
            @endforeach
        </div>
    </div>
</div>
