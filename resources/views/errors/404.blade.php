@extends('layouts.base')

@section('title', __('Not Found'))

@section('content')
    <section class="px-4 py-6">
        <x-page.header title="{{ __('Page Not Found') }}" containerClass="max-w-xl">
            <p class="subtitle text-base sm:text-lg text-gray-700">
                {{ __('The page you are looking for could not be found.') }}
            </p>
        </x-page.header>
        <p class="mt-4">
            <a href="{{ url('/') }}" class="link">{{ __('Go to home') }}</a>
        </p>
    </section>
@endsection
