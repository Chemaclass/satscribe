<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Satscribe – Bitcoin Describer')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite('resources/css/app.css')
    <script src="https://kit.fontawesome.com/cfd779d106.js" crossorigin="anonymous"></script>
    @stack('head')
</head>
<body>
<header>
    <a href="{{ url('/') }}" class="brand">Satscribe</a>
    <nav class="nav-links">
        <a href="{{ route('describe') }}">
            <i class="fas fa-vial"></i> Describe
        </a>
        <a href="{{ url('/history') }}">
            <i class="fas fa-scroll"></i> History
        </a>
        <a href="https://github.com/Chemaclass/satscribe" target="_blank" title="View on GitHub">
            <i class="fab fa-github"></i> Code
        </a>
    </nav>
</header>

@yield('content')

<footer>
    &copy;{{ date('Y') }}<a href="https://chemaclass.com/" target="_blank">Chema</a>
    —<a target="_blank" href="https://getalby.com/p/chemaclass" style="color: currentColor">
        Leave a tip <i class="fa-solid fa-bitcoin-sign"></i>
    </a>
</footer>
</body>
</html>
