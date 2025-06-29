@extends('layouts.base')

@section('title', 'Profile')

@section('content')
    <section class="px-4 sm:px-6 lg:px-8 py-6 space-y-4">
        <x-page.header title="Profile" />

        <div class="space-y-2 text-gray-700 dark:text-gray-300">
            @if($pubkey)
                <p><strong>Pubkey:</strong> {{ $pubkey }}</p>
            @else
                <p>{{ __('Not logged in via Nostr.') }}</p>
            @endif
            <p><strong>Total chats:</strong> {{ number_format($totalChats) }}</p>
            <p><strong>Total messages:</strong> {{ number_format($totalMessages) }}</p>
            <p><strong>Total zaps:</strong> {{ number_format($totalZaps) }}</p>
        </div>
    </section>
@endsection
