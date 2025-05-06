@props(['input'])

<div class="mt-6 follow-up-suggestions">
    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Try a follow-up:</h3>
    <div class="flex flex-wrap gap-2">
        @php
            // todo: pass these from controller
            $suggestions = [
                "Compare with previous block?",
                "Summarize for a friend",
                "What are the fees involved?",
                "How many confirmations does it have?",
                "Break down the inputs/outputs",
            ];
        @endphp

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
