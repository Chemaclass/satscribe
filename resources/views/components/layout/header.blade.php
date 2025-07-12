@props([
    'btcPriceUsd' => null,
    'btcPriceEur' => null,
    'btcPriceCny' => null,
    'btcPriceGbp' => null,
    'hasFaqs' => false,
])

<header class="flex justify-between select-none items-center px-4 py-3 border-gray-200">
    <a href="{{ url('/') }}" class="brand text-xl font-bold">Satscribe</a>

    <nav class="nav-links flex items-center" aria-label="Main navigation">

        @if($hasFaqs)
            <a href="{{ route('faq.index') }}"
               data-faq-item
               class="nav-link flex items-center gap-1">
                <svg data-lucide="lightbulb" class="w-5 h-5"></svg>
                <span class="link-text">{{ __('FAQ') }}</span>
            </a>
        @endif

        <a href="{{ route('nostr.index') }}" class="nav-link flex items-center ml-2">
            <svg data-lucide="zap" class="w-5 h-5"></svg>
            <span class="link-text">{{ __('Nostr') }}</span>
        </a>

        @if(nostr_pubkey())
            <div class="relative" x-data="{ open: false }" data-nostr-menu @keydown.escape.window="open = false">
                <button type="button" class="nav-link flex items-center gap-1 ml-5" @click="open = !open">
                    <img id="nostr-avatar" src="" alt="nostr avatar" class="w-5 h-5 rounded-full hidden" />
                    <span id="nostr-logout-label" class="link-text">{{ __('Loading...') }}</span>
                    <svg id="nostr-menu-icon" data-lucide="chevron-down" class="w-5 h-5"></svg>
                </button>

                <div
                    x-show="open"
                    x-cloak
                    @click.away="open = false"
                    class="profile-menu absolute right-0 text-left mt-2 w-36 rounded-md shadow-lg border border-gray-300 z-50 flex flex-col items-start bg-white text-gray-900"
                >
                    <a href="{{ route('history.index') }}"
                           class="flex items-center gap-1 px-4 py-2 nav-link w-full text-left border-b border-gray-200">
                        <svg data-lucide="scroll" class="w-5 h-5"></svg>
                        <span>{{ __('History') }}</span>
                    </a>

                    <a href="{{ route('profile.index') }}" class="flex items-center gap-1 px-4 py-2 nav-link w-full text-left">
                        <svg data-lucide="user" class="w-5 h-5"></svg>
                        <span>{{ __('Profile') }}</span>
                    </a>

                    <form method="POST" action="{{ route('nostr.logout') }}" class="w-full">
                        @csrf
                        <button type="submit"
                                class="w-full text-left px-4 py-2 nav-link flex items-center gap-1 border-b border-gray-200 last:border-b-0">
                            <svg data-lucide="log-out" class="w-5 h-5"></svg>
                            <span class="ml-1">{{ __('Logout') }}</span>
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="relative" x-data="{ open: false }" data-nostr-menu>
                <button type="button" class="nav-link flex items-center gap-1 ml-2" @click="open = !open">
                    <svg data-lucide="user" class="w-5 h-5"></svg>
                    <span class="link-text">{{ __('Login') }}</span>
                    <svg data-lucide="chevron-down" class="w-5 h-5"></svg>
                </button>

                <div
                    x-show="open"
                    x-cloak
                    @click.away="open = false"
                    class="profile-menu absolute right-0 text-left mt-2 w-36 rounded-md shadow-lg border border-gray-300 z-50 flex flex-col items-start bg-white text-gray-900"
                >
                    <button type="button" id="nostr-login-btn"
                            class="w-full text-left px-4 py-2 nav-link flex items-center gap-1 border-b border-gray-200">
                        <svg data-lucide="log-in" class="w-5 h-5"></svg>
                        <span class="ml-1">{{ __('Nostr') }}</span>
                    </button>

                    <a href="{{ route('history.index') }}"
                       class="flex items-center gap-1 px-4 py-2 nav-link w-full text-left border-b border-gray-200">
                        <svg data-lucide="scroll" class="w-5 h-5"></svg>
                        <span>{{ __('History') }}</span>
                    </a>
                </div>
            </div>
        @endif

    </nav>
</header>
