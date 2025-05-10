@props([
    'input',
    'question' => '',
])

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
            class="form-input w-full sm:w-auto flex-1 transition"
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
</div>
