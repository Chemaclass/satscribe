@props([
    'input',
    'question' => '',
    'suggestions' => [],
])

@php
    $suggestions = collect($suggestions)
        ->filter(fn($s) => trim($s) !== trim($question))
        ->values();
@endphp

<div class="mt-6 follow-up-suggestions" x-data>
    <h3 class="text-sm font-semibold mb-2">
        Ask a follow-up
    </h3>

    <div
        class="flex flex-col sm:flex-row gap-3 sm:items-center"
        @keydown.enter="resubmit('{{ $input }}', $refs.customFollowUp.value)"
    >
        <input
            type="text"
            x-ref="customFollowUp"
            class="form-input w-full sm:w-auto flex-1 px-4 py-2 rounded-md shadow-sm focus:ring-2 focus:outline-none transition placeholder-gray-400 dark:placeholder-gray-500"
            placeholder="Type your question here"
        />

        <button
            type="button"
            class="form-button"
            @click="resubmit('{{ $input }}', $refs.customFollowUp.value)"
        >
            Submit
        </button>
    </div>

    @if ($suggestions->isNotEmpty())
        <div class="mt-4">
            <p class="text-sm font-medium mb-2">Or try one of these</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($suggestions as $suggestion)
                    <button
                        type="button"
                        class="suggested-question-prompt px-3 py-1 rounded-full text-sm transition"
                        @click="resubmit('{{ $input }}', '{{ $suggestion }}')"
                    >
                        {{ $suggestion }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif
</div>
