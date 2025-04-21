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
    <title>@yield('title', 'Satscribe – AI Satoshi Describer')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
    if (localStorage.getItem('theme') === 'dark') {
        document.documentElement.classList.add('dark');
    }
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
<body class="min-h-screen flex flex-col bg-white text-gray-900 dark:bg-gray-900 dark:text-white transition-colors duration-300">
<header class="flex justify-between items-center px-4 py-3 border-gray-200 dark:border-gray-700">
    <a href="{{ url('/') }}" class="brand text-xl font-bold">Satscribe</a>

    <nav class="nav-links flex items-center">
        <a href="{{ route('faq') }}" class="nav-link flex items-center gap-1">
            <svg data-lucide="lightbulb" class="w-5 h-5"></svg>
            <span class="link-text">FAQ</span>
        </a>
        <a href="{{ url('/history') }}" class="nav-link flex items-center gap-1">
            <svg data-lucide="scroll" class="w-5 h-5"></svg>
            <span class="link-text">History</span>
        </a>
        <button class="nav-link flex items-center gap-1" @click="dark = !dark">
            <svg :data-lucide="dark ? 'sun' : 'moon'" class="w-5 h-5"></svg>
            <span class="link-text" x-text="dark ? 'Light' : 'Dark'"></span>
        </button>

        @if(!empty($btcPriceUsd))
            <a href="https://coinmarketcap.com/currencies/bitcoin/"
               target="_blank"
               rel="noopener"
               class="nav-link hidden sm:inline-flex items-center gap-1 px-3 py-1 text-sm whitespace-nowrap"
            >
                <span>${{ number_format($btcPriceUsd, 0) }}</span>
            </a>
        @endif
    </nav>
</header>

<main class="flex-grow">
    @yield('content')
</main>

<footer class="text-center py-4 border-t border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400">
    <div class="flex justify-center items-center gap-2 flex-wrap">
        <span>&copy;{{ date('Y') }} Built by <a href="https://chemaclass.com/" target="_blank" class="hover:underline">Chema</a></span>
        <span class="hidden sm:inline">•</span>
        <a href="https://getalby.com/p/chemaclass" target="_blank" class="flex items-center gap-1 hover:underline">
            <i data-lucide="bitcoin" class="w-4 h-4 text-orange-500 dark:text-[--btc-orange-dark]"></i>
            Leave a tip
        </a>
        <span class="hidden sm:inline">•</span>
        <a href="https://github.com/Chemaclass/satscribe" target="_blank" class="flex items-center gap-1 hover:underline">
            <svg data-lucide="github" class="w-4 h-4"></svg>
            GitHub
        </a>
    </div>
</footer>

@stack('scripts')
</body>
</html>
