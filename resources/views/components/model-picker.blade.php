@php
    use App\View\Components\ModelPicker;
@endphp

{{--
    Provider/model options come from the registry via App\View\Components\ModelPicker.
    Never write a model list here: it would drift from Modules\OpenAI\Domain\Enum\AiProvider.

    The key the visitor pastes below lives in localStorage and travels in the
    X-Ai-Api-Key header, never in the form body, a data attribute or the URL.
--}}
<div
    id="model-picker"
    class="model-picker"
    x-data="modelPicker(@js([
        'defaultChoice' => $defaultChoice,
        'requiresKey' => $requiresKeyByChoice,
        'premium' => $premiumByChoice,
        'loggedIn' => nostr_pubkey() !== null,
        'labels' => ['balance' => __('model_picker.premium.balance', ['count' => ':count'])],
    ]))"
    @keydown.escape.window="open = false"
    @click.outside="open = false"
>
    <button
        type="button"
        x-ref="trigger"
        class="model-picker__trigger"
        :class="missingKey ? 'model-picker__trigger--alert' : ''"
        :aria-expanded="open ? 'true' : 'false'"
        aria-controls="model-picker-panel"
        @click="toggle()"
        title="{{ __('model_picker.title') }}"
    >
        <i data-lucide="bot" class="w-4 h-4 shrink-0"></i>
        <span class="model-picker__current" x-text="label"></span>
        <i data-lucide="circle-alert" class="w-3.5 h-3.5 shrink-0" x-show="missingKey" x-cloak></i>
        <i data-lucide="chevron-down" class="w-3.5 h-3.5 shrink-0"></i>
    </button>

    <div
        id="model-picker-panel"
        x-ref="panel"
        class="model-picker__panel"
        :class="dropUp ? 'model-picker__panel--up' : ''"
        x-show="open"
        x-cloak
        x-transition
    >
        <div class="model-picker__header">
            <span class="model-picker__heading">{{ __('model_picker.title') }}</span>
            <button type="button" class="model-picker__close" @click="open = false" title="{{ __('Close') }}">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <label for="model-picker-select" class="model-picker__label">{{ __('model_picker.select.label') }}</label>
        <select
            id="model-picker-select"
            x-ref="select"
            x-model="choice"
            @change="onChoiceChange()"
            class="model-picker__select"
        >
            <option value="" data-short="{{ __('model_picker.auto.short') }}">{{ __('model_picker.auto') }}</option>
            @foreach($providers as $provider)
                <optgroup label="{{ $provider['label'] }}">
                    @foreach($provider['models'] as $model)
                        @php
                            // Premium only counts when this deployment holds the
                            // key: otherwise the visitor brings their own and
                            // pays their own provider, which is not a sats sale.
                            $isPremium = $model['premium'] && !$provider['requires_user_key'];

                            $badges = [];

                            if ($isPremium) {
                                $badges[] = __('model_picker.badge.premium', ['sats' => number_format($packSats)]);
                            } else {
                                $badges[] = $model['free']
                                    ? __('model_picker.badge.free')
                                    : __('model_picker.badge.paid');
                            }

                            if ($provider['requires_user_key']) {
                                $badges[] = __('model_picker.badge.your_key');
                            }
                        @endphp
                        <option
                            value="{{ $provider['id'] . ModelPicker::CHOICE_SEPARATOR . $model['id'] }}"
                            data-short="{{ $model['label'] }}"
                        >{{ $model['label'] }} · {{ implode(' · ', $badges) }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        <p class="model-picker__hint">{{ __('model_picker.select.help') }}</p>

        {{-- Only for models this deployment funds; a bring-your-own-key model
             is the visitor's own bill and needs none of this. --}}
        <div class="model-picker__premium" x-show="isPremiumChoice" x-cloak>
            <p class="model-picker__notice" x-show="loggedIn">
                <i data-lucide="zap" class="w-3.5 h-3.5 shrink-0"></i>
                <span x-text="balanceLabel"></span>
            </p>

            <p class="model-picker__notice model-picker__notice--error" x-show="!loggedIn">
                <i data-lucide="circle-alert" class="w-3.5 h-3.5 shrink-0"></i>
                <span>{{ __('model_picker.premium.login') }}</span>
            </p>

            <button
                type="button"
                class="model-picker__buy"
                x-show="loggedIn && !packInvoice"
                @click="buyPack()"
                :disabled="buying"
            >{{ __('model_picker.premium.buy', ['count' => $packMessages, 'sats' => number_format($packSats)]) }}</button>

            <div class="model-picker__invoice" x-show="packInvoice" x-cloak>
                <img
                    :src="packInvoice?.qr_code_svg"
                    alt="{{ __('Lightning Invoice QR') }}"
                    class="model-picker__qr"
                    x-show="packInvoice?.qr_code_svg"
                />
                <button type="button" class="model-picker__forget" @click="copyInvoice()">
                    <span x-text="copiedInvoice ? '{{ __('Copied!') }}' : '{{ __('Copy') }}'"></span>
                </button>
            </div>
        </div>

        <label for="model-picker-key" class="model-picker__label mt-3">{{ __('model_picker.key.label') }}</label>
        <div class="model-picker__key-row">
            <input
                id="model-picker-key"
                x-ref="keyInput"
                type="password"
                x-model="apiKey"
                @input="onKeyInput()"
                class="model-picker__input"
                :class="showKeyError ? 'model-picker__input--error' : ''"
                placeholder="{{ __('model_picker.key.placeholder') }}"
                autocomplete="off"
                autocapitalize="off"
                autocorrect="off"
                spellcheck="false"
                data-1p-ignore
                data-lpignore="true"
                aria-describedby="model-picker-privacy"
            >
            <button
                type="button"
                class="model-picker__forget"
                x-show="apiKey.trim() !== ''"
                x-cloak
                @click="forgetKey()"
            >{{ __('model_picker.key.clear') }}</button>
        </div>

        <p class="model-picker__notice model-picker__notice--error" x-show="showKeyError" x-cloak>
            <i data-lucide="circle-alert" class="w-3.5 h-3.5 shrink-0"></i>
            <span>{{ __('model_picker.key.missing') }}</span>
        </p>

        <p id="model-picker-privacy" class="model-picker__notice">
            <i data-lucide="lock" class="w-3.5 h-3.5 shrink-0"></i>
            <span>{{ __('model_picker.key.privacy') }}</span>
        </p>
    </div>
</div>
