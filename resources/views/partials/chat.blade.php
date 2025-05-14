@props([
    'chat',
    'question' => '',
    'suggestions' => [],
])

<section id="chat-container" class="description-body w-full max-w-3xl mx-auto space-y-6">
    @foreach($chat->messages as $message)
        <x-chat.message
            :message="$message"
            :suggestions="$suggestions"
            :loop="$loop"
        />
    @endforeach
</section>
