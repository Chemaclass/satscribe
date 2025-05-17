@php
    use App\Enums\PromptPersona;
@endphp

@extends('layouts.base')

@section('content')
    <section
        class="satscribe-home px-2 py-6"
        x-data="searchInputValidator('{{ old('search', $search ?? '') }}', {{ $maxBitcoinBlockHeight }})"
        x-init="
            validate();
            $watch('isSubmitting', value => {
                if (value) {
                    window.refreshLucideIcons?.();
                }
            });
        ">
        <x-home.header/>
        <x-home.form
            :search="old('search', $search ?? '')"
            :question="old('question', $question ?? '')"
            :maxBitcoinBlockHeight="$maxBitcoinBlockHeight"
            :suggestedPromptsGrouped="$suggestedPromptsGrouped"
            :persona="old('persona', $persona ?? PromptPersona::DEFAULT)"
            :personaDescriptions="$personaDescriptions"
        />

        @if(isset($chat))
            @include('partials.chat', [
                'chat' => $chat,
                'suggestions' => $suggestions,
            ])
        @endif

        <div id="results-container"></div>

        <div id="loading-container" class="p-4 hidden rounded shadow-sm">
            <h2 class="text-2xl font-bold mb-2 flex items-center">
                <i data-lucide="bot" class="w-6 h-6 mr-2"></i> Assistant
            </h2>
            <div class="leading-relaxed prose dark:prose-invert">
                <p class="flex items-center gap-2">
                    <i data-lucide="loader-2" class="w-5 h-5 animate-spin text-orange-400"></i>
                    <span x-text="loadingMessage"></span>
                </p>
            </div>
        </div>

    </section>
    <x-paywall-modal/>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    <script>
        function searchInputValidator(initial = '', initialMaxHeight = 10_000_000) {
            return {
                input: initial,
                valid: false,
                isHex64: false,
                isBlockHeight: false,
                isBlockHash: false,
                isSubmitting: false,
                maxBitcoinBlockHeight: initialMaxHeight,
                errorFollowUpQuestion: '',
                loadingMessage: '',
                loadingMessages: [
                    "Just a sec — I'm working on your request and putting everything together for you!",
                    "Hang on a moment while I sort this out for you — almost there!",
                    "Give me a moment, I'm digging into your request and cooking up a response.",
                    "One moment while I pull everything together — this will be worth the wait!",
                    "Working on it! Just making sure I get you the best answer I can.",
                    "Hold tight — I'm piecing things together and getting your reply ready.",
                    "Crafting your answer — I'll be done in a flash.",
                    "Almost done — just double-checking everything for you!",
                    "Hang tight — I'm wrapping this up right now.",
                    "Working my magic — your response is coming up shortly!",
                    "Just a moment — pulling in all the right info for you.",
                    "On it! I'm making sure every detail is spot-on.",
                ],

                async submitForm(form) {
                    if (this.isSubmitting) return;

                    document.getElementById('chat-container')?.style.setProperty('display', 'none');
                    this.loadingMessage = this.loadingMessages[Math.floor(Math.random() * this.loadingMessages.length)];
                    this.isSubmitting = true;

                    try {
                        const formData = new FormData(form);

                        const {data} = await axios.post(form.action, formData, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            validateStatus: function (status) {
                                return true;
                            }
                        });

                        if (data.maxBitcoinBlockHeight) {
                            this.maxBitcoinBlockHeight = data.maxBitcoinBlockHeight;
                        }

                        if (data.status === 'rate_limited') {
                            window.dispatchEvent(new CustomEvent('rate-limit-reached', {
                                detail: {
                                    invoice: data.invoice ?? {},
                                    maxAttempts: data.maxAttempts
                                }
                            }));

                            document.dispatchEvent(new CustomEvent('invoice-created', {
                                detail: {
                                    identifier: data.invoice.identifier,
                                }
                            }));

                            return;
                        }

                        const resultsContainer = document.getElementById('results-container');
                        resultsContainer.innerHTML = data.html || data.error || '';

                        window.refreshLucideIcons?.();

                        const url = new URL(window.location);
                        url.pathname = `/chats/${data.chatUlid}`;
                        window.history.pushState({}, '', url);

                        const searchInput = document.getElementById('search-input');
                        if (searchInput && data.search?.text) {
                            searchInput.value = data.search.text;
                            this.input = data.search.text;
                            this.validate();
                        }
                    } catch (error) {
                        console.error('submitForm error:', error.message);
                    } finally {
                        this.isSubmitting = false;
                    }
                },

                get helperText() {
                    if (!this.input.trim()) return 'Enter a valid TXID, block height, or block hash.';
                    if (!this.valid) return 'Invalid format. Must be a TXID, block height, or block hash.';
                    if (this.isBlockHash) return 'Valid block hash found.';
                    if (this.isHex64) return 'Valid TXID found.';
                    if (this.isBlockHeight) return 'Valid block height found.';
                    return '';
                },

                get helperClass() {
                    if (!this.input.trim()) return 'text-gray-600';
                    return this.valid ? 'text-green-600 font-medium' : 'text-red-600';
                },

                validate() {
                    const trimmed = this.input.trim();
                    const height = parseInt(trimmed, 10);

                    this.isHex64 = /^[a-fA-F0-9]{64}$/.test(trimmed);
                    this.isBlockHeight = /^\d+$/.test(trimmed) && height <= this.maxBitcoinBlockHeight;
                    this.isBlockHash = this.isHex64 && trimmed.startsWith('00000000');
                    this.valid = this.isHex64 || this.isBlockHeight || this.isBlockHash;
                },

                async fetchRandomBlock() {
                    const maxHeight = this.maxBitcoinBlockHeight;
                    const randomHeight = Math.floor(Math.random() * maxHeight);
                    this.input = randomHeight.toString();

                    // Sync DOM input
                    const searchInput = document.getElementById('search-input');
                    if (searchInput) searchInput.value = this.input;

                    // Pick a random question
                    const groups = window.suggestedPromptsGrouped || {};
                    let possibleQuestions = [
                        ...(groups['both'] || []),
                        ...(groups['block'] || []),
                    ];
                    const questionInput = document.getElementById('question');
                    if (questionInput && possibleQuestions.length > 0) {
                        questionInput.value = possibleQuestions[Math.floor(Math.random() * possibleQuestions.length)];
                    }

                    // Random persona
                    const personaSelect = document.getElementById('persona');
                    if (personaSelect && personaSelect.options.length > 0) {
                        personaSelect.selectedIndex = Math.floor(Math.random() * personaSelect.options.length);
                    }

                    this.loadingMessage = this.loadingMessages[Math.floor(Math.random() * this.loadingMessages.length)];

                    this.validate();


                    const form = document.querySelector('form');
                    if (form) {
                        await this.submitForm(form);
                    }
                },

                async sendMessageToChat(chatUlid, message) {
                    if (!message || !message.trim()) return;

                    const chatGroups = document.getElementById('chat-message-groups');
                    const assistantMsgCount = document.querySelectorAll('.assistant-message').length;

                    // 1. Add the user message
                    const userHtml = `
            <div class="chat-message-group mb-6">
                <div class="user-message mb-2 text-right">
                    <span class="font-semibold">You:</span>
                    <div class="inline-block rounded px-3 py-2">
                        ${this.escapeHtml(message)}
                    </div>
                </div>
                <!-- Assistant will be appended here -->
                <div id="assistant-message-${assistantMsgCount}"
                     class="assistant-message loading-spinner-group text-left">
                    <span class="font-semibold">Assistant:</span>
                    <div class="inline-block rounded px-3 py-2">
                        <span class="spinner-border w-4 h-4 inline-block animate-spin border-2 border-yellow-600 border-t-transparent rounded-full"></span>
                        <span class="ml-2">Thinking…</span>
                    </div>
                </div>
            </div>
        `;
                    chatGroups.insertAdjacentHTML('beforeend', userHtml);

                    // 2. Clear the input
                    document.getElementById('customFollowUp').value = "";

                    // 3. Send AJAX to backend
                    fetch(`/chats/${chatUlid}/messages`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({message: message})
                    })
                        .then(r => r.json())
                        .then(data => {
                            const spinner = document.getElementById(`assistant-message-${assistantMsgCount}`);
                            if (spinner) {
                                spinner.innerHTML = `
                                    <span class="font-semibold">Assistant:</span>
                                    <div class="inline-block rounded px-3 py-2 prose">
                                        ${data.content ? marked.parse(data.content) : 'No response.'}
                                    </div>
                                `;
                                spinner.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            }
                            if (data.suggestions) {
                                this.updateSuggestionsList(chatUlid, data.suggestions);
                            }
                        }).catch((e) => {
                            console.error(e)
                            const spinner = document.getElementById(`assistant-message-${assistantMsgCount}`);
                            if (spinner) {
                                spinner.innerHTML = `
                                    <span class="font-semibold text-yellow-700">Assistant:</span>
                                    <div class="inline-block rounded px-3 py-2 text-red-700">
                                        Error fetching assistant response.
                                    </div>
                                `;
                            }
                        }
                    );
                },

                updateSuggestionsList(chatUlid, newSuggestions) {
                    const suggestionsContainer = document.getElementById('follow-up-suggestions');
                    if (!suggestionsContainer) return;

                    suggestionsContainer.innerHTML = `
        <div class="mt-4">
            <p class="text-sm font-medium mb-2">Or try one of these</p>
            <div class="flex flex-wrap gap-2">
                ${newSuggestions.map((s, i) => `
                    <button
                        type="button"
                        class="suggested-question-prompt"
                        data-suggestion="${s.replace(/"/g, '&quot;')}"
                        data-chat-ulid="${chatUlid}"
                    >
                        ${s}
                    </button>
                `).join('')}
            </div>
        </div>
    `;
                    // Re-attach event listeners
                    const buttons = suggestionsContainer.querySelectorAll('button[data-suggestion]');
                    buttons.forEach(button => {
                        button.addEventListener('click', () => {
                            const suggestion = button.getAttribute('data-suggestion');
                            const ulid = button.getAttribute('data-chat-ulid');
                            this.sendMessageToChat(ulid, suggestion);
                        });
                    });
                },

                escapeHtml(text) {
                    return text.replace(/[\"&'\/<>]/g, function (a) {
                        return {
                            '"': '&quot;', '&': '&amp;', "'": '&#39;',
                            '/': '&#47;', '<': '&lt;', '>': '&gt;'
                        }[a];
                    });
                }
            };
        }

        function disableAllButtons() {
            const submitButtons = document.querySelectorAll('button[type="submit"],#random-button');
            submitButtons.forEach(btn => {
                btn.disabled = true
                btn.classList.add('opacity-50', 'cursor-not-allowed');
            });
        }

        function enableAllButtons() {
            const submitButtons = document.querySelectorAll('button[type="submit"],#random-button');
            submitButtons.forEach(btn => {
                btn.disabled = false
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            });
        }

        function resubmit(searchValue, questionValue = '') {
            const form = document.getElementById('satscribe-form');
            if (!form) return;

            const searchInput = document.getElementById('search-input');
            const questionInput = document.querySelector('input[name="question"]');

            if (searchInput) searchInput.value = searchValue;
            if (questionInput) questionInput.value = questionValue;

            // Update Alpine state manually
            if (window.Alpine) {
                const component = Alpine.closestDataStack(form);
                if (component) {
                    component.input = searchValue;
                    component.validate?.();
                }
            }

            window.scrollTo({top: 0, behavior: 'smooth'});

            // Submit form programmatically
            form.dispatchEvent(new Event('submit', {bubbles: true}));
        }

        function resubmitWithRefresh(searchValue, questionValue = '') {
            const refreshCheckbox = document.getElementById('refresh');
            if (refreshCheckbox) refreshCheckbox.checked = true;
            resubmit(searchValue, questionValue);
        }
    </script>
@endpush
