@props(['chat', 'owned'])

@php
    use Illuminate\Support\Str;
    /** @var \App\Models\Chat $chat */
    $assistantMsg = $chat->getFirstAssistantMessage();
    $entryId = 'entry-' . $assistantMsg->id;
@endphp

<li class="chat-item">
    <div class="chat-header font-medium mb-1 flex justify-between items-start gap-2">
        <div class="cursor-pointer w-full"
             onclick="window.location.href='{{ route('chat.show', $chat) }}'">
            <strong>{{ ucfirst($chat->type) }}:</strong>
            <span class="truncate overflow-hidden text-ellipsis block link">
                {{ $chat->input }}
            </span>
        </div>
        @if($owned)
            <i data-lucide="badge-check"
               class="text-orange-500 w-6 h-6"
               title="This chat belongs to you"></i>
        @endif
    </div>
    <div class="chat-body relative collapsed" data-target="{{ $entryId }}">
        @if($assistantMsg)
            <div id="{{ $entryId }}"
                 class="prose markdown-content overflow-hidden max-h-[8.5rem] transition-all duration-300">
                {!! Str::markdown($assistantMsg->content) !!}
            </div>
        @endif
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
