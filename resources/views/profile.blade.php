@extends('layouts.base')

@section('title', 'Profile')

@section('content')
    <section class="px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <x-page.header title="Profile" />

        <div class="space-y-4 text-gray-700 dark:text-gray-300">
            @if($pubkey)
                <div id="nostr-profile-meta" class="profile-card overflow-hidden">
                    <div id="profile-banner" class="h-32 bg-cover bg-center skeleton"></div>
                    <div class="p-4 flex items-start gap-4">
                        <img id="profile-avatar" class="w-24 h-24 rounded-full -mt-16 border-4 skeleton" alt="avatar" />
                        <div class="p-4 flex items-start gap-4 w-full relative">
                            <div class="flex-1">
                                <div class="flex justify-between items-start">
                                    <p id="profile-name" class="text-xl font-semibold skeleton h-6 w-32"></p>
                                    <button type="button" id="profile-refresh" class="px-3 py-1 rounded border text-sm link">
                                        {{ __('Refresh profile') }}
                                    </button>
                                </div>
                                <p class="text-sm text-gray-500 skeleton h-4 w-24" id="profile-username"></p>
                                <p class="text-sm"><a id="profile-url" href="#" class=" hover:underline hidden" target="_blank"></a></p>
                                <p id="profile-nip05" class="text-sm hidden"></p>
                                <p id="profile-lud16" class="text-sm hidden"></p>
                                <p id="profile-about" class="text-sm hidden"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="profile-stat">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pubkey</p>
                        <p class="break-all text-sm mt-1">{{ $pubkey }}</p>
                    </div>
                    <div class="profile-stat">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total chats</p>
                        <p class="text-xl font-semibold mt-1">{{ number_format($totalChats) }}</p>
                    </div>
                    <div class="profile-stat">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total messages</p>
                        <p class="text-xl font-semibold mt-1">{{ number_format($totalMessages) }}</p>
                    </div>
                    <div class="profile-stat sm:col-span-3">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total zaps</p>
                        <p class="text-xl font-semibold mt-1">{{ number_format($totalZaps) }}</p>
                    </div>
                </div>
            @else
                <div class="p-4 rounded-lg profile-stat">
                    <p>{{ __('Not logged in via Nostr.') }} What is nostr? Check this out: <a href="https://nostr.com/">https://nostr.com/</a></p>
                </div>
            @endif
        </div>
    </section>
@endsection
