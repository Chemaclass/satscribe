@props([
    'input',
    'question' => '',
    'suggestions' => [],
])

@php
    $filtered = collect($suggestions)
        ->filter(fn($s) => trim($s) !== trim($question))
        ->values();
@endphp

@if ($filtered->isNotEmpty())
    <div class="mt-6 follow-up-suggestions">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Try a follow-up:</h3>
        <div class="flex flex-wrap gap-2">
            @foreach ($filtered as $suggestion)
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
