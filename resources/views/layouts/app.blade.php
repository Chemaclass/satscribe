<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Satscribe – Bitcoin Describer')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            font-family: system-ui, sans-serif;
            background: #f9fafb;
            color: #111827;
            padding: 2rem;
            max-width: 900px;
            margin: auto;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2563eb;
            text-decoration: none;
        }

        .nav-links a {
            margin-left: 1rem;
            font-size: 0.95rem;
            text-decoration: none;
            color: #374151;
        }

        .nav-links a:hover {
            color: #1d4ed8;
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        form {
            margin-bottom: 2rem;
        }

        nav[role="navigation"] svg {
            width: 1rem;
            height: 1rem;
            margin: 0 0.2rem;
            vertical-align: middle;
            fill: currentColor;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            max-width: 500px;
        }

        .form-input {
            padding: 0.5rem 0.75rem;
            font-size: 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
        }

        .form-checkbox {
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-button {
            padding: 0.5rem 1.25rem;
            font-size: 1rem;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
        }

        .form-button:hover {
            background: #1d4ed8;
        }

        .info-message {
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }

        .info-fresh {
            color: #047857;
        }

        .info-cached {
            color: #6b7280;
        }

        .error {
            color: #dc2626;
        }

        pre {
            background: #f3f4f6;
            padding: 1rem;
            border-radius: 0.375rem;
            overflow-x: auto;
            font-size: 0.9rem;
        }

        .section {
            margin-top: 2rem;
        }

        .section h2 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }

        .box {
            background: #f3f4f6;
            padding: 1rem;
            border-radius: 0.375rem;
        }
    </style>

    @stack('head')
</head>
<body>
<header>
    <a href="{{ url('/') }}" class="brand">Satscribe</a>
    <nav class="nav-links">
        <a href="{{ route('describe') }}">Describe</a>
        <a href="{{ url('/history') }}">History</a>
    </nav>
</header>

@yield('content')
</body>
</html>
