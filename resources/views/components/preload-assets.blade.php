@php
    $css = Vite::asset('resources/css/app.css');
    $js = Vite::asset('resources/js/app.js');
@endphp

<link rel="preload" as="style" href="{{ $css }}" crossorigin>
<link rel="stylesheet" href="{{ $css }}">

<link rel="modulepreload" href="{{ $js }}" crossorigin>
<script type="module" src="{{ $js }}" crossorigin></script>
