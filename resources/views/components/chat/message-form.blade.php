@props(['chat'])
<div x-data="{ message: '' }" class="w-full">
    <form @submit.prevent="sendMessageToChat('{{ $chat->ulid }}', message)" class="flex w-full gap-2">
        <input
            id="customFollowUp"
            type="text"
            x-model="message"
            @input="errorFollowUpQuestion = ''"
            class="w-3/4 p-2 border rounded"
            placeholder="Ask a follow-up question..."
            autocomplete="off"
        />
        <button
            type="submit"
            class="w-1/4 form-button flex items-center justify-center"
        >
            <span class="submit-icon mr-2" x-cloak>
                <i data-lucide="send" class="w-4 h-4"></i>
            </span>
            <span class="submit-text" x-cloak>Send</span>
        </button>
    </form>
    <template x-if="errorFollowUpQuestion">
        <span class="block text-sm text-red-600 mt-1" x-text="errorFollowUpQuestion"></span>
    </template>
</div>
