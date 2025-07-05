<footer class="py-2 border-t border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-2 flex-wrap">
        <div class="flex items-center gap-2 flex-wrap footer-left">
            <span>&copy;{{ date('Y') }} Built by <a href="https://chemaclass.com/" target="_blank" class="hover:underline">Chema</a></span>
        </div>

        <div class="flex items-center gap-2 flex-wrap footer-right">
            <a href="https://getalby.com/p/chemaclass" target="_blank" class="flex items-center gap-1 hover:underline">
                <i data-lucide="bitcoin" class="w-4 h-4 text-orange-500 dark:text-[--btc-orange-dark]"></i>
                <span class="sm:hidden">{{ __("home.footer.support") }}</span>
                <span class="hidden sm:inline">{{ __("home.footer.support_the_project") }}</span>
            </a>
            <span class="hidden sm:inline">•</span>

            @if(!empty($btcPriceUsd))
                <span
                    class="hidden sm:inline"
                    data-btc-price-item
                    title="1 BTC in fiat currency"
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
                </span>
                <span class="hidden sm:inline">•</span>
            @endif

            <a href="https://github.com/Chemaclass/satscribe" target="_blank" class="flex items-center gap-1 hover:underline">
                <svg data-lucide="github" class="w-4 h-4"></svg>
                {{ __('GitHub') }}
            </a>
            <span class="hidden sm:inline">•</span>

            @if(config('app.last_commit') !== 'unknown')
                <div class="hidden sm:block" title="Released commit">{{ substr(config('app.last_commit'), 0, 7) }}</div>
                <span class="hidden sm:inline">•</span>
            @endif

            <select class="nav-link" onchange="const p=new URLSearchParams(window.location.search);p.set('lang', this.value);window.location.search=p.toString();">
                <option value="en" @selected(app()->getLocale()==='en')>EN</option>
                <option value="de" @selected(app()->getLocale()==='de')>DE</option>
                <option value="es" @selected(app()->getLocale()==='es')>ES</option>
            </select>
        </div>
    </div>
</footer>
