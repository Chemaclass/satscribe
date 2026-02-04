@props([
    'btcPriceUsd' => 0,
    'btcPriceEur' => 0,
    'btcPriceCny' => 0,
    'btcPriceGbp' => 0,
    'hasFaqs' => false,
])
@php
    $defaultTitle = __('Unlock the Story Behind Every Satoshi') . ' – Satscribe';
    $defaultDescription = __('Satscribe transforms Bitcoin blocks and transactions into human-readable AI conversations. Explore the blockchain with natural language explanations.');
    $pageTitle = View::yieldContent('title', $defaultTitle);
    $pageDescription = View::yieldContent('description', $defaultDescription);
    $pageImage = View::yieldContent('og_image', asset('images/og-image.png'));
    $pageUrl = url()->current();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $pageTitle }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="keywords" content="Bitcoin, blockchain, transaction, block explorer, AI, Satoshi, cryptocurrency, BTC, UTXO, Nostr">
    <meta name="author" content="Satscribe">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ $pageUrl }}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $pageUrl }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:image" content="{{ $pageImage }}">
    <meta property="og:site_name" content="Satscribe">
    <meta property="og:locale" content="{{ app()->getLocale() }}">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ $pageUrl }}">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $pageImage }}">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <!-- App-specific meta -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="nostr-pubkey" content="{{ nostr_pubkey() }}">
    <meta name="nostr-login-url" content="{{ route('nostr.login') }}">
    <meta name="nostr-logout-url" content="{{ route('nostr.logout') }}">
    <meta name="nostr-challenge-url" content="{{ route('nostr.challenge') }}">

    <!-- Structured Data -->
    @php
    $schemaData = [
        '@context' => 'https://schema.org',
        '@type' => 'WebApplication',
        'name' => 'Satscribe',
        'description' => $defaultDescription,
        'url' => url('/'),
        'applicationCategory' => 'FinanceApplication',
        'operatingSystem' => 'Web Browser',
        'offers' => [
            '@type' => 'Offer',
            'price' => '0',
            'priceCurrency' => 'USD',
        ],
    ];
    @endphp
    <script type="application/ld+json">{!! json_encode($schemaData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}</script>
    @stack('structured_data')
    <script>
    window.i18n = {
        showMore: "{{ __('Show more') }}",
        showLess: "{{ __('Show less') }}",
        showRawData: "{{ __('Show raw data') }}",
        hideRawData: "{{ __('Hide raw data') }}",
        hide: "{{ __('Hide') }}",
        raw: "{{ __('Raw') }}",
        loading: "{{ __('Loading...') }}"
    };
    </script>
    <x-preload-assets />
    @if(isset($cronitorClientKey))
    <script async src="https://rum.cronitor.io/script.js"></script>
    <script>
        window.cronitor = window.cronitor || function() { (window.cronitor.q = window.cronitor.q || []).push(arguments); };
        cronitor('config', { clientKey: '{{$cronitorClientKey}}' });
    </script>
    @endif
    @stack('head')
</head>
<body class="min-h-screen flex flex-col transition-colors duration-300">
    <x-layout.header
        :btc-price-usd="$btcPriceUsd"
        :btc-price-eur="$btcPriceEur"
        :btc-price-cny="$btcPriceCny"
        :btc-price-gbp="$btcPriceGbp"
        :has-faqs="$hasFaqs"
    />

    <div class="flex flex-col flex-grow min-h-0">
        <main class="body-container flex-grow flex flex-col min-h-0">
            @yield('content')
        </main>
    </div>

    <x-layout.footer />
    <x-layout.scroll-to-top />
    <x-nostr-login-modal />
    @stack('scripts')
</body>
</html>
