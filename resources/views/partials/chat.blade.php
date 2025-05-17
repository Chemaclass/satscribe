@props([
    'chat',
    'question' => '',
    'suggestions' => [],
])

<section id="chat-container" class="chat-body w-full p-2">
    @foreach($chat->messages as $message)
        <x-chat.message
            :message="$message"
            :suggestions="$suggestions"
            :loop="$loop"
        />
    @endforeach
</section>
