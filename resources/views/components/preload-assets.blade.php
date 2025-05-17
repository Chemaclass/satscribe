@php
    $css = Vite::asset('resources/css/app.css');
    $js = Vite::asset('resources/js/app.js');
@endphp

<!-- CSS Preload + Fallback -->
<link rel="preload" href="{{ $css }}" as="style" crossorigin="anonymous">
<link rel="stylesheet" href="{{ $css }}" crossorigin="anonymous">

<!-- JS Preload + Module -->
<link rel="modulepreload" href="{{ $js }}" crossorigin="anonymous">
<script type="module" src="{{ $js }}" crossorigin="anonymous"></script>
