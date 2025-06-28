@props([
    'title',
    'subtitle' => null,
    'titleClass' => 'text-2xl sm:text-3xl font-bold leading-tight',
    'containerClass' => ''
])

<section class="section-header mb-6">
    <div class="flex flex-col {{ $containerClass }}">
        <h1 class="{{ $titleClass }}">{{ $title }}</h1>
        @if($subtitle)
            <p class="mt-3 text-base sm:text-lg subtitle leading-relaxed">
                {!! $subtitle !!}
            </p>
        @endif
        {{ $slot }}
    </div>
</section>
