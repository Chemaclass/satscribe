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

<div
    id="follow-up-suggestions"
    x-data="{ suggestions: @js($filteredSuggestions ?? []) }"
>
    <div class="mt-4">
        <p class="text-sm font-medium mb-2">{{ __('Or try one of these') }}</p>
        <div class="flex flex-wrap gap-2">
            <template x-for="suggestion in suggestions" :key="suggestion">
                <form @submit.prevent="sendMessageToChat('{{ $chat->ulid }}', suggestion)" class="inline">
                    <button type="submit" class="suggested-question-prompt" x-text="suggestion"></button>
                </form>
            </template>
        </div>
    </div>
</div>
