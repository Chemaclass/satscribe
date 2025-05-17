@props([
    'userMsg',
    'assistantMsg',
])

<div class="chat-message-group mb-6">
    @if($userMsg)
        <div class="user-message mb-2 text-right">
            <div class="flex items-center gap-1 justify-end group relative">
                @php
                    $userCreatedAt = $userMsg->created_at;
                    $userOlderThan5Min = $userCreatedAt && $userCreatedAt->lt(now()->subMinutes(5));
                @endphp
                <span
                    class="opacity-0 group-hover:opacity-100 invisible group-hover:visible
                           transition-opacity duration-300 text-xs absolute -top-5 right-0"
                >
                    {{ $userOlderThan5Min
                        ? $userCreatedAt->format('Y-m-d H:i:s')
                        : $userCreatedAt?->diffForHumans()
                    }}
                </span>
                <i data-lucide="user" class="w-6 h-6"></i>
                <div class="inline-block rounded px-3 py-2">
                    {{ $userMsg->content }}
                </div>
            </div>
        </div>
    @endif

    @if($assistantMsg)
        <div class="assistant-message text-left">
            <div class="flex items-center gap-1 group relative">
                <i data-lucide="bot" class="w-6 h-6"></i>
                <span class="font-semibold">Assistant</span>
                @php
                    $assistantCreatedAt = $assistantMsg->created_at;
                    $assistantOlderThan5Min = $assistantCreatedAt && $assistantCreatedAt->lt(now()->subMinutes(5));
                @endphp
                <span
                    class="opacity-0 group-hover:opacity-100 invisible group-hover:visible
                           transition-opacity duration-300 text-xs absolute -top-5 left-0"
                >
                    {{ $assistantOlderThan5Min
                        ? $assistantCreatedAt->format('Y-m-d H:i:s')
                        : $assistantCreatedAt?->diffForHumans()
                    }}
                </span>
            </div>
            <div class="inline-block rounded px-3 py-2">
                {!! Str::markdown($assistantMsg->content) !!}
            </div>
        </div>
    @endif
</div>
