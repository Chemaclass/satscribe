@extends('layouts.base')

@section('title', 'Too Many Requests')

@section('content')
    <div class="section">
        <h1 class="text-xl font-semibold">⏳ Too Many Requests</h1>
        <p class="mt-4 text-gray-700">
            You've reached the daily limit of requests for generating Bitcoin descriptions.
            Please try again tomorrow.
        </p>
    </div>
@endsection
