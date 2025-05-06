<section id="description-body-results" class="description-body w-full max-w-3xl mx-auto space-y-6">
    @if($result->force_refresh)
        <div class="alert-warning" role="alert">
            ⚠️ This transaction is unconfirmed. You might want to refresh later to get the latest status.
        </div>
    @endif

    <div class="section rounded p-4 shadow-sm">
        <h2 class="text-2xl font-bold mb-2 flex items-center">
            <i data-lucide="bot" class="w-6 h-6"></i> AI Summary
        </h2>
        <div class="prose dark:prose-invert">
            {!! Str::markdown($result->ai_response) !!}
        </div>

        <x-description-result.follow-up-suggestions :input="$result->input" />

        <x-description-result.raw-data-toggle-button
            :id="$result->id"
            :input="$result->input"
            :question="$result->question"
            :createdAt="$result->created_at"
        />

    </div>
</section>
