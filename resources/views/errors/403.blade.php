@extends('layouts.base')

@section('title', __('Forbidden'))

@push('head')
    <meta name="redirect-after-login" content="{{ url('/') }}">
@endpush

@section('content')
    <section class="px-4 py-6">
        <x-page.header title="{{ __('Access Denied') }}" containerClass="max-w-xl">
            <p class="subtitle text-base sm:text-lg text-gray-700">
                {{ $exception->getMessage() ?: __('You are not allowed to access this page.') }}
            </p>
        </x-page.header>
        <p class="mt-4">
            <a href="{{ url('/') }}" class="link">{{ __('Go to home') }}</a>
        </p>
    </section>
@endsection
