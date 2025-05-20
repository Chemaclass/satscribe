@props(['chat', 'owned'])

@php
    use Illuminate\Support\Str;

    /** @var \App\Models\Chat $chat */
    $userMsg = $chat->getFirstUserMessage();
    $assistantMsg = $chat->getFirstAssistantMessage();
    $entryId = 'entry-' . $assistantMsg->id;
@endphp

<li class="chat-item">
    <div class="cursor-pointer w-full hover:bg-gray-50 rounded-lg p-3 transition-colors duration-300"
         onclick="window.location.href='{{ route('chat.show', $chat) }}'"
    >
        <div class="chat-header font-medium mb-1 flex justify-between items-start gap-2">
            <div>
                <strong>{{ ucfirst($chat->type) }}:</strong>
                <span class="truncate overflow-hidden text-ellipsis block link text-left">
                    {{ $chat->input }}
                </span>
            </div>
            @if($owned)
                <span class="relative group">
                    <i data-lucide="badge-check"
                       class="text-orange-500 w-6 h-6 cursor-pointer"
                       aria-label="This chat belongs to you"
                       aria-hidden="false"
                       role="img"></i>

                    <span class="tooltip-content absolute z-10 right-full top-1/2 -translate-y-1/2 mr-2
                                 text-xs font-medium px-2 py-1 rounded whitespace-nowrap
                                 opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none">
                        This chat belongs to you
                    </span>
                </span>
            @endif
        </div>
    </div>
    <div class="chat-body relative collapsed" data-target="{{ $entryId }}">
        <div class="user-message mb-2 text-right">
            <div class="flex items-center gap-1 justify-end">
                <div class="inline-block rounded px-3 py-2">
                    {{ $userMsg->content }}
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
                {!! Str::markdown($assistantMsg->content) !!}
            </div>
        </div>
    </div>
    <div class="chat-meta mt-2 flex justify-between items-center text-sm text-gray-500">
        <span>{{ $chat->created_at->diffForHumans() }}</span>
        <div class="flex gap-4 items-center">
            <button type="button"
                    class="toggle-chat-btn link"
                    data-target="{{ $entryId }}">
                <span class="full-label hidden sm:inline">Show full response</span>
                <span class="short-label inline sm:hidden">Full</span>
            </button>
            <button type="button"
                    class="toggle-history-raw-btn link"
                    data-target="raw-{{ $assistantMsg->id }}"
                    data-id="{{ $assistantMsg->id }}">
                <span class="full-label hidden sm:inline">Show raw data</span>
                <span class="short-label inline sm:hidden">Raw</span>
            </button>
        </div>
    </div>
    <pre id="raw-{{ $assistantMsg->id }}"
         class="hidden bg-gray-100 text-xs p-3 rounded overflow-auto max-h-96 whitespace-pre-wrap"
         data-loaded="false">
<span class="loading">Loading...</span>
</pre>
</li>
