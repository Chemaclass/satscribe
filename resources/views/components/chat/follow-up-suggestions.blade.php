@props([
    'input',
    'message',
    'question' => '',
    'suggestions' => [],
])

@php
    $filteredSuggestions = collect($suggestions)
        ->filter(fn($s) => trim($s) !== trim($question))
        ->values();

    $chat = $message->chat;
@endphp

<div class="mt-6 follow-up-suggestions">
    <h3 class="text-sm font-semibold mb-2">
        Ask a follow-up
    </h3>

    <div x-data="{ message: '' }" class="w-full">
        <form @submit.prevent="sendMessageToChat('{{ $chat->ulid }}', message)" class="flex w-full gap-2">
            <input
                id="customFollowUp"
                type="text"
                x-model="message"
                class="w-3/4 p-2 border rounded"
                placeholder="Ask a follow-up question..."
                autocomplete="off"
            />

            <button
                type="submit"
                class="w-1/4 form-button flex items-center justify-center"
            >
            <span id="submit-icon" x-cloak class="mr-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
            </span>
                <span id="submit-text" x-cloak>Send</span>
            </button>
        </form>
    </div>

    <div class="mt-4">
        <p class="text-sm font-medium mb-2">Or try one of these</p>
        <div class="flex flex-wrap gap-2">
            @foreach($filteredSuggestions as $suggestion)
                <form
                    @submit.prevent="sendMessageToChat('{{ $chat->ulid }}', '{{ addslashes($suggestion) }}')"
                    class="inline"
                >
                    <button
                        type="submit"
                        class="suggested-question-prompt"
                    >
                        {{ $suggestion }}
                    </button>
                </form>
            @endforeach
        </div>
    </div>
</div>
