@props(['item'])

@php
    use Illuminate\Support\Str;
    use Modules\Chat\Domain\ViewModel\HistoryChatItem;
    /** @var HistoryChatItem $item */
    $entryId = 'entry-' . $item->assistantMessageId;
@endphp

<li class="chat-item">
    <div class="cursor-pointer w-full rounded-lg p-3 transition-colors duration-300"
         onclick="window.location.href='{{ route('chat.show', $item->ulid) }}'"
    >
        <div class="chat-header font-medium mb-1 flex justify-between items-start gap-2">
            <div>
                <strong>{{ ucfirst($item->type) }}:</strong>
                <span class="truncate overflow-hidden text-ellipsis block link text-left">
                    {{ $item->input }}
                </span>
            </div>
            <div class="flex gap-1 items-center">
                @if($item->owned)
                    <button type="button"
                            class="chat-visibility-btn relative group"
                            data-url="{{ route('chat.toggle-visibility', $item->ulid) }}"
                            data-public="{{ $item->isPublic ? '1' : '0' }}">
                        <i data-lucide="{{ $item->isPublic ? 'unlock' : 'lock' }}"
                           class="text-orange-700 w-6 h-6 cursor-pointer"
                           aria-label="{{ $item->isPublic ? 'Public chat' : 'Private chat' }}"
                           aria-hidden="false"
                           role="img"></i>

                        <span class="tooltip-content absolute z-10 bottom-full mb-1 left-1/2 -translate-x-1/2
                                     bg-gray-800 text-white text-xs font-medium px-2 py-1 rounded shadow-lg
                                     whitespace-nowrap opacity-0 group-hover:opacity-100 transition-all duration-200 ease-out
                                     pointer-events-none">
                            {{ $item->isPublic ? 'Public chat' : 'Private chat' }}
                        </span>
                    </button>
                @elseif(!$item->isPublic)
                    <span class="relative group">
                        <i data-lucide="lock"
                           class="text-orange-700 w-6 h-6 cursor-pointer"
                           aria-label="Private chat"
                           aria-hidden="false"
                           role="img"></i>

                        <span class="tooltip-content absolute z-10 bottom-full mb-1 left-1/2 -translate-x-1/2
                                     bg-gray-800 text-white text-xs font-medium px-2 py-1 rounded shadow-lg
                                     whitespace-nowrap opacity-0 group-hover:opacity-100 transition-all duration-200 ease-out
                                     pointer-events-none">
                            Private chat
                        </span>
                    </span>
                @endif

                @if($item->owned)
                <button type="button"
                        class="share-chat-toggle relative group cursor-pointer"
                        data-url="{{ route('chat.share', $item->ulid) }}"
                        data-link="{{ route('chat.show', $item->ulid) }}"
                        data-shared="{{ $item->isShared ? '1' : '0' }}">
                    <i data-lucide="share-2"
                       class="{{ $item->isShared ? 'text-orange-600' : 'text-gray-400' }} w-6 h-6"
                       aria-label="{{ $item->isShared ? 'Shared chat' : 'Not shared' }}"
                       aria-hidden="false"
                       role="img"></i>

                    <span class="tooltip-content absolute z-10 bottom-full mb-1 left-1/2 -translate-x-1/2
                                 bg-gray-800 text-white text-xs font-medium px-2 py-1 rounded shadow-lg
                                 whitespace-nowrap opacity-0 group-hover:opacity-100 transition-all duration-200 ease-out
                                 pointer-events-none">
                        {{ $item->isShared ? 'Shared chat' : 'Not shared' }}
                    </span>
                </button>
                <span class="relative group cursor-default">
                    <i data-lucide="badge-check"
                       class="text-orange-500 w-6 h-6"
                       aria-label="This chat belongs to you"
                       aria-hidden="false"
                       role="img"></i>

                    <span class="tooltip-content absolute z-10 bottom-full mb-1 left-1/2 -translate-x-1/2
                                 bg-gray-800 text-white text-xs font-medium px-2 py-1 rounded shadow-lg
                                 whitespace-nowrap opacity-0 group-hover:opacity-100 transition-all duration-200 ease-out
                                 pointer-events-none">
                        This chat belongs to you
                    </span>
                </span>
                @endif
            </div>
        </div>
    </div>
    <div class="chat-body relative cursor-pointer" onclick="window.location.href='{{ route('chat.show', $item->ulid) }}'">
        <div class="user-message mb-2 text-right" data-owned="{{ $item->owned ? '1' : '0' }}">
            <div class="flex items-center gap-1 justify-end">
                <div class="inline-block rounded px-3 py-2">
                    {{ $item->userMessage }}
                </div>
                <i data-lucide="user" class="w-6 h-6"></i>
            </div>
        </div>

        <div class="assistant-message text-left">
            <span class="font-semibold flex items-center gap-1">
                <i data-lucide="bot" class="w-6 h-6"></i>
                <span class="font-semibold">Scribe</span>
            </span>

            <div  id="{{ $entryId }}" class="inline-block rounded prose markdown-content overflow-hidden max-h-[8.5rem] transition-all duration-300">
                {!! Str::markdown($item->assistantMessage) !!}
            </div>
        </div>
    </div>
    <div class="chat-meta mt-2 flex justify-between items-center text-sm text-gray-500">
        <span>{{ $item->createdAt->diffForHumans() }}</span>
        <div class="flex gap-4 items-center">
            <a href="{{ $item->mempoolUrl }}" target="_blank" rel="noopener" class="link full-label hidden sm:inline">
                {{ __('View on mempool') }}
            </a>
            <a href="{{ $item->mempoolUrl }}" target="_blank" rel="noopener" class="link short-label inline sm:hidden">
                {{ __('Mempool') }}
            </a>
            <button type="button"
                    class="toggle-history-raw-btn link"
                    data-target="raw-{{ $item->assistantMessageId }}"
                    data-id="{{ $item->assistantMessageId }}">
                <span class="full-label hidden sm:inline">{{ __('Show raw data') }}</span>
                <span class="short-label inline sm:hidden">{{ __('Raw') }}</span>
            </button>
        </div>
    </div>
    <pre id="raw-{{ $item->assistantMessageId }}"
         class="hidden bg-gray-100 text-xs p-3 rounded overflow-auto max-h-96 whitespace-pre-wrap"
         data-loaded="false">
<span class="loading">Loading...</span>
</pre>
</li>
