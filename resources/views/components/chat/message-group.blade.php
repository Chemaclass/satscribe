@props([
    'userMsg',
    'assistantMsg',
])

<div class="chat-message-group mb-6">
    @if($userMsg)
        <div class="user-message mb-2 text-right">
            <div class="flex items-center gap-1 justify-end">
                <i data-lucide="user" class="w-4 h-4"></i>
                <span class="font-semibold">You:</span>
                <div class="inline-block rounded px-3 py-2">
                    {{ $userMsg->content }}
                </div>
            </div>
        </div>
    @endif

    @if($assistantMsg)
        <div class="assistant-message text-left">
            <span class="font-semibold flex items-center gap-1">
                <i data-lucide="bot" class="w-4 h-4"></i>
                Scribe:
            </span>

            <div class="inline-block rounded px-3 py-2">
                {!! Str::markdown($assistantMsg->content) !!}
            </div>
        </div>
    @endif
</div>
