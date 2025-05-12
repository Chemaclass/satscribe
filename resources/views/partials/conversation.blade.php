@props([
    'conversation',
    'question' => '',
    'suggestions' => [],
])

<section id="description-body-results" class="description-body w-full max-w-3xl mx-auto space-y-6">
    @foreach($conversation->messages as $message)
        <x-conversation.message
            :message="$message"
            :suggestions="$suggestions"
            :loop="$loop"
        />
    @endforeach
</section>
