@props([
    'userMsg',
    'assistantMsg',
])

<div class="chat-message-group mb-6">
    @if ($userMsg)
        <x-chat.user-message :userMsg="$userMsg"/>
    @endif

    @if ($assistantMsg)
        <x-chat.assistant-message :assistantMsg="$assistantMsg"/>
    @endif
</div>
