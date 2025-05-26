@props([
    'btcPriceUsd' => null,
    'btcPriceEur' => null,
    'btcPriceCny' => null,
    'btcPriceGbp' => null,
    'hasFaqs' => true,
])

<header class="flex justify-between select-none items-center px-4 py-3 border-gray-200 dark:border-gray-700">
    <a href="{{ url('/') }}" class="brand text-xl font-bold">Satscribe</a>

    <nav class="nav-links flex items-center">
        @if($hasFaqs)
            <a href="{{ route('faq.index') }}" class="nav-link flex items-center gap-1">
                <svg data-lucide="lightbulb" class="w-5 h-5"></svg>
                <span class="link-text">{{ __('FAQ') }}</span>
            </a>
        @endif
        <a href="{{ route('history.index') }}" class="nav-link flex items-center gap-1">
            <svg data-lucide="scroll" class="w-5 h-5"></svg>
            <span class="link-text">{{ __('History') }}</span>
        </a>
        <button class="nav-link flex items-center gap-1" @click="dark = !dark; $nextTick(() => refreshThemeIcon())">
            <svg :data-lucide="dark ? 'sun' : 'moon'" id="theme-icon" class="w-5 h-5"></svg>
            <span class="link-text" x-text="dark ? '{{ __('Light') }}' : '{{ __('Dark') }}'"></span>
        </button>


        @if(!empty($btcPriceUsd))
            <div
                class="nav-link sm:inline-flex items-center gap-1 px-1 py-1 text-sm whitespace-nowrap"
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
                    <span x-show="currency === 'usd'" x-cloak>
                        ${{ number_format($btcPriceUsd, 0) }}
                    </span>
                    <span x-show="currency === 'eur'" x-cloak>
                        &euro;{{ number_format($btcPriceEur, 0) }}
                    </span>
                    <span x-show="currency === 'cny'" x-cloak>
                        &yen;{{ number_format($btcPriceCny, 0) }}
                    </span>
                    <span x-show="currency === 'gbp'" x-cloak>
                        &pound;{{ number_format($btcPriceGbp, 0) }}
                    </span>
                </span>
                <a href="https://coinmarketcap.com/currencies/bitcoin/" target="_blank" rel="noopener"
                   class="flex items-center hidden sm:inline-flex"
                >
                    <svg data-lucide="external-link" class="w-4 h-4"></svg>
                </a>
            </div>
        @endif
    </nav>
</header>
