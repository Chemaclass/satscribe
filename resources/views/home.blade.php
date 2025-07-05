@php
    use Modules\Shared\Domain\Enum\Chat\PromptPersona;
@endphp

@extends('layouts.base')

@section('content')
    <x-home.disclaimer />
    <section
            class="satscribe-home px-2"
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
                :latestBlockHeight="$latestBlockHeight"
                :suggestedPromptsGrouped="$suggestedPromptsGrouped"
                :persona="old('persona', $persona ?? PromptPersona::DEFAULT)"
                :personaDescriptions="$personaDescriptions"
                :isChat="isset($chat)"
        />

        @if(!isset($chat))
            <div
                class="mt-6 mb-6 text-center text-gray-500 text-sm space-y-2 home-narrative"
                x-show="!hasSubmitted"
                x-cloak>
                <p>{{ __('home.narrative.line1') }}</p>
                <p>{{ __('home.narrative.line2') }}</p>
                <p>{{ __('home.total_messages', ['count' => number_format($totalMessages)]) }}</p>
            </div>
        @endif

        @if(isset($chat))
            @include('partials.chat', [
                'chat' => $chat,
                'suggestions' => $suggestions,
            ])
        @else
            <section id="chat-container"></section>
        @endif

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
                hasSubmitted: false,
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
                lastRequest: null,

                async submitForm(form) {
                    if (window.__PAYWALL_ACTIVE) return;
                    if (this.isSubmitting) return;

                    this.loadingMessage = this.loadingMessages[Math.floor(Math.random() * this.loadingMessages.length)];
                    this.isSubmitting = true;

                    // if existing chat exists then remove
                    const chatContainer = document.getElementById('chat-container');
                    if (chatContainer) {
                        chatContainer.innerHTML = '';
                    }

                    const assistantMsgCount = document.querySelectorAll('.assistant-message').length;

                    try {
                        const formData = new FormData(form);

                        this.lastRequest = {
                            search: formData.get('search') || '',
                            question: formData.get('question') || '',
                            persona: formData.get('persona') || '',
                            refresh: formData.get('refresh') === 'true',
                            private: formData.get('private') === 'true',
                        };
                        window.__LAST_REQUEST__ = this.lastRequest;

                        const rawQuestion = formData.get('question');
                        const userMessage = rawQuestion?.trim() ? rawQuestion.trim() : @js(__('Give me a generic overview.'));

                        // Render user input
                        const nostrImg = StorageClient.getNostrImage();
                        const userIcon = nostrImg ?
                            `<img src="${nostrImg}" alt="user" class="w-6 h-6 rounded-full nostr-avatar">` :
                            `<i data-lucide="user" class="w-6 h-6"></i>`;

                        const userHtml = `
            <div class="chat-message-group mb-6">
                <div class="user-message mb-2 text-right" data-owned="1">
                    <div class="flex items-center gap-1 justify-end">
                        <div class="inline-block rounded px-3 py-2">
                            ${this.escapeHtml(userMessage)}
                        </div>
                        ${userIcon}
                    </div>
                </div>
                <div id="assistant-message-${assistantMsgCount}" class="assistant-message loading-spinner-group text-left">
                    <x-chat.assistant-loading-prompt/>
                </div>
            </div>
        `;
                        chatContainer.insertAdjacentHTML('beforeend', userHtml);
                        window.refreshLucideIcons?.();
                        window.setUserAvatar?.(StorageClient.getNostrImage());

                        // Send to backend
                        const {data} = await axios.post(form.action, formData, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
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

                            return;
                        }

                        const chatContainerEl = document.getElementById('chat-container');
                        if (chatContainerEl) {
                            chatContainerEl.innerHTML = data.html || '';
                            const msgDiv = chatContainerEl.querySelector('.assistant-message div.inline-block');
                            const header = chatContainerEl.querySelector('.assistant-message .flex.items-center');
                            if (header) {
                                header.insertAdjacentHTML('beforeend', `
                                    <span class="ml-2 flex items-center gap-1 loading-dots-container">
                                        <span class="dots-loader">
                                            <span class="dot"></span>
                                            <span class="dot"></span>
                                            <span class="dot"></span>
                                            <span class="dot"></span>
                                            <span class="dot"></span>
                                            <span class="dot"></span>
                                        </span>
                                    </span>
                                `);
                            }
                            if (msgDiv && data.content) {
                                msgDiv.textContent = '';
                                this.typeText(msgDiv, data.content).then(() => {
                                    const loader = chatContainerEl.querySelector('.loading-dots-container');
                                    if (loader) loader.remove();
                                });
                            }
                            window.refreshLucideIcons?.();
                        }

                        // PushState to chat URL
                        if (data.chatUlid) {
                            const url = new URL(window.location);
                            url.pathname = `/chats/${data.chatUlid}`;
                            window.history.pushState({}, '', url);
                        }

                        // Update search field
                        const searchInput = document.getElementById('search-input');
                        if (searchInput && data.search?.text) {
                            searchInput.value = data.search.text;
                            this.input = data.search.text;
                            this.validate();
                        }

                        window.refreshLucideIcons?.();
                    } catch (error) {
                        if (error.response?.status === 429) {
                            const data = error.response.data;

                            window.dispatchEvent(new CustomEvent('rate-limit-reached', {detail: data}));
                            return;
                        }

                        const assistantDiv = document.getElementById(`assistant-message-${assistantMsgCount}`);
                        if (assistantDiv) {
                            assistantDiv.innerHTML = `
            <div class="inline-block rounded px-3 py-2 text-red-700">
                Error fetching assistant response.
            </div>
        `;
                        }

                        return Promise.reject(error);
                    } finally {
                        this.isSubmitting = false;
                        this.hasSubmitted = true;
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


                    const form = document.getElementById('satscribe-form');
                    if (form) {
                        await this.submitForm(form);
                    }
                },

                async sendMessageToChat(chatUlid, message) {
                    if (!message || !message.trim()) return;
                    hideChatFormContainer();

                    const chatGroups = document.getElementById('chat-message-groups');
                    const assistantMsgCount = document.querySelectorAll('.assistant-message').length;

                    // 1. Add the user message
                    const nostrImg = StorageClient.getNostrImage();
                    const userIcon = nostrImg ?
                        `<img src="${nostrImg}" alt="user" class="w-6 h-6 rounded-full nostr-avatar">` :
                        `<i data-lucide="user" class="w-6 h-6"></i>`;

                    const userHtml = `
            <div class="chat-message-group mb-6">
                <div class="user-message mb-2 text-right" data-owned="1">
                    <div class="flex items-center gap-1 justify-end">
                        <div class="inline-block rounded px-3 py-2">
                            ${this.escapeHtml(message)}
                        </div>
                        ${userIcon}
                    </div>
                </div>
                <!-- Assistant will be appended here -->
                <div id="assistant-message-${assistantMsgCount}" class="assistant-message loading-spinner-group text-left">
                    <x-chat.assistant-loading-prompt/>
                </div>
            </div>
        `;
                    chatGroups.insertAdjacentHTML('beforeend', userHtml);
                    window.refreshLucideIcons?.();
                    window.setUserAvatar?.(StorageClient.getNostrImage());

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
                                    <span class="font-semibold flex items-center gap-1">
                                        <i data-lucide="bot" class="w-6 h-6"></i>
                                        <span class="font-semibold">Scribe</span>
                                        <span class="ml-2 flex items-center gap-1 loading-dots-container">
                                            <span class="dots-loader">
                                                <span class="dot"></span>
                                                <span class="dot"></span>
                                                <span class="dot"></span>
                                                <span class="dot"></span>
                                                <span class="dot"></span>
                                                <span class="dot"></span>
                                            </span>
                                        </span>
                                    </span>
                                    <div class="inline-block rounded px-3 py-2 prose"></div>
                                `;
                                const msgEl = spinner.querySelector('div');
                                if (msgEl && data.content) {
                                    this.typeText(msgEl, data.content).then(() => {
                                        const loader = spinner.querySelector('.loading-dots-container');
                                        if (loader) loader.remove();
                                    });
                                }
                                spinner.scrollIntoView({behavior: 'smooth', block: 'start'});
                            }
                            if (data.suggestions) {
                                this.updateSuggestionsList(chatUlid, data.suggestions);
                            }
                            window.refreshLucideIcons?.();
                        }).catch((e) => {
                            console.error(e)
                            const spinner = document.getElementById(`assistant-message-${assistantMsgCount}`);
                            if (spinner) {
                                spinner.innerHTML = `
                                    <span class="font-semibold text-yellow-700">Scribe:</span>
                                    <div class="inline-block rounded px-3 py-2 text-red-700">
                                        Error fetching response.
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

                focusSearchInput() {
                    const el = document.getElementById('search-input');
                    if (el) el.focus();
                },

                typeText(element, markdownText, delay = 1) {
                    hideChatFormContainer();

                    return new Promise(resolve => {
                        // Immediately render the full markdown text without typing effect
                        element.innerHTML = marked.parse(markdownText);
                        showChatFormContainer();
                        resolve();
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


        function hideChatFormContainer() {
            const formContainer = document.getElementById('chat-message-form-container');
            if (formContainer) formContainer.classList.add('hidden');
        }

        function showChatFormContainer() {
            const formContainer = document.getElementById('chat-message-form-container');
            if (formContainer) formContainer.classList.remove('hidden');
        }
    </script>
@endpush
