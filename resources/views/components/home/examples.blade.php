@php
    // Famous, permanently-confirmed inputs — a first-time visitor gets a real
    // answer in one click instead of guessing what to paste into the field.
    $examples = [
        [
            'icon' => 'box',
            'title' => __('home.examples.genesis.title'),
            'subtitle' => __('home.examples.genesis.subtitle'),
            'search' => '0',
            'question' => __('home.examples.genesis.question'),
        ],
        [
            'icon' => 'arrow-right-left',
            'title' => __('home.examples.pizza.title'),
            'subtitle' => __('home.examples.pizza.subtitle'),
            'search' => 'a1075db55d416d3ca199f55b6084e2115b9345e16c5cf302fc80e9d5fbf5d48d',
            'question' => __('home.examples.pizza.question'),
        ],
        [
            'icon' => 'box',
            'title' => __('home.examples.halving.title'),
            'subtitle' => __('home.examples.halving.subtitle'),
            'search' => '210000',
            'question' => __('home.examples.halving.question'),
        ],
    ];
@endphp

<div class="home-examples max-w-5xl" x-show="!hasSubmitted" x-cloak>
    <p class="text-sm font-medium text-gray-700 mb-2">{{ __('home.examples.heading') }}</p>

    <div class="grid gap-2 sm:grid-cols-3">
        @foreach ($examples as $example)
            <button
                type="button"
                class="example-card"
                :disabled="isSubmitting"
                @click="runExample(@js($example['search']), @js($example['question']))"
            >
                <span class="example-card__icon">
                    <i data-lucide="{{ $example['icon'] }}" class="w-4 h-4"></i>
                </span>
                <span class="min-w-0">
                    <span class="example-card__title">{{ $example['title'] }}</span>
                    <span class="example-card__subtitle">{{ $example['subtitle'] }}</span>
                </span>
            </button>
        @endforeach
    </div>
</div>
