@props(['chat'])

@php
    /** @var \App\Models\Chat $chat */
    $assistantMsg = $chat->getFirstAssistantMessage();
@endphp

<div class="chat-meta mt-2 flex justify-between items-center text-sm text-gray-500">
    <span>{{ $chat->getLastAssistantMessage()->created_at->diffForHumans() }}</span>
    <div class="flex gap-4 items-center">
        <button type="button"
                class="toggle-history-raw-btn link"
                data-target="raw-{{ $assistantMsg->id }}"
                data-id="{{ $assistantMsg->id }}">
            <span class="full-label hidden sm:inline">{{ __('Show raw data') }}</span>
            <span class="short-label inline sm:hidden">{{ __('Raw') }}</span>
        </button>
    </div>
</div>

<pre id="raw-{{ $assistantMsg->id }}"
     class="hidden bg-gray-100 text-xs p-3 rounded overflow-auto max-h-128 whitespace-pre-wrap mt-2"
     data-loaded="false">
    <span class="loading">{{ __('Loading...') }}</span>
</pre>
