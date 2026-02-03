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
                prefetchedInputs: {},
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

                        const nostrImg = StorageClient.getNostrImage();
                        const userIcon = nostrImg ?
                            `<img src="${nostrImg}" alt="user" class="w-6 h-6 rounded-full nostr-avatar object-cover">` :
                            `<span class="w-6 h-6 rounded-full bg-gray-300/50 flex items-center justify-center nostr-avatar-placeholder"><i data-lucide="user" class="w-4 h-4 text-gray-500"></i></span>`;

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
                <div id="assistant-message-${assistantMsgCount}" class="assistant-message text-left">
                    <div class="flex items-center gap-1">
                        <i data-lucide="bot" class="w-6 h-6"></i>
                        <span class="font-semibold">Scribe</span>
                        <span class="ml-2 flex items-center gap-1 loading-dots-container">
                            <span class="dots-loader">
                                <span class="dot"></span><span class="dot"></span><span class="dot"></span>
                                <span class="dot"></span><span class="dot"></span><span class="dot"></span>
                            </span>
                        </span>
                    </div>
                    <div class="inline-block rounded px-3 py-2 prose streaming-content"></div>
                </div>
            </div>
        `;
                        chatContainer.insertAdjacentHTML('beforeend', userHtml);
                        window.refreshLucideIcons?.();
                        window.setUserAvatar?.(StorageClient.getNostrImage());

                        const response = await fetch('/stream', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (response.status === 429) {
                            const data = await response.json();
                            window.dispatchEvent(new CustomEvent('rate-limit-reached', {detail: data}));
                            return;
                        }

                        const reader = response.body.getReader();
                        const decoder = new TextDecoder();
                        let streamedContent = '';
                        const contentEl = document.querySelector(`#assistant-message-${assistantMsgCount} .streaming-content`);

                        while (true) {
                            const {done, value} = await reader.read();
                            if (done) break;

                            const text = decoder.decode(value, {stream: true});
                            const lines = text.split('\n');

                            for (const line of lines) {
                                if (line.startsWith('data: ')) {
                                    try {
                                        const event = JSON.parse(line.slice(6));

                                        if (event.type === 'chunk') {
                                            streamedContent += event.data;
                                            if (contentEl) {
                                                contentEl.innerHTML = marked.parse(streamedContent);
                                            }
                                        } else if (event.type === 'done') {
                                            const loader = document.querySelector(`#assistant-message-${assistantMsgCount} .loading-dots-container`);
                                            if (loader) loader.remove();

                                            if (event.data.maxBitcoinBlockHeight) {
                                                this.maxBitcoinBlockHeight = event.data.maxBitcoinBlockHeight;
                                            }

                                            if (event.data.chatUlid) {
                                                const url = new URL(window.location);
                                                url.pathname = `/chats/${event.data.chatUlid}`;
                                                window.history.pushState({}, '', url);
                                            }

                                            if (event.data.suggestions) {
                                                this.renderSuggestions(event.data.chatUlid, event.data.suggestions);
                                            }
                                        } else if (event.type === 'error') {
                                            if (contentEl) {
                                                contentEl.innerHTML = `<span class="text-red-700">${this.escapeHtml(event.data)}</span>`;
                                            }
                                        }
                                    } catch (e) {
                                        // Ignore parse errors for incomplete chunks
                                    }
                                }
                            }
                        }

                        window.refreshLucideIcons?.();
                        showChatFormContainer();
                    } catch (error) {
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

                renderSuggestions(chatUlid, suggestions) {
                    const chatContainer = document.getElementById('chat-container');
                    if (!chatContainer || !suggestions?.length) return;

                    const suggestionsHtml = `
                        <div id="chat-message-form-container" class="mt-4 mb-8">
                            <div id="follow-up-suggestions" class="mb-8">
                                <div class="mt-4">
                                    <p class="text-sm font-medium mb-2">Or try one of these</p>
                                    <div class="flex flex-wrap gap-2">
                                        ${suggestions.map(s => `
                                            <button type="button" class="suggested-question-prompt"
                                                data-suggestion="${s.replace(/"/g, '&quot;')}"
                                                data-chat-ulid="${chatUlid}">
                                                ${s}
                                            </button>
                                        `).join('')}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    chatContainer.insertAdjacentHTML('beforeend', suggestionsHtml);

                    const buttons = chatContainer.querySelectorAll('button[data-suggestion]');
                    buttons.forEach(button => {
                        button.addEventListener('click', () => {
                            const suggestion = button.getAttribute('data-suggestion');
                            const ulid = button.getAttribute('data-chat-ulid');
                            this.sendMessageToChat(ulid, suggestion);
                        });
                    });
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

                    if (this.valid && !this.prefetchedInputs[trimmed]) {
                        this.prefetch(trimmed);
                    }
                },

                prefetch(input) {
                    this.prefetchedInputs[input] = 'pending';
                    fetch(`/api/prefetch?q=${encodeURIComponent(input)}`)
                        .then(r => r.json())
                        .then(data => {
                            this.prefetchedInputs[input] = data.status === 'ok' ? 'done' : 'error';
                        })
                        .catch(() => {
                            this.prefetchedInputs[input] = 'error';
                        });
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

                    const chatGroups = document.getElementById('chat-message-groups') || document.getElementById('chat-container');
                    if (!chatGroups) return;

                    const assistantMsgCount = document.querySelectorAll('.assistant-message').length;

                    const nostrImg = StorageClient.getNostrImage();
                    const userIcon = nostrImg ?
                        `<img src="${nostrImg}" alt="user" class="w-6 h-6 rounded-full nostr-avatar object-cover">` :
                        `<span class="w-6 h-6 rounded-full bg-gray-300/50 flex items-center justify-center nostr-avatar-placeholder"><i data-lucide="user" class="w-4 h-4 text-gray-500"></i></span>`;

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
                <div id="assistant-message-${assistantMsgCount}" class="assistant-message text-left">
                    <div class="flex items-center gap-1">
                        <i data-lucide="bot" class="w-6 h-6"></i>
                        <span class="font-semibold">Scribe</span>
                        <span class="ml-2 flex items-center gap-1 loading-dots-container">
                            <span class="dots-loader">
                                <span class="dot"></span><span class="dot"></span><span class="dot"></span>
                                <span class="dot"></span><span class="dot"></span><span class="dot"></span>
                            </span>
                        </span>
                    </div>
                    <div class="inline-block rounded px-3 py-2 prose streaming-content"></div>
                </div>
            </div>
        `;
                    chatGroups.insertAdjacentHTML('beforeend', userHtml);
                    window.refreshLucideIcons?.();
                    window.setUserAvatar?.(StorageClient.getNostrImage());

                    // Clear the input if it exists
                    const customFollowUp = document.getElementById('customFollowUp');
                    if (customFollowUp) customFollowUp.value = "";

                    try {
                        // Use streaming endpoint for follow-up messages
                        const response = await fetch(`/chats/${chatUlid}/messages/stream`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({message: message.trim()})
                        });

                        if (response.status === 429) {
                            const data = await response.json();
                            window.dispatchEvent(new CustomEvent('rate-limit-reached', {detail: data}));
                            return;
                        }

                        const reader = response.body.getReader();
                        const decoder = new TextDecoder();
                        let streamedContent = '';
                        const contentEl = document.querySelector(`#assistant-message-${assistantMsgCount} .streaming-content`);

                        while (true) {
                            const {done, value} = await reader.read();
                            if (done) break;

                            const text = decoder.decode(value, {stream: true});
                            const lines = text.split('\n');

                            for (const line of lines) {
                                if (line.startsWith('data: ')) {
                                    try {
                                        const event = JSON.parse(line.slice(6));

                                        if (event.type === 'chunk') {
                                            streamedContent += event.data;
                                            if (contentEl) {
                                                contentEl.innerHTML = marked.parse(streamedContent);
                                            }
                                        } else if (event.type === 'done') {
                                            const loader = document.querySelector(`#assistant-message-${assistantMsgCount} .loading-dots-container`);
                                            if (loader) loader.remove();

                                            if (event.data.suggestions) {
                                                this.updateSuggestionsList(chatUlid, event.data.suggestions);
                                            }
                                        } else if (event.type === 'error') {
                                            if (contentEl) {
                                                contentEl.innerHTML = `<span class="text-red-700">${this.escapeHtml(event.data)}</span>`;
                                            }
                                        }
                                    } catch (e) {
                                        // Ignore parse errors for incomplete chunks
                                    }
                                }
                            }
                        }

                        const assistantEl = document.getElementById(`assistant-message-${assistantMsgCount}`);
                        if (assistantEl) {
                            assistantEl.scrollIntoView({behavior: 'smooth', block: 'start'});
                        }
                        showChatFormContainer();
                        window.refreshLucideIcons?.();
                    } catch (e) {
                        console.error(e);
                        const assistantEl = document.getElementById(`assistant-message-${assistantMsgCount}`);
                        if (assistantEl) {
                            const contentEl = assistantEl.querySelector('.streaming-content');
                            if (contentEl) {
                                contentEl.innerHTML = `<span class="text-red-700">Error fetching response.</span>`;
                            }
                        }
                        showChatFormContainer();
                    }
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
