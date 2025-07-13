@extends('layouts.base')

@section('title', 'Profile')

@section('content')
    <section id="profile" class="sm:px-6 lg:px-8 px-4 py-6">
        @if($pubkey)
            <div id="nostr-profile-meta">
            {{-- Banner & Avatar --}}
            <div class="relative">
                <div id="profile-banner" class="h-48 sm:h-56 md:h-64 bg-cover bg-center rounded-lg skeleton"></div>
                <div class="absolute -bottom-4 left-4">
                    <img id="profile-avatar" src="" alt="avatar" class="w-28 h-28 sm:w-32 sm:h-32 rounded-full border-4 border-white shadow-lg object-cover skeleton" />
                </div>
            </div>

            {{-- Main Card --}}
            <div class="bg-white mt-2 rounded-lg p-6 shadow space-y-6">
                {{-- Header --}}
                <div class="flex justify-between items-center">
                    <h2 id="profile-displayname" class="text-xl font-semibold text-gray-900">Profile</h2>
                    <button type="button" id="profile-refresh" class="px-3 py-1 rounded border text-sm link">
                        {{ __('Refresh profile') }}
                    </button>
                </div>

                {{-- Name --}}
                <div class="space-y-2">
                    <div id="profile-name" class="h-6 w-36 rounded skeleton"></div>
                </div>

                {{-- Bio Section --}}
                <p id="profile-about" class="hidden text-gray-600 whitespace-pre-line space-y-2">
                    <span class="block h-4 w-full rounded skeleton"></span>
                    <span class="block h-4 w-4/6 rounded skeleton"></span>
                </p>

                {{-- Metadata --}}
{{--                <div class="space-y-1 text-sm">--}}
{{--                    <a id="profile-url" href="#" class="hidden link break-all"></a>--}}
{{--                    <div id="profile-nip05" class="hidden break-all"></div>--}}
{{--                    <div id="profile-lud16" class="hidden break-all"></div>--}}
{{--                </div>--}}

                {{-- Social Stats --}}
{{--                <div class="flex gap-6 text-sm pt-2 border-t border-gray-200">--}}
{{--                    <div class="h-4 w-24 rounded skeleton"></div>--}}
{{--                    <div class="h-4 w-24 rounded skeleton"></div>--}}
{{--                </div>--}}

                {{-- App Stats --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 border-gray-200">
                    <div class="space-y-1">
                        <div class="h-4 w-24">Chats</div>
                        <div class="h-6 w-12 mt-2">{{ number_format($totalChats) }}</div>
                    </div>
                    <div class="space-y-1">
                        <div class="h-4 w-24">Messages</div>
                        <div class="h-6 w-12 mt-2">{{ number_format($totalMessages) }}</div>
                    </div>
                    <div class="space-y-1">
                        <div class="h-4 w-24">Zaps</div>
                        <div class="h-6 w-12 mt-2">{{ number_format($totalZaps) }}</div>
                    </div>
                </div>

                {{-- Pubkey --}}
                <div class="mt-6">
                    <h3 class="text-sm font-medium text-gray-600 uppercase tracking-wide">Pubkey</h3>
                    <div class="mt-1 px-3 py-2 bg-gray-100 rounded-md text-sm font-mono text-gray-800 break-all border border-gray-200">
                        {{ $pubkey }}
                    </div>
                </div>

                {{-- npub key --}}
                <div class="mt-6">
                    <h3 class="text-sm font-medium text-gray-600 uppercase tracking-wide">npub</h3>
                    <div id="profile-npub" class="mt-1 px-3 py-2 bg-gray-100 rounded-md text-sm font-mono text-gray-800 break-all border border-gray-200">
                        Loading...
                    </div>
                </div>

                {{-- Secret Key (local only) --}}
                <div class="mt-6 hidden" id="secret-key-container">
                    <h3 class="text-sm font-medium text-gray-600 uppercase tracking-wide">Secret key</h3>
                    <div class="mt-1 flex items-center gap-2">
                        <input type="password"
                               readonly
                               class="flex-1 px-3 py-2 bg-yellow-50 rounded-md text-sm font-mono text-gray-800 break-all border border-yellow-200"
                               id="secret-key-value" />
                        <button type="button"
                                id="secret-key-copy"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-2 py-1 rounded">
                            Copy
                        </button>
                        <button type="button"
                                id="secret-key-toggle"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-2 py-1 rounded">
                            Show
                        </button>
                    </div>
                    <p class="text-xs text-red-600 mt-2 font-semibold">
                        <strong>IMPORTANT</strong>: save this key in your password manager and delete it from local storage afterwards.
                        <strong>Satscribe will not store it for you.</strong>
                    </p>
                    <button type="button" id="secret-key-delete" class="mt-2 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
                        Delete from local storage
                    </button>
                </div>
            </div>
        </div>
        @else
        {{-- Not logged in fallback --}}
        <div class="p-4 rounded-lg profile-stat bg-white shadow">
            <p>
                {{ __('Not logged in via Nostr.') }} What is nostr?
                <a href="/nostr" class="underline text-orange-300">Check this out</a>
            </p>
        </div>
        @endif
    </section>
@endsection
