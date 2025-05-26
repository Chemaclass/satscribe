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
    <title>@yield('title', 'Satscribe – Satoshi Describer')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
    if (localStorage.getItem('theme') === 'dark') {
        document.documentElement.classList.add('dark');
    }
    window.i18n = {
        showRaw: @json(__('Show raw data')),
        hideRaw: @json(__('Hide raw data')),
        raw: @json(__('Raw')),
        hide: @json(__('Hide'))
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
    />

    <main class="flex-grow">
        @yield('content')
    </main>

    <x-layout.footer />
    <x-layout.scroll-to-top />
    @stack('scripts')
</body>
</html>
