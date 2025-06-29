@props([
    'userMsg',
    'owned' => false,
])

<div class="user-message mb-2 text-right" data-owned="{{ $owned ? '1' : '0' }}">
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
        <div class="inline-block rounded px-3 py-2">
            {{ $userMsg->content }}
        </div>
        <i data-lucide="user" class="w-6 h-6"></i>
    </div>
</div>
