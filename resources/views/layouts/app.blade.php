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

        h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        form {
            margin-bottom: 2rem;
        }

        input[type="text"] {
            padding: 0.5rem;
            font-size: 1rem;
            width: 100%;
            max-width: 500px;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            margin-bottom: 0.5rem;
        }

        button {
            padding: 0.5rem 1rem;
            font-size: 1rem;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        .error {
            color: #dc2626;
            margin-top: 0.5rem;
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
    </style>

    @stack('head')
</head>
<body>
@yield('content')
</body>
</html>
