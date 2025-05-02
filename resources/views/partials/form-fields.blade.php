<form
    method="POST"
    action="{{ route('home.submit') }}"
    aria-labelledby="form-heading"
    data-turbo="false"
    @submit.prevent="submitForm($event.target)"
>
    @csrf
    <fieldset>
        <legend id="form-heading" class="sr-only">Describe Bitcoin Data</legend>
        <fieldset>
            <legend id="form-heading" class="sr-only">Describe Bitcoin Data</legend>

            {{-- Input field --}}
            <div class="form-section mb-6">
                <label for="search" class="block text-sm font-medium text-gray-900 mb-1">
                    Transaction ID or Block Height
                </label>

                <input
                    type="text"
                    id="search"
                    name="search"
                    x-model="input"
                    placeholder="e.g. 4b0d... or 840000 — default: latest"
                    class="form-input w-full"
                    autocomplete="off"
                    :required="false"
                    autofocus
                    @input="validate()"
                    aria-describedby="searchHelp"
                >

                <small id="searchHelp" class="text-sm mt-1 block">
                    <span
                        x-text="helperText"
                        :class="helperClass"
                        x-cloak
                        class="transition-colors duration-200 ease-in-out"
                    ></span>
                </small>

                @error('search')
                <div class="error mt-1 text-red-500 text-sm" role="alert">{{ $message }}</div>
                @enderror
            </div>

            {{-- Hidden flag --}}
            <input type="hidden" name="submitted" value="1">

            {{-- Advanced options --}}
            <div x-data="{ showAdvanced: false }" class="form-group ">
                <button
                    type="button"
                    class="text-sm font-medium text-orange-600 hover:text-orange-700 flex items-center cursor-pointer gap-2 mt-4"
                    @click="showAdvanced = !showAdvanced"
                >
                    <i data-lucide="sliders-horizontal" class="text-orange-500"></i>
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
                    <div>
                        <label for="persona" class="persona-label block text-sm font-medium mb-1">
                            AI Persona
                        </label>

                        <div class="relative">
                            <select
                                name="persona" id="persona"
                                class="form-select persona-select w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-sm text-gray-800 dark:text-gray-100 rounded-md shadow-sm focus:ring-orange-400 focus:border-orange-400"
                            >
                                @foreach (\App\Enums\PromptPersona::cases() as $p)
                                    <option value="{{ $p->value }}"
                                        {{ old('persona', $persona ?? 'developer') === $p->value ? 'selected' : '' }}>
                                        {{ $p->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <small class="checkbox-help text-gray-600 dark:text-gray-400 mt-1 block">
                            Choose how you'd like the AI to explain things.
                        </small>
                    </div>

                    {{-- Optional question --}}
                    <div class="form-section mb-6">
                        <label for="question" class="block text-sm font-medium text-gray-900 mb-1">
                            Ask a Question
                        </label>
                        <input
                            type="text"
                            id="question"
                            name="question"
                            value="{{ old('question', $question ?? '') }}"
                            placeholder="{{ $questionPlaceholder ?? 'What is the total input value?' }}"
                            class="form-input w-full"
                            aria-describedby="questionHelp"
                            autocomplete="off"
                            maxlength="200"
                        >
                        <small id="questionHelp" class="text-gray-600 text-sm mt-1 block">
                            Ask the AI a specific question about this transaction or block.
                        </small>
                        @error('question')
                        <div class="error mt-1 text-red-500 text-sm" role="alert">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Suggested Prompts --}}
                    <div x-data="{ promptType: null }"
                         x-init="$watch('input', value => {
        if (/^[a-fA-F0-9]{64}$/.test(value)) {
            promptType = 'transaction';
        } else if (/^\d+$/.test(value)) {
            promptType = 'block';
        } else {
            promptType = null;
        }
     })"
                         class="mt-3"
                    >
                        <template x-if="promptType">
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-200 mb-1">
                                    Suggested Questions
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($suggestedPromptsGrouped as $type => $questions)
                                        <template x-if="promptType === '{{ $type }}' || '{{ $type }}' === 'both'">
                                            <template x-for="prompt in @js($questions)" :key="prompt">
                                                <button type="button"
                                                        class="px-3 py-1 rounded-full text-sm bg-gray-100 hover:bg-orange-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-orange-400 dark:hover:text-gray-900 transition cursor-pointer"
                                                        @click="document.getElementById('question').value = prompt">
                                                    <span x-text="prompt"></span>
                                                </button>
                                            </template>
                                        </template>
                                    @endforeach
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Refresh checkbox --}}
                    <div class="flex items-start gap-3">
                        <input
                            type="checkbox"
                            id="refresh"
                            name="refresh"
                            value="true"
                            class="checkbox-input mt-1 accent-orange-500 dark:accent-orange-400 cursor-pointer"
                        >
                        <label for="refresh" class="block text-sm font-medium text-gray-900 mb-1 cursor-pointer">
                            Fetch the latest data from the blockchain<br>
                            <small class="checkbox-help text-gray-600 dark:text-gray-400">
                                (Skips cached data and requests fresh live data from the blockchain and OpenAI)
                            </small>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Submit button --}}
            <div class="form-actions mt-4 mb-4 flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                <button
                    type="submit"
                    class="form-button w-full sm:w-auto"
                    id="submit-button"
                    :disabled="isSubmitting"
                >
                    <i data-lucide="loader-2" class="animate-spin mr-2" x-show="isSubmitting" x-cloak></i>
                    <span x-show="!isSubmitting" x-cloak id="submit-text">Satscribe</span>
                    <span x-show="!isSubmitting" x-cloak id="submit-icon" class="sm-2">
                        <i data-lucide="zap" class="w-4 h-4"></i>
                    </span>
                </button>

                {{-- Optional freshness badge --}}
                @isset($isFresh)
                    @if ($isFresh)
                        <div class="inline-flex items-center text-sm text-green-800 bg-green-100 border border-green-200 px-3 py-2 rounded-md shadow-sm">
                            <i data-lucide="flask" class="text-green-500 mr-1"></i>
                            <strong class="mr-1">Fresh!</strong>
                            <span>Generation using live blockchain data ✨</span>
                        </div>
                    @else
                        <div class="inline-flex items-center text-sm text-yellow-800 bg-yellow-100 border border-yellow-200 px-3 py-2 rounded-md shadow-sm">
                            <i data-lucide="history" class="text-yellow-600 mr-1"></i>
                            <span>Loaded from previous analysis (cached)</span>
                        </div>
                    @endif
                @endisset
            </div>

        </fieldset>
    </fieldset>
</form>
