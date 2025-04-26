
<section id="description-body-results" class="description-body mt-6 w-full max-w-3xl mx-auto space-y-6 ">
    @if($result->force_refresh)
        <div class="alert-warning" role="alert">
            ⚠️ This transaction is unconfirmed. You might want to refresh later to get the latest status.
        </div>
    @endif

    <div class="section rounded p-4 shadow-sm">
        <h2 class="text-lg font-semibold mb-2 flex items-center gap-2">
            <i data-lucide="bot" class="w-6 h-6"></i>AI Summary
        </h2>
        <div class=" text-gray-800 leading-relaxed">
            {!! Str::markdown($result->ai_response) !!}
        </div>
    </div>

    <div class="section rounded p-4 shadow-sm">
        <h2 class="text-lg font-semibold mb-2 flex items-center gap-2">
            <i data-lucide="box" class="w-6 h-6"></i> Raw Blockchain Data
        </h2>
        <div class="code-block-collapsible">
            <pre id="blockchain-data" class="code-block collapsed overflow-x-auto text-sm sm:text-base">
{{ json_encode($result->raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}
</pre>
        </div>
    </div>
</section>
