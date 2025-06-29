@extends('layouts.base')

@section('title', 'Profile')

@section('content')
    <section class="px-4 sm:px-6 lg:px-8 py-6 space-y-4">
        <x-page.header title="Profile" />

        <div class="space-y-2 text-gray-700 dark:text-gray-300">
            @if($pubkey)
                <div id="nostr-profile-meta" class="flex items-start gap-4 mb-4">
                    <img id="profile-avatar" class="w-32 rounded-full hidden" alt="avatar" />
                    <div class="space-y-1">
                        <p id="profile-name" class="text-xl font-semibold"></p>
                        <p class="text-sm text-gray-500" id="profile-username"></p>
                        <p class="text-sm"><a id="profile-url" href="#" class="text-blue-600 hover:underline hidden" target="_blank"></a></p>
                        <p id="profile-nip05" class="text-sm hidden"></p>
                        <p id="profile-lud16" class="text-sm hidden"></p>
                        <p id="profile-about" class="text-sm hidden"></p>
                    </div>
                </div>
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
