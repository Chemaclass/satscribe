@props([
    'userMsg',
    'assistantMsg',
    'owned' => false,
])

<div class="chat-message-group mb-6">
    @if ($userMsg)
        <x-chat.user-message :userMsg="$userMsg" :owned="$owned"/>
    @endif

    @if ($assistantMsg)
        <x-chat.assistant-message :assistantMsg="$assistantMsg"/>
    @endif
</div>
