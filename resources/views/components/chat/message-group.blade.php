@props([
    'userMsg',
    'assistantMsg',
])

<div class="chat-message-group mb-6">
    @if($userMsg)
        <div class="user-message mb-2 text-right">
            <span class="font-semibold">You:</span>
            <div class="inline-block rounded px-3 py-2">
                {{ $userMsg->content }}
            </div>
        </div>
    @endif

    @if($assistantMsg)
        <div class="assistant-message text-left">
            <span class="font-semibold">Scribe:</span>
            <div class="inline-block rounded px-3 py-2">
                {!! Str::markdown($assistantMsg->content) !!}
            </div>
        </div>
    @endif
</div>
