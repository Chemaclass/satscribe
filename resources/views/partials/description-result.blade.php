<section id="description-body-results" class="description-body w-full max-w-3xl mx-auto space-y-6">
    @if($result->force_refresh)
        <div class="alert-warning" role="alert">
            ⚠️ This transaction is unconfirmed. You might want to refresh later to get the latest status.
        </div>
    @endif

    {{-- AI Summary Section --}}
    <div class="section rounded p-4 shadow-sm">
        <h2 class="text-2xl font-bold mb-2 flex items-center">
            <i data-lucide="bot" class="w-6 h-6"></i> AI Summary
        </h2>
        <div class="prose dark:prose-invert">
            {!! Str::markdown($result->ai_response) !!}
        </div>

        {{-- Raw Data Toggle Button --}}
        <div class="description-meta mt-4 flex justify-between items-center text-sm text-gray-500">
            <span>{{ $result->created_at->diffForHumans() }}</span>

            <button type="button"
                    class="toggle-history-raw-btn link"
                    data-target="raw-{{ $result->id }}"
                    data-id="{{ $result->id }}">
                Show raw data
            </button>
        </div>
        <pre id="raw-{{ $result->id }}"
             class="hidden bg-gray-100 text-xs p-3 rounded overflow-auto max-h-128 whitespace-pre-wrap mt-2"
             data-loaded="false">
    <span class="loading">Loading...</span>
</pre>
    </div>
</section>
