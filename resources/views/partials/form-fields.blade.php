<form
    method="POST"
    action="{{ route('submit') }}"
    aria-labelledby="form-heading"
    @submit.prevent="submitForm($el)"
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
                    placeholder="e.g. 4b0d... or 840000 — default: latest block"
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

            {{-- Submit button --}}
            <div class="form-actions mt-4 flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
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

            {{-- Advanced options --}}
            <div x-data="{ showAdvanced: false }" class="form-group mb-4">
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
                    class="mt-4 advanced-fields mt-1 rounded-lg px-4 py-3 space-y-4 shadow-sm"
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
                                        {{ old('persona', $persona ?? 'educator') === $p->value ? 'selected' : '' }}>
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

                    {{-- Refresh checkbox --}}
                    <div class="flex items-start gap-3 ">
                        <input
                            type="checkbox"
                            id="refresh"
                            name="refresh"
                            value="true"
                            class="checkbox-input mt-1 accent-orange-500 dark:accent-orange-400 cursor-pointer"
                        >
                        <label for="refresh" class="checkbox-label text-sm text-gray-800 dark:text-orange-200 cursor-pointer">
                            <strong class="text-orange-700 dark:text-orange-400">Fetch the latest data from the blockchain</strong><br>
                            <small class="checkbox-help text-gray-600 dark:text-gray-400">
                                (Skips cached descriptions and requests live data from the blockchain and OpenAI)
                            </small>
                        </label>
                    </div>
                </div>
            </div>
        </fieldset>
    </fieldset>
</form>
