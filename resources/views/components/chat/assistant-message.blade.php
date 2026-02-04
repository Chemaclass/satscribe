@props([
    'assistantMsg',
])

@php
    $contentLength = strlen($assistantMsg->content);
    $isLongMessage = $contentLength > 1500;
    $assistantCreatedAt = $assistantMsg->created_at;
    $assistantOlderThan5Min = $assistantCreatedAt && $assistantCreatedAt->lt(now()->subMinutes(5));
@endphp

<div
    class="assistant-message text-left"
    x-data="{
        copied: false,
        expanded: {{ $isLongMessage ? 'false' : 'true' }},
        copyContent() {
            const content = this.$refs.content.innerText;
            navigator.clipboard.writeText(content).then(() => {
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);
            });
        }
    }"
>
    <div class="flex items-center gap-1 group relative">
        <i data-lucide="bot" class="w-6 h-6"></i>
        <span class="font-semibold">Scribe</span>
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
    <div
        x-ref="content"
        class="inline-block rounded px-3 py-2 message-content transition-all duration-300"
        :class="{ 'max-h-64 overflow-hidden': !expanded && {{ $isLongMessage ? 'true' : 'false' }} }"
    >
        {!! Str::markdown($assistantMsg->content) !!}
    </div>

    <!-- Action buttons -->
    <div class="flex items-center gap-2 mt-1 ml-3">
        @if($isLongMessage)
            <button
                type="button"
                @click="expanded = !expanded"
                class="text-xs text-gray-500 hover:text-gray-700 flex items-center gap-1"
            >
                <i x-show="!expanded" data-lucide="chevron-down" class="w-3 h-3"></i>
                <i x-show="expanded" x-cloak data-lucide="chevron-up" class="w-3 h-3"></i>
                <span x-text="expanded ? '{{ __('Show less') }}' : '{{ __('Show more') }}'"></span>
            </button>
        @endif
        <button
            type="button"
            @click="copyContent()"
            class="text-xs text-gray-500 hover:text-gray-700 flex items-center gap-1"
        >
            <i x-show="!copied" data-lucide="copy" class="w-3 h-3"></i>
            <i x-show="copied" x-cloak data-lucide="check" class="w-3 h-3 text-green-600"></i>
            <span x-text="copied ? '{{ __('Copied!') }}' : '{{ __('Copy') }}'"></span>
        </button>
    </div>
</div>
