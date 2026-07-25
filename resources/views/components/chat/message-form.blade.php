@props(['chat'])
@php
    use Modules\Shared\Domain\Chat\ChatConstants;
@endphp
<div x-data="{ message: '', isSending: false }" class="w-full pt-1">
    <form @submit.prevent="if (!isSending && !isStreaming && message.trim()) { isSending = true; sendMessageToChat('{{ $chat->ulid }}', message).finally(() => { isSending = false; message = ''; }); }" class="flex w-full items-center gap-2">
        <input
            id="customFollowUp"
            type="text"
            x-model="message"
            @input="errorFollowUpQuestion = ''"
            @keydown.enter.prevent="if (!isSending && !isStreaming && message.trim()) { $el.closest('form').requestSubmit(); }"
            :disabled="isSending || isStreaming"
            {{-- text-base keeps iOS Safari from zooming in when the field is focused --}}
            class="flex-1 min-w-0 p-2 text-base border rounded disabled:opacity-50"
            placeholder="{{ __('Ask a follow-up question...') }}"
            {{-- Mirrors the server rule so the limit is visible while typing
                 rather than arriving as a rejected request. --}}
            maxlength="{{ ChatConstants::MAX_QUESTION_LENGTH }}"
            autocomplete="off"
        />
        <button
            type="submit"
            :disabled="isSending || isStreaming || !message.trim()"
            aria-label="{{ __('Send') }}"
            class="form-button shrink-0 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <span class="submit-icon sm:mr-2" x-cloak>
                <i x-show="!isSending" data-lucide="send" class="w-4 h-4"></i>
                <i x-show="isSending" x-cloak data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
            </span>
            <span class="submit-text hidden sm:inline" x-cloak x-text="isSending ? '{{ __('Sending...') }}' : '{{ __('Send') }}'"></span>
        </button>
    </form>
    <template x-if="errorFollowUpQuestion">
        <span class="block text-sm text-red-600 mt-1" x-text="errorFollowUpQuestion"></span>
    </template>
</div>
