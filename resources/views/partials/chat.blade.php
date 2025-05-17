@props([
    'chat',
    'question' => '',
    'suggestions' => [],
])

<section id="chat-container" class="chat-body w-full p-2">
    @foreach($chat->messageGroups() as $group)
        <x-chat.message-group
            :userMsg="$group['userMsg']"
            :assistantMsg="$group['assistantMsg']"
        />
    @endforeach

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
        <span id="submit-icon" x-cloak class="mr-2">
            <i data-lucide="send" class="w-4 h-4"></i>
        </span>
                <span id="submit-text" x-cloak>Send</span>
            </button>
        </form>

        <template x-if="errorFollowUpQuestion">
            <span class="block text-sm text-red-600 mt-1" x-text="errorFollowUpQuestion"></span>
        </template>
    </div>

</section>
