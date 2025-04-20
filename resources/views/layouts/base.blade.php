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
    <title>@yield('title', 'Satscribe – AI Satosi Describer')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
    <script src="https://kit.fontawesome.com/cfd779d106.js" crossorigin="anonymous"></script>
    <script src="//unpkg.com/alpinejs" defer></script>
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
<header class="site-header flex justify-between items-center px-4 py-3 border-b border-gray-200 dark:border-gray-700">
    <a href="{{ url('/') }}" class="brand text-xl font-bold">Satscribe</a>

    <nav class="nav-links flex items-center space-x-4">
        <a href="{{ route('faq') }}" class="nav-link">
            <i class="fas fa-lightbulb"></i> <span class="link-text">FAQ</span>
        </a>
        <a href="{{ url('/history') }}"  class="nav-link">
            <i class="fas fa-scroll"></i> <span class="link-text">History</span>
        </a>
        <a href="https://github.com/Chemaclass/satscribe" target="_blank" class="nav-link">
            <i class="fab fa-github"></i> <span class="link-text">Code</span>
        </a>

        {{-- 🌗 Dark Mode Toggle --}}
        <nav class="nav-links">
            <!-- other nav items -->
            <button class="nav-link flex items-center gap-1" @click="dark = !dark">
                <i :class="dark ? 'fas fa-sun' : 'fas fa-moon'"></i>
                <span class="link-text" x-text="dark ? 'Light' : 'Dark'"></span>
            </button>
        </nav>
    </nav>
</header>

<main class="flex-grow">
    @yield('content')
</main>

<footer class="text-center py-4 border-t border-gray-200 dark:border-gray-700">
    &copy;{{ date('Y') }} <a href="https://chemaclass.com/" target="_blank">Chema</a>
    — <a target="_blank" href="https://getalby.com/p/chemaclass" style="color: currentColor">
        Leave a tip <i class="fa-solid fa-bitcoin-sign"></i>
    </a>
</footer>

@stack('scripts')
</body>
</html>
