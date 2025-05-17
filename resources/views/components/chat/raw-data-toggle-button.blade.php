@props(['input', 'question', 'createdAt', 'id'])

<div class="chat-meta mt-4 flex justify-between items-center text-sm text-gray-500">
    @if ($createdAt->diffInMinutes(now()) > 1)
        <button
            type="button"
            class="text-sm text-gray-500 hover:text-orange-400 cursor-pointer"
            onclick="resubmitWithRefresh('{{ $input }}', '{{ $question }}')"
            title="Refresh this result with latest data"
        >
            {{ $createdAt->diffForHumans() }}
        </button>
    @else
        <span>{{ $createdAt->diffForHumans() }}</span>
    @endif

    <button type="button"
            class="toggle-history-raw-btn link"
            data-target="raw-{{ $id }}"
            data-id="{{ $id }}">
        Show raw data
    </button>
</div>

<pre id="raw-{{ $id }}"
     class="hidden bg-gray-100 text-xs p-3 rounded overflow-auto max-h-128 whitespace-pre-wrap mt-2"
     data-loaded="false">
    <span class="loading">Loading...</span>
</pre>
