@props(['chat'])

@php
    /** @var \App\Models\Chat $chat */
    $assistantMsg = $chat->getFirstAssistantMessage();
    $rawData = $assistantMsg->rawData ?? [];
    $mempoolUrl = $assistantMsg->isBlock()
        ? 'https://mempool.space/block/' . ($rawData['hash'] ?? $assistantMsg->input)
        : 'https://mempool.space/tx/' . ($rawData['txid'] ?? $assistantMsg->input);
@endphp

<div class="chat-meta py-3 flex flex-wrap justify-between items-center gap-2 text-sm">
    <span class="text-gray-400 text-xs">
        <i data-lucide="clock" class="w-3 h-3 inline-block mr-1"></i>
        {{ $chat->getLastAssistantMessage()->created_at->diffForHumans() }}
    </span>
    <div class="flex gap-3 items-center">
        <div x-data="{ copied: false }" class="relative">
            <button type="button"
                    @click="axios.post('{{ route('chat.share', $chat) }}', { shared: true }); navigator.clipboard.writeText(window.location.href).then(() => { copied = true; setTimeout(() => copied = false, 2000); })"
                    class="chat-action-btn">
                <i data-lucide="share-2" class="w-3.5 h-3.5"></i>
                <span class="hidden sm:inline">{{ __('Share') }}</span>
            </button>
            <span x-show="copied" x-transition
                  class="absolute left-1/2 -translate-x-1/2 top-full mt-1 text-xs text-green-600 whitespace-nowrap"
                  style="display: none;">
                {{ __('Link Copied!') }}
            </span>
        </div>
        <a href="{{ $mempoolUrl }}" target="_blank" rel="noopener" class="chat-action-btn">
            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
            <span class="hidden sm:inline">{{ __('Mempool') }}</span>
        </a>
        <button type="button"
                class="toggle-history-raw-btn chat-action-btn"
                data-target="raw-container-{{ $assistantMsg->id }}"
                data-id="{{ $assistantMsg->id }}">
            <i data-lucide="code" class="w-3.5 h-3.5 icon-show"></i>
            <i data-lucide="x" class="w-3.5 h-3.5 icon-hide hidden"></i>
            <span class="full-label hidden sm:inline">{{ __('Show raw data') }}</span>
            <span class="short-label sm:hidden">{{ __('Raw') }}</span>
        </button>
    </div>
</div>

<!-- Raw Data Container -->
<div
    id="raw-container-{{ $assistantMsg->id }}"
    class="hidden raw-data-container mb-2"
    data-loaded="false"
    x-data="{ copied: false }"
>
    <div class="raw-data-header">
        <div class="flex items-center gap-2">
            <i data-lucide="braces" class="w-4 h-4"></i>
            <span class="font-medium">{{ __('Raw JSON Data') }}</span>
        </div>
        <button
            type="button"
            @click="
                const content = $el.closest('.raw-data-container').querySelector('.raw-data-content').innerText;
                navigator.clipboard.writeText(content).then(() => {
                    copied = true;
                    setTimeout(() => copied = false, 2000);
                });
            "
            class="raw-data-copy-btn"
        >
            <i x-show="!copied" data-lucide="copy" class="w-3.5 h-3.5"></i>
            <i x-show="copied" x-cloak data-lucide="check" class="w-3.5 h-3.5 text-green-600"></i>
            <span x-text="copied ? '{{ __('Copied!') }}' : '{{ __('Copy') }}'"></span>
        </button>
    </div>
    <pre class="raw-data-content"><span class="loading">{{ __('Loading...') }}</span></pre>
</div>
