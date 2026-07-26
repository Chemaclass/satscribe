@php
    use Modules\Shared\Domain\Enum\Chat\PromptPersona;
@endphp

@props([
    'questionPlaceholder',
    'persona',
    'suggestedPromptsGrouped',
    'isChat',
    'search' => '',
    'question' => '',
    'maxBitcoinBlockHeight' => 10_000_000,
    'latestBlockHeight' => 0,
    'personaDescriptions'=> '',
])

<script>
    window.suggestedPromptsGrouped = @json($suggestedPromptsGrouped);
</script>

{{-- Form + Icon Side-by-Side --}}
<div class="flex flex-col sm:flex-row sm:justify-between items-start sm:items-start gap-6 max-w-5xl my-12">
    {{-- Left: Form --}}
    <div class="w-full sm:w-2/3">
        <form
                id="satscribe-form"
                method="POST"
                action="{{ route('home.create-chat') }}"
                @submit.prevent="submitForm($event.target); hasSubmitted = true;"
                aria-labelledby="form-heading"
                data-turbo="false"
        >
            @csrf

            <fieldset>
                <legend id="form-heading" class="sr-only">{{ __('Describe Bitcoin Data') }}</legend>

                {{-- Search input --}}
                <div class="flex gap-2 items-start">
                    <div class="flex-grow">
                        <input
                                id="search-input"
                                type="text"
                                name="search"
                                x-model="input"
                                @input="validate"
                                :disabled="isSubmitting"
                                autocomplete="off"
                                spellcheck="false"
                                inputmode="text"
                                aria-describedby="search-detected search-helper"
                                {{-- text-base keeps iOS Safari from zooming in when the field is focused --}}
                                class="w-full px-4 py-2 text-base border rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                placeholder="{{ __('home.placeholder', ['height' => $latestBlockHeight]) }}"
                        />
                    </div>

                    @error('search')
                    <div class="error mt-1 text-red-500 text-sm" role="alert">{{ $message }}</div>
                    @enderror
                </div>

                {{--
                    What the server will treat this input as. The verdict comes from
                    resources/js/prompt-input.js, which mirrors PromptInput::fromRaw(),
                    so the badge can never disagree with what actually gets fetched.
                --}}
                <p
                    id="search-detected"
                    x-show="showDetected"
                    x-cloak
                    class="detected-badge mt-2"
                    aria-live="polite"
                >
                    <span x-show="detectedIsBlock" x-cloak><i data-lucide="box" class="w-3.5 h-3.5"></i></span>
                    <span x-show="!detectedIsBlock" x-cloak><i data-lucide="arrow-right-left" class="w-3.5 h-3.5"></i></span>
                    <span>{{ __('Detected') }}:</span>
                    <strong x-text="detectedLabel"></strong>
                </p>

                {{-- Helper text --}}
                <p id="search-helper" x-text="helperText" :class="helperClass" class="text-sm mt-1 block cursor-pointer" @click="focusSearchInput"></p>

                {{-- The two controls that shape the answer stay in the form:
                     hiding them behind a disclosure meant most visitors never
                     changed the persona at all. --}}
                <div class="form-group mt-5 space-y-5">
                    {{-- Persona selection --}}
                        <div
                                x-data="{
                                selectedPersona: '{{ $persona ?? PromptPersona::DEFAULT }}',
                                descriptions: {{$personaDescriptions}}
                            }"
                                class="space-y-2"
                        >
                            <label for="persona" class="persona-label block text-sm font-medium mb-1">
                                {{ __('AI Persona') }}
                            </label>

                            <input type="hidden" name="persona" :value="selectedPersona">

                            <div class="persona-buttons flex gap-2 mt-2 w-full">
                                @foreach (PromptPersona::cases() as $p)
                                    <button
                                            type="button"
                                            @click="selectedPersona = '{{ $p->value }}'"
                                            :class="selectedPersona === '{{ $p->value }}'
                ? 'persona-btn persona-btn--active'
                : 'persona-btn'"
                                            class="transition duration-200 ease-in-out w-1/3 text-center"
                                    >
                                        {{ $p->label() }}
                                    </button>
                                @endforeach
                            </div>

                            <small class="checkbox-help block" x-text="descriptions[selectedPersona]"></small>
                        </div>

                    {{-- Optional question + Suggested Prompts --}}
                        <div class="form-section mb-6">
                            <label for="question" class="block text-sm font-medium text-gray-900 mb-1">
                                {{ __('Ask a Question') }}
                            </label>
                            <input
                                    type="text"
                                    id="question"
                                    name="question"
                                    value="{{ $question }}"
                                    placeholder="{{ __($questionPlaceholder ?? 'Compare with the previous block') }}"
                                    class="form-input w-full"
                                    aria-describedby="questionHelp"
                                    autocomplete="off"
                                    maxlength="200"
                            >
                            <small id="questionHelp" class="text-gray-600 text-sm mt-1 block mb-2">
                                {{ __('Optional. Leave blank for a general overview.') }}
                            </small>

                            {{-- Suggested Prompts inline --}}
                            <div
                                    x-data="{ promptType: null }"
                                    x-init="$watch('input', value => {
        if (/^[a-fA-F0-9]{64}$/.test(value)) {
            promptType = 'transaction';
        } else if (/^\d+$/.test(value)) {
            promptType = 'block';
        } else {
            promptType = null;
        }
    })"
                            >
                                <template x-if="promptType">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($suggestedPromptsGrouped as $type => $questions)
                                            <template
                                                    x-if="promptType === '{{ $type }}' || '{{ $type }}' === 'both'">
                                                <template x-for="prompt in @js($questions)" :key="prompt">
                                                    <button type="button"
                                                            class="suggested-question-prompt px-3 py-1 rounded-full text-sm transition cursor-pointer"
                                                            @click="document.getElementById('question').value = prompt">
                                                        <span x-text="prompt"></span>
                                                    </button>
                                                </template>
                                            </template>
                                        @endforeach
                                    </div>
                                </template>
                            </div>

                            @error('question')
                            <div class="error mt-1 text-red-500 text-sm" role="alert">{{ $message }}</div>
                            @enderror
                        </div>
                </div>

                {{-- Rarely-touched switches, collapsed by default --}}
                <div x-data="{ showAdvanced: false }" class="form-group">
                    <button
                            type="button"
                            class="options-toggle"
                            :aria-expanded="showAdvanced ? 'true' : 'false'"
                            @click="showAdvanced = !showAdvanced"
                    >
                        <i data-lucide="sliders-horizontal" class="w-4 h-4 shrink-0"></i>
                        <span>{{ __('More options') }}</span>
                        <i data-lucide="chevron-down" class="w-4 h-4 shrink-0 options-toggle__chevron"
                           :class="showAdvanced ? 'options-toggle__chevron--open' : ''"></i>
                    </button>

                    <div
                            x-show="showAdvanced"
                            x-cloak
                            x-transition
                            class="mt-3 advanced-fields rounded-lg px-4 py-3 space-y-3 shadow-sm"
                    >
                        {{-- Refresh checkbox --}}
                        <div class="flex items-start gap-3">
                            <input
                                    type="checkbox"
                                    id="refresh"
                                    name="refresh"
                                    value="true"
                                    class="checkbox-input mt-1 cursor-pointer"
                            >
                            <label for="refresh" class="option-label">
                                <span class="option-label__title">{{ __('Skip the cache') }}</span>
                                <span class="option-label__hint">{{ __('Fetch this block or transaction again and write a new answer.') }}</span>
                            </label>
                        </div>

                        {{-- Private checkbox --}}
                        <div class="flex items-start gap-3">
                            <input
                                    type="checkbox"
                                    id="private"
                                    name="private"
                                    checked
                                    class="checkbox-input mt-1 cursor-pointer"
                            >
                            <label for="private" class="option-label">
                                <span class="option-label__title">{{ __('Keep this chat private') }}</span>
                                <span class="option-label__hint">{{ __('Keeps it out of the public archive. You can still share the link.') }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Submit + Surprise Buttons --}}
                <div class="form-actions mt-4 mb-4 flex gap-2">
                    <button
                            id="submit-button"
                            type="submit"
                            :disabled="isSubmitting"
                            class="form-button w-3/4"
                            @click="hasSubmitted = true"
                    >
                        <span x-cloak class="submit-icon sm-2">
                            <i data-lucide="zap" class="w-4 h-4"></i>
                        </span>
                        @if($isChat)
                            <span>{{ __('Start a new chat') }}</span>
                        @else
                            <span x-text="hasSubmitted ? '{{ __('Start a new chat') }}' : 'Satscribe'"></span>
                        @endif
                    </button>

                    <button
                            id="random-button"
                            type="button"
                            @click="hasSubmitted = true && fetchRandomBlock()"
                            :disabled="isSubmitting"
                            class="w-1/4 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md flex items-center justify-center gap-2 cursor-pointer"
                            title="{{ __('Get a random block') }}"
                    >
                        <i data-lucide="shuffle" class="w-4 h-4"></i>
                        <span class="hidden md:inline"> {{ __('Random') }}</span>
                    </button>
                </div>
            </fieldset>
        </form>
    </div>
    {{-- Right: Bitcoin Icon --}}
    <div class="hidden sm:flex w-1/3 h-45 items-center justify-center select-none" aria-hidden="true">
        <i data-lucide="bitcoin" class="w-[150px] h-[150px] animate-bounce-wave text-orange-500"
           style="color: var(--btc-orange);"></i>
    </div>
</div>
