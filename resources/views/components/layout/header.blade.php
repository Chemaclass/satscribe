@props([
    'btcPriceUsd' => null,
    'btcPriceEur' => null,
    'btcPriceCny' => null,
    'btcPriceGbp' => null,
    'hasFaqs' => true,
])

<header class="flex justify-between select-none items-center px-4 py-3 border-gray-200 dark:border-gray-700">
    <a href="{{ url('/') }}" class="brand text-xl font-bold">Satscribe</a>

    <nav class="nav-links flex items-center" aria-label="Main navigation">
        @if($hasFaqs)
            <a href="{{ route('faq.index') }}" class="nav-link flex items-center gap-1">
                <svg data-lucide="lightbulb" class="w-5 h-5"></svg>
                <span class="link-text">{{ __('FAQ') }}</span>
            </a>
        @endif

        @if(!empty($btcPriceUsd))
            <div
                class="profile-menu nav-link sm:inline-flex items-center gap-1 px-1 py-1 text-sm whitespace-nowrap"
                x-data="{
                currency: StorageClient.getFiatCurrency() || 'usd',
                toggle() {
                    const order = ['usd', 'eur', 'cny', 'gbp'];
                    const idx = order.indexOf(this.currency);
                    this.currency = order[(idx + 1) % order.length];
                    StorageClient.setFiatCurrency(this.currency);
                }
            }"
                x-cloak
            >
                <span class="cursor-pointer" @click="toggle()">
                    <span x-show="currency === 'usd'" x-cloak>${{ number_format($btcPriceUsd, 0) }}</span>
                    <span x-show="currency === 'eur'" x-cloak>&euro;{{ number_format($btcPriceEur, 0) }}</span>
                    <span x-show="currency === 'cny'" x-cloak>&yen;{{ number_format($btcPriceCny, 0) }}</span>
                    <span x-show="currency === 'gbp'" x-cloak>&pound;{{ number_format($btcPriceGbp, 0) }}</span>
                </span>
            </div>
        @endif

        @if(nostr_pubkey())
            <div class="relative" x-data="{ open: false }" data-nostr-menu @keydown.escape.window="open = false">
                <button type="button" class="nav-link flex items-center gap-1" @click="open = !open">
                    <img id="nostr-avatar" src="" alt="nostr avatar" class="w-5 h-5 rounded-full hidden" />
                    <span id="nostr-logout-label" class="link-text">{{ substr(nostr_pubkey(), 0, 5) }}</span>
                    <svg id="nostr-menu-icon" data-lucide="chevron-down" class="w-5 h-5"></svg>
                </button>

                <div
                    x-show="open"
                    x-cloak
                    @click.away="open = false"
                    :class="dark ? 'bg-gray-800 text-white' : 'bg-white text-gray-900'"
                    class="profile-menu absolute right-0 text-left mt-2 w-36 rounded-md shadow-lg border border-gray-300 dark:border-gray-600 z-50 flex flex-col items-start"
                >
                    <a href="{{ route('history.index') }}"
                           class="flex items-center gap-1 px-4 py-2 nav-link w-full text-left border-b border-gray-200 dark:border-gray-700">
                        <svg data-lucide="scroll" class="w-5 h-5"></svg>
                        <span>{{ __('History') }}</span>
                    </a>

                    <a href="{{ route('profile.index') }}" class="flex items-center gap-1 px-4 py-2 nav-link w-full text-left">
                        <svg data-lucide="user" class="w-5 h-5"></svg>
                        <span>{{ __('Profile') }}</span>
                    </a>

                    <button type="button"
                            class="w-full text-left px-4 py-2 nav-link flex items-center gap-1 border-b border-gray-200 dark:border-gray-700"
                            @click="dark = !dark; $nextTick(() => refreshThemeIcon()); open = false;">
                        <svg :data-lucide="dark ? 'sun' : 'moon'" id="theme-icon" class="w-5 h-5"></svg>
                        <span class="ml-1">{{ __('Theme') }}</span>
                    </button>

                    <form method="POST" action="{{ route('nostr.logout') }}" class="w-full">
                        @csrf
                        <button type="submit"
                                class="w-full text-left px-4 py-2 nav-link flex items-center gap-1 border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                            <svg data-lucide="log-out" class="w-5 h-5"></svg>
                            <span class="ml-1">{{ __('Logout') }}</span>
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="relative" x-data="{ open: false }" data-nostr-menu>
                <button type="button" class="nav-link flex items-center gap-1" @click="open = !open">
                    <svg data-lucide="user" class="w-5 h-5"></svg>
                    <span class="link-text">{{ __('Login') }}</span>
                    <svg data-lucide="chevron-down" class="w-5 h-5"></svg>
                </button>

                <div
                    x-show="open"
                    x-cloak
                    @click.away="open = false"
                    :class="dark ? 'bg-gray-800 text-white' : 'bg-white text-gray-900'"
                    class="profile-menu absolute right-0 text-left mt-2 w-36 rounded-md shadow-lg border border-gray-300 dark:border-gray-600 z-50 flex flex-col items-start"
                >
                    <button type="button" id="nostr-login-btn"
                            class="w-full text-left px-4 py-2 nav-link flex items-center gap-1 border-b border-gray-200 dark:border-gray-700">
                        <svg data-lucide="log-in" class="w-5 h-5"></svg>
                        <span class="ml-1">{{ __('Nostr') }}</span>
                    </button>

                    <a href="{{ route('history.index') }}"
                       class="flex items-center gap-1 px-4 py-2 nav-link w-full text-left border-b border-gray-200 dark:border-gray-700">
                        <svg data-lucide="scroll" class="w-5 h-5"></svg>
                        <span>{{ __('History') }}</span>
                    </a>

                    <button type="button"
                            class="w-full text-left px-4 py-2 nav-link flex items-center gap-1 border-b border-gray-200 dark:border-gray-700 last:border-b-0"
                            @click="dark = !dark; $nextTick(() => refreshThemeIcon()); open = false;">
                        <svg :data-lucide="dark ? 'sun' : 'moon'" id="theme-icon" class="w-5 h-5"></svg>
                        <span class="ml-1">{{ __('Theme') }}</span>
                    </button>
                </div>
            </div>
        @endif
    </nav>
</header>
