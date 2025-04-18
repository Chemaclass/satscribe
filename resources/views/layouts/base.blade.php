<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Satscribe – AI Satosi Describer')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
    <script src="https://kit.fontawesome.com/cfd779d106.js" crossorigin="anonymous"></script>
    <script src="//unpkg.com/alpinejs" defer></script>
    @stack('head')
</head>
<body class="min-h-screen flex flex-col">

<header class="site-header">
    <a href="{{ url('/') }}" class="brand">Satscribe</a>
    <nav class="nav-links">
        <a href="{{ url('/history') }}">
            <i class="fas fa-scroll"></i> <span class="link-text">History</span>
        </a>
        <a href="https://github.com/Chemaclass/satscribe" target="_blank" title="View on GitHub">
            <i class="fab fa-github"></i> <span class="link-text">Code</span>
        </a>
    </nav>
</header>

<main class="flex-grow">
    @yield('content')
</main>

<footer>
    &copy;{{ date('Y') }}<a href="https://chemaclass.com/" target="_blank">Chema</a>
    —<a target="_blank" href="https://getalby.com/p/chemaclass" style="color: currentColor">
        Leave a tip <i class="fa-solid fa-bitcoin-sign"></i>
    </a>
</footer>
@stack('scripts')
</body>
</html>
