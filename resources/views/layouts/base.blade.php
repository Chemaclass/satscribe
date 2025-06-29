@props([
    'btcPriceUsd' => 0,
    'btcPriceEur' => 0,
    'btcPriceCny' => 0,
    'btcPriceGbp' => 0,
    'hasFaqs' => false,
])
<!DOCTYPE html>
<html lang="en"
      x-data="{ dark: localStorage.getItem('theme') === 'dark' }"
      x-init="$watch('dark', val => {
          localStorage.setItem('theme', val ? 'dark' : 'light');
          document.documentElement.classList.toggle('dark', val);
      })"
      :class="{ 'dark': dark }"
>
<head>
    <meta charset="UTF-8">
    <title>@yield('title', __('Unlock the Story Behind Every Satoshi') . ' – Satscribe')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="nostr-pubkey" content="{{ nostr_pubkey() }}">
    <meta name="nostr-login-url" content="{{ route('nostr.login') }}">
    <meta name="nostr-logout-url" content="{{ route('nostr.logout') }}">
    <meta name="nostr-challenge-url" content="{{ route('nostr.challenge') }}">
    <script>
    if (localStorage.getItem('theme') === 'dark') {
        document.documentElement.classList.add('dark');
    }
    window.i18n = {
        showMore: "{{ __('Show more') }}",
        showLess: "{{ __('Show less') }}",
        showRawData: "{{ __('Show raw data') }}",
        hideRawData: "{{ __('Hide raw data') }}",
        hide: "{{ __('Hide') }}",
        raw: "{{ __('Raw') }}"
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

    <main class="flex-grow">
        @yield('content')
    </main>

    <x-layout.footer />
    <x-layout.scroll-to-top />
    @stack('scripts')
</body>
</html>
