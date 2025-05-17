@php
    use App\Enums\PromptPersona;
@endphp

@props([
    'questionPlaceholder',
    'persona',
    'suggestedPromptsGrouped',
    'search' => '',
    'question' => '',
    'maxBitcoinBlockHeight' => 10_000_000,
    'personaDescriptions'=> '',
])

<script>
    window.suggestedPromptsGrouped = @json($suggestedPromptsGrouped);
</script>

{{-- Form + Icon Side-by-Side --}}
<div class="flex flex-col sm:flex-row sm:justify-between items-start sm:items-start gap-6 max-w-5xl">
    {{-- Left: Form --}}
    <div class="w-full sm:w-2/3">
        <form
            id="satscribe-form"
            method="POST"
            action="{{ route('home.create-chat') }}"
            @submit.prevent="submitForm($event.target)"
            aria-labelledby="form-heading"
            data-turbo="false"
        >
            @csrf

            <fieldset>
                <legend id="form-heading" class="sr-only">Describe Bitcoin Data</legend>

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
                            class="w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                            placeholder="Enter txid or block height"
                        />
                    </div>

                    @error('search')
                    <div class="error mt-1 text-red-500 text-sm" role="alert">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Helper text --}}
                <p x-text="helperText" :class="helperClass" class="text-sm mt-1 block"></p>

                {{-- Advanced options --}}
                <div x-data="{ showAdvanced: false }" class="form-group ">
                    <button
                        type="button"
                        class="text-sm font-medium flex items-center cursor-pointer gap-2 mt-4"
                        @click="showAdvanced = !showAdvanced"
                    >
                        <i data-lucide="sliders-horizontal"></i>
                        <span x-show="!showAdvanced">Show advanced fields ▾</span>
                        <span x-show="showAdvanced">Hide advanced fields ▴</span>
                    </button>

                    <div
                        x-show="showAdvanced"
                        x-cloak
                        x-transition
                        class="mt-4 advanced-fields rounded-lg px-4 py-3 space-y-4 shadow-sm"
                    >
                        {{-- Persona selection --}}
                        <div
                            x-data="{
                                selectedPersona: '{{ $persona ?? PromptPersona::DEFAULT }}',
                                descriptions: {{$personaDescriptions}}
                            }"
                            class="space-y-2"
                        >
                            <label for="persona" class="persona-label block text-sm font-medium mb-1">
                                AI Persona
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
                                Ask a Question
                            </label>
                            <input
                                type="text"
                                id="question"
                                name="question"
                                value="{{ $question }}"
                                placeholder="{{ $questionPlaceholder ?? 'Compare with the previous block' }}"
                                class="form-input w-full"
                                aria-describedby="questionHelp"
                                autocomplete="off"
                                maxlength="200"
                            >
                            <small id="questionHelp" class="text-gray-600 text-sm mt-1 block mb-2">
                                Ask the AI a specific question about this transaction or block.
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

                        {{-- Refresh checkbox --}}
                        <div class="flex items-start gap-3">
                            <input
                                type="checkbox"
                                id="refresh"
                                name="refresh"
                                value="true"
                                class="checkbox-input mt-1 cursor-pointer"
                            >
                            <label for="refresh"
                                   class="block text-sm font-medium text-gray-900 mb-1 cursor-pointer">
                                Fetch the latest data from the blockchain<br>
                                <small class="checkbox-help text-gray-600 dark:text-gray-400">
                                    (Skips cached data and requests fresh live data from the blockchain and OpenAI)
                                </small>
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
                    >
                        <template x-if="isSubmitting">
                            <!-- spinner icon here -->
                            <svg class="animate-spin h-5 w-5 mr-2"> ... </svg>
                        </template>

                        <span id="submit-icon" x-cloak class="sm-2">
                            <i data-lucide="zap" class="w-4 h-4"></i>
                        </span>
                        <span id="submit-text" x-cloak> Satscribe</span>
                    </button>

                    <button
                        id="random-button"
                        type="button"
                        @click="fetchRandomBlock()"
                        :disabled="isSubmitting"
                        class="w-1/4 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md flex items-center justify-center gap-2 cursor-pointer"
                        title="Get a random block"
                    >
                        <i data-lucide="shuffle" class="w-4 h-4"></i>
                        <span class="hidden md:inline"> Random</span>
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
