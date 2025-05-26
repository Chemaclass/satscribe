<div class="mt-10 flex flex-col items-center text-center">
    <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 48 48">
        <circle cx="24" cy="24" r="22" stroke-width="2" />
        <path d="M12 29c1.5-4 7-6 12-6s10.5 2 12 6" stroke-width="2" stroke-linecap="round"/>
        <circle cx="17" cy="21" r="2" fill="currentColor"/>
        <circle cx="31" cy="21" r="2" fill="currentColor"/>
    </svg>
    <h2 class="text-xl font-semibold mb-2">{{ __('No history yet!') }}</h2>
    <p class="mb-4">
        {{ __('Your history is empty — start your first Bitcoin analysis and be part of the collective knowledge.') }}
    </p>
    <a href="{{ url('/') }}" class="form-button">
        {{ __('Start Satscribing') }}
    </a>
</div>
