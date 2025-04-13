<!DOCTYPE html>
<html>
<head>
    <title>Bitcoin Describer</title>
</head>
<body>
<h1>Describe a Bitcoin Transaction or Block</h1>

<form method="POST" action="{{ route('describe') }}">
    @csrf
    <input name="input" value="{{ old('input') }}" style="width: 400px;" required>
    <button>Describe</button>
</form>

@error('input') <p style="color:red">{{ $message }}</p> @enderror

@isset($description)
    <hr>
    <h2>AI Description:</h2>
    <p>{{ $description }}</p>

    <hr>
    <h3>Raw Blockchain Data:</h3>
    <pre>{{ json_encode($data, JSON_PRETTY_PRINT) }}</pre>
@endisset
</body>
</html>
