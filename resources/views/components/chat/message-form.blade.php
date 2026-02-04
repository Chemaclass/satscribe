@props(['chat'])
<div x-data="{ message: '', isSending: false }" class="w-full pt-1">
    <form @submit.prevent="if (!isSending && message.trim()) { isSending = true; sendMessageToChat('{{ $chat->ulid }}', message).finally(() => { isSending = false; message = ''; }); }" class="flex w-full gap-2">
        <input
            id="customFollowUp"
            type="text"
            x-model="message"
            @input="errorFollowUpQuestion = ''"
            @keydown.enter.prevent="if (!isSending && message.trim()) { $el.closest('form').requestSubmit(); }"
            :disabled="isSending"
            class="w-3/4 p-2 border rounded disabled:opacity-50"
            placeholder="{{ __('Ask a follow-up question...') }}"
            autocomplete="off"
        />
        <button
            type="submit"
            :disabled="isSending || !message.trim()"
            class="w-1/4 form-button flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <span class="submit-icon mr-2" x-cloak>
                <i x-show="!isSending" data-lucide="send" class="w-4 h-4"></i>
                <i x-show="isSending" x-cloak data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
            </span>
            <span class="submit-text" x-cloak x-text="isSending ? '{{ __('Sending...') }}' : '{{ __('Send') }}'"></span>
        </button>
    </form>
    <template x-if="errorFollowUpQuestion">
        <span class="block text-sm text-red-600 mt-1" x-text="errorFollowUpQuestion"></span>
    </template>
</div>
