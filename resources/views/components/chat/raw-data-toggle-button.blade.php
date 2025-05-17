@props(['input', 'question', 'createdAt', 'id'])

<div class="chat-meta mt-4 flex justify-between items-center text-sm text-gray-500">
    <span>{{ $createdAt->diffForHumans() }}</span>

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
