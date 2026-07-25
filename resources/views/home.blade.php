@php
    use Modules\Shared\Domain\Enum\Chat\PromptPersona;
@endphp

@extends('layouts.base')

@section('content')
    <section
            class="satscribe-home px-2 flex flex-col flex-grow min-h-0"
            x-data="searchInputValidator('{{ old('search', $search ?? '') }}', {{ $maxBitcoinBlockHeight }})"
            x-init="
            validate();
            bindStopButton();
            $watch('isSubmitting', value => {
                if (value) {
                    window.refreshLucideIcons?.();
                }
            });
        ">
        <div
            id="homepage-header"
            class="flex-shrink-0 transition-all duration-300"
            :class="{ 'hidden': hasSubmitted }"
            @if(isset($chat)) style="display: none;" @endif
        >
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
                <x-home.examples />

                <div
                    class="mt-6 mb-6 text-center text-gray-500 text-sm space-y-2 home-narrative"
                    x-show="!hasSubmitted"
                    x-cloak>
                    <p>{{ __('home.narrative.line1') }}</p>
                    <p>{{ __('home.narrative.line2') }}</p>
                    <p>{{ __('home.total_messages', ['count' => number_format($totalMessages)]) }}</p>
                </div>
            @endif
        </div>

        <div id="chat-wrapper" class="flex flex-col flex-grow min-h-0">
            @if(isset($chat))
                @include('partials.chat', [
                    'chat' => $chat,
                    'suggestions' => $suggestions,
                ])
            @else
                <section id="chat-container" class="relative flex flex-col flex-grow min-h-0"></section>
            @endif
        </div>

    </section>
    <x-paywall-modal/>
    <x-home.disclaimer />
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
                streamController: null,
                isStreaming: false,
                detectedKind: 'empty',

                async submitForm(form) {
                    if (window.__PAYWALL_ACTIVE) return;
                    if (this.isSubmitting) return;

                    this.loadingMessage = this.loadingMessages[Math.floor(Math.random() * this.loadingMessages.length)];
                    this.isSubmitting = true;

                    const chatContainer = document.getElementById('chat-container');
                    if (chatContainer) {
                        const searchInput = this.input.trim();
                        const contextIcon = window.PromptInput.promptInputIcon(searchInput);
                        const contextLabel = searchInput === ''
                            ? @js(__('Latest block'))
                            : window.PromptInput.promptInputLabel(searchInput, {
                                block: @js(__('Block')),
                                transaction: @js(__('Transaction')),
                            });

                        // Create scrollable structure for new chat with header
                        chatContainer.innerHTML = `
                            <div id="chat-header" class="flex-shrink-0 flex items-center justify-between gap-2 p-2 border-b border-gray-200">
                                <div class="flex items-center gap-2 min-w-0">
                                    <i data-lucide="${contextIcon}" class="w-4 h-4 shrink-0 text-gray-500"></i>
                                    <span id="chat-context-label" class="text-sm font-medium text-gray-700 truncate">${this.escapeHtml(contextLabel)}</span>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <button
                                        id="stop-streaming-btn"
                                        type="button"
                                        class="hidden items-center gap-1 text-sm text-gray-600 hover:text-red-600 cursor-pointer"
                                        title="{{ __('Stop generating') }}"
                                    >
                                        <i data-lucide="square" class="w-4 h-4"></i>
                                        <span class="hidden sm:inline">{{ __('Stop') }}</span>
                                    </button>
                                    <a href="{{ route('home.index') }}" class="flex items-center gap-1 text-sm text-orange-600 hover:text-orange-700">
                                        <i data-lucide="plus" class="w-4 h-4"></i>
                                        <span class="hidden sm:inline">{{ __('New Chat') }}</span>
                                    </a>
                                </div>
                            </div>
                            <div id="chat-messages-scroll" class="flex-grow overflow-y-auto p-2 relative">
                                <div id="chat-message-groups"></div>
                            </div>
                            <div id="scroll-to-bottom-btn" class="hidden absolute bottom-24 right-4 z-10">
                                <button type="button" onclick="scrollChatToBottom()" class="p-2 bg-white border border-gray-300 rounded-full shadow-lg hover:bg-gray-50 transition-colors" title="{{ __('Scroll to bottom') }}">
                                    <i data-lucide="chevron-down" class="w-5 h-5 text-gray-600"></i>
                                </button>
                            </div>
                        `;
                        window.refreshLucideIcons?.();
                        this.setupScrollListener();
                        this.bindStopButton();
                    }

                    const chatMessageGroups = document.getElementById('chat-message-groups');
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

                        const userHtml = this.buildPendingExchangeHtml(userMessage, assistantMsgCount);
                        chatMessageGroups.insertAdjacentHTML('beforeend', userHtml);
                        window.refreshLucideIcons?.();
                        window.setUserAvatar?.(StorageClient.getNostrImage());

                        // Scroll to bottom of chat
                        scrollChatToBottom(false);

                        const assistantEl = document.getElementById(`assistant-message-${assistantMsgCount}`);
                        const retry = () => this.submitForm(form);

                        this.streamController = new AbortController();
                        this.setStreamingUi(true);

                        const response = await fetch('/stream', {
                            method: 'POST',
                            body: formData,
                            signal: this.streamController.signal,
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (response.status === 429) {
                            const data = await response.json();
                            window.dispatchEvent(new CustomEvent('rate-limit-reached', {detail: data}));
                            this.renderStreamOutcome(assistantEl, {status: 'rate-limited', detail: data}, retry);
                            return;
                        }

                        if (!response.ok) {
                            this.renderStreamOutcome(assistantEl, {status: 'http-error', detail: response.status}, retry);
                            return;
                        }

                        const outcome = await this.consumeStream(response, assistantEl);

                        if (outcome.status === 'done') {
                            const data = outcome.data ?? {};

                            // Update max block height first (needed for validation)
                            if (data.maxBitcoinBlockHeight) {
                                this.maxBitcoinBlockHeight = data.maxBitcoinBlockHeight;
                            }

                            // Update search input and chat header with the block/tx that was used
                            if (data.search) {
                                if (!this.input.trim()) {
                                    this.input = data.search;
                                    const searchInput = document.getElementById('search-input');
                                    if (searchInput) searchInput.value = data.search;
                                    this.validate(); // Re-validate with the new input
                                }
                                const contextLabel = document.getElementById('chat-context-label');
                                if (contextLabel) {
                                    contextLabel.textContent = window.PromptInput.promptInputLabel(data.search, {
                                        block: @js(__('Block')),
                                        transaction: @js(__('Transaction')),
                                    });
                                }
                            }

                            if (data.chatUlid) {
                                const url = new URL(window.location);
                                url.pathname = `/chats/${data.chatUlid}`;
                                window.history.pushState({}, '', url);
                            }

                            if (data.suggestions) {
                                this.renderSuggestions(data.chatUlid, data.suggestions);
                            }
                        } else {
                            this.renderStreamOutcome(assistantEl, outcome, retry);
                        }

                        window.refreshLucideIcons?.();
                    } catch (error) {
                        if (error?.name === 'AbortError') return;

                        this.renderStreamOutcome(
                            document.getElementById(`assistant-message-${assistantMsgCount}`),
                            {status: 'network', detail: error?.message ?? ''},
                            () => this.submitForm(form),
                        );

                        return Promise.reject(error);
                    } finally {
                        this.setStreamingUi(false);
                        this.streamController = null;
                        this.isSubmitting = false;
                        this.hasSubmitted = true;
                        // Ensure focus returns to input after completion
                        requestAnimationFrame(() => {
                            const input = document.getElementById('customFollowUp');
                            if (input) input.focus();
                        });
                    }
                },

                renderSuggestions(chatUlid, suggestions) {
                    const chatContainer = document.getElementById('chat-container');
                    if (!chatContainer) return;

                    const suggestionsHtml = suggestions?.length ? `
                        <div id="follow-up-suggestions">
                            <div class="mt-2">
                                <p class="text-sm font-medium mb-2">{{ __('Or try one of these') }}</p>
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
                    ` : '';

                    const formHtml = `
                        <div id="chat-message-form-container" class="flex-shrink-0 border-t border-gray-200 bg-inherit p-2">
                            <div x-data="{ message: '' }" class="w-full pt-1">
                                <form @submit.prevent="sendMessageToChat('${chatUlid}', message)" class="flex w-full items-center gap-2">
                                    <input
                                        id="customFollowUp"
                                        type="text"
                                        x-model="message"
                                        :disabled="isStreaming"
                                        class="flex-1 min-w-0 p-2 text-base border rounded disabled:opacity-50"
                                        placeholder="{{ __('Ask a follow-up question...') }}"
                                        autocomplete="off"
                                    />
                                    <button type="submit" :disabled="isStreaming" class="form-button shrink-0 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                                        <span class="submit-icon sm:mr-2">
                                            <i data-lucide="send" class="w-4 h-4"></i>
                                        </span>
                                        <span class="submit-text hidden sm:inline">{{ __('Send') }}</span>
                                    </button>
                                </form>
                            </div>
                            ${suggestionsHtml}
                        </div>
                    `;
                    // Insert form after scrollable area (at end of chat container)
                    chatContainer.insertAdjacentHTML('beforeend', formHtml);

                    // Initialize Alpine.js on the new form
                    if (window.Alpine) {
                        Alpine.initTree(document.getElementById('chat-message-form-container'));
                    }

                    window.refreshLucideIcons?.();

                    const buttons = chatContainer.querySelectorAll('button[data-suggestion]');
                    const self = this;
                    buttons.forEach(button => {
                        button.addEventListener('click', function() {
                            const suggestion = this.getAttribute('data-suggestion');
                            const ulid = this.getAttribute('data-chat-ulid');
                            self.sendMessageToChat(ulid, suggestion);
                        });
                    });

                    // Scroll to bottom after rendering form and focus input
                    scrollChatToBottom();
                    // Use setTimeout to ensure focus after Alpine initialization completes
                    setTimeout(() => {
                        const customFollowUp = document.getElementById('customFollowUp');
                        if (customFollowUp) customFollowUp.focus();
                    }, 50);
                },

                get helperText() {
                    if (!this.input.trim()) return @js(__('home.helper.empty'));
                    if (!this.valid) return @js(__('home.helper.invalid'));
                    return '';
                },

                get helperClass() {
                    if (!this.input.trim()) return 'text-gray-600';
                    return this.valid ? 'text-green-600 font-medium' : 'text-red-600';
                },

                // What the *server* will treat this input as, surfaced before the
                // user submits. Kinds come straight from prompt-input.js.
                get detectedLabel() {
                    return {
                        'block-height': @js(__('Block height')),
                        'block-hash': @js(__('Block hash')),
                        'transaction': @js(__('Transaction ID')),
                    }[this.detectedKind] ?? '';
                },

                get detectedIsBlock() {
                    return this.detectedKind === 'block-height' || this.detectedKind === 'block-hash';
                },

                get showDetected() {
                    return this.detectedLabel !== '';
                },

                validate() {
                    const result = window.PromptInput.classifyPromptInput(this.input, this.maxBitcoinBlockHeight);

                    this.isHex64 = result.isHex64;
                    this.isBlockHeight = result.isBlockHeight;
                    this.isBlockHash = result.isBlockHash;
                    this.valid = result.valid;
                    this.detectedKind = result.kind;

                    if (this.valid && !this.prefetchedInputs[result.value]) {
                        this.prefetch(result.value);
                    }
                },

                // Empty-state examples: fill the form and run it in one click.
                async runExample(search, question = '') {
                    this.input = search;
                    const searchInput = document.getElementById('search-input');
                    if (searchInput) searchInput.value = search;

                    const questionInput = document.getElementById('question');
                    if (questionInput) questionInput.value = question;

                    this.validate();

                    const form = document.getElementById('satscribe-form');
                    if (form) {
                        this.hasSubmitted = true;
                        await this.submitForm(form);
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

                    const chatContainer = document.getElementById('chat-container');
                    const chatGroups = document.getElementById('chat-message-groups') || chatContainer;
                    if (!chatGroups) return;

                    const assistantMsgCount = document.querySelectorAll('.assistant-message').length;

                    const userHtml = this.buildPendingExchangeHtml(message, assistantMsgCount);
                    chatGroups.insertAdjacentHTML('beforeend', userHtml);
                    window.refreshLucideIcons?.();
                    window.setUserAvatar?.(StorageClient.getNostrImage());

                    // Scroll to bottom after adding user message
                    scrollChatToBottom(false);

                    // Clear the input if it exists
                    const customFollowUp = document.getElementById('customFollowUp');
                    if (customFollowUp) {
                        customFollowUp.value = "";
                        customFollowUp.focus();
                    }

                    const assistantEl = document.getElementById(`assistant-message-${assistantMsgCount}`);
                    const retry = () => this.sendMessageToChat(chatUlid, message);

                    try {
                        this.streamController = new AbortController();
                        this.setStreamingUi(true);

                        // Use streaming endpoint for follow-up messages
                        const response = await fetch(`/chats/${chatUlid}/messages/stream`, {
                            method: 'POST',
                            signal: this.streamController.signal,
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
                            this.renderStreamOutcome(assistantEl, {status: 'rate-limited', detail: data}, retry);
                            return;
                        }

                        if (!response.ok) {
                            this.renderStreamOutcome(assistantEl, {status: 'http-error', detail: response.status}, retry);
                            return;
                        }

                        const outcome = await this.consumeStream(response, assistantEl);

                        if (outcome.status === 'done') {
                            if (outcome.data?.suggestions) {
                                this.updateSuggestionsList(chatUlid, outcome.data.suggestions);
                            }
                        } else {
                            this.renderStreamOutcome(assistantEl, outcome, retry);
                        }

                        window.refreshLucideIcons?.();
                    } catch (e) {
                        if (e?.name !== 'AbortError') {
                            console.error(e);
                            this.renderStreamOutcome(assistantEl, {status: 'network', detail: e?.message ?? ''}, retry);
                        }
                    } finally {
                        this.setStreamingUi(false);
                        this.streamController = null;
                        // Always give the composer back, whatever the outcome was
                        this.moveFormContainerToBottom(chatContainer);
                    }
                },

                moveFormContainerToBottom(chatContainer) {
                    const formContainer = document.getElementById('chat-message-form-container');
                    if (formContainer && chatContainer) {
                        // Move to end of chat container (before raw-data-toggle if it exists)
                        const rawDataToggle = chatContainer.querySelector('[id^="raw-data"]');
                        if (rawDataToggle) {
                            chatContainer.insertBefore(formContainer, rawDataToggle);
                        } else {
                            chatContainer.appendChild(formContainer);
                        }
                        // Focus on input after moving (with delay for fast cached responses)
                        setTimeout(() => {
                            const customFollowUp = document.getElementById('customFollowUp');
                            if (customFollowUp) customFollowUp.focus();
                        }, 50);
                    }
                },

                updateSuggestionsList(chatUlid, newSuggestions) {
                    const suggestionsContainer = document.getElementById('follow-up-suggestions');
                    if (!suggestionsContainer || !newSuggestions?.length) return;

                    suggestionsContainer.innerHTML = `
        <div class="mt-4">
            <p class="text-sm font-medium mb-2">{{ __('Or try one of these') }}</p>
            <div class="flex flex-wrap gap-2">
                ${newSuggestions.map(s => `
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
                    const self = this;
                    buttons.forEach(button => {
                        button.addEventListener('click', function() {
                            const suggestion = this.getAttribute('data-suggestion');
                            const ulid = this.getAttribute('data-chat-ulid');
                            self.sendMessageToChat(ulid, suggestion);
                        });
                    });

                    // Clear the input field and keep focus (with delay for fast cached responses)
                    setTimeout(() => {
                        const customFollowUp = document.getElementById('customFollowUp');
                        if (customFollowUp) {
                            customFollowUp.value = '';
                            customFollowUp.focus();
                        }
                    }, 50);
                },

                focusSearchInput() {
                    const el = document.getElementById('search-input');
                    if (el) el.focus();
                },

                typeText(element, markdownText, delay = 1) {
                    return new Promise(resolve => {
                        // Immediately render the full markdown text without typing effect
                        element.innerHTML = marked.parse(markdownText);
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
                },

                /**
                 * Reads one SSE response into the assistant placeholder. Both the
                 * initial search and the follow-up flow go through here, so progress,
                 * cancellation and error handling can only ever behave one way.
                 *
                 * Returns {status, data, content}:
                 *   done        the server sent its final payload
                 *   error       the server sent an `error` event
                 *   stopped     the user pressed Stop
                 *   interrupted the connection closed before `done` arrived
                 */
                async consumeStream(response, assistantEl) {
                    const contentEl = assistantEl?.querySelector('.streaming-content');
                    const skeletonEl = assistantEl?.querySelector('.skeleton-container');
                    const actionsEl = assistantEl?.querySelector('.message-actions');
                    const statusEl = assistantEl?.querySelector('.stream-status');

                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();

                    let streamedContent = '';
                    // SSE frames are split across network reads; buffering the tail
                    // keeps a chunk that straddles the boundary from being dropped.
                    let buffer = '';
                    let firstChunk = true;
                    let outcome = {status: 'interrupted', data: null};

                    if (statusEl) statusEl.textContent = @js(__('home.stream.waiting'));

                    try {
                        while (true) {
                            const {done, value} = await reader.read();
                            if (done) break;

                            buffer += decoder.decode(value, {stream: true});
                            const lines = buffer.split('\n');
                            buffer = lines.pop() ?? '';

                            for (const line of lines) {
                                if (!line.startsWith('data: ')) continue;

                                let event;
                                try {
                                    event = JSON.parse(line.slice(6));
                                } catch (e) {
                                    continue;
                                }

                                if (event.type === 'chunk') {
                                    if (firstChunk) {
                                        if (skeletonEl) skeletonEl.classList.add('hidden');
                                        if (contentEl) contentEl.classList.remove('hidden');
                                        firstChunk = false;
                                    }
                                    streamedContent += event.data;
                                    if (contentEl) {
                                        contentEl.innerHTML = marked.parse(streamedContent);
                                        if (isNearBottom()) {
                                            scrollChatToBottom();
                                        }
                                    }
                                    if (statusEl) {
                                        statusEl.textContent = this.writingProgressLabel(streamedContent);
                                    }
                                } else if (event.type === 'done') {
                                    outcome = {status: 'done', data: event.data};
                                } else if (event.type === 'error') {
                                    outcome = {status: 'error', detail: event.data};
                                }
                            }
                        }
                    } catch (e) {
                        if (e?.name !== 'AbortError') throw e;
                        outcome = {status: 'stopped', data: null};
                    }

                    if (outcome.status === 'done') {
                        const loader = assistantEl?.querySelector('.loading-dots-container');
                        if (loader) loader.remove();
                        if (actionsEl) actionsEl.classList.remove('hidden');
                    }

                    // A stream that closed mid-answer still has readable text; keep it.
                    return {...outcome, content: streamedContent};
                },

                writingProgressLabel(content) {
                    const words = content.trim() ? content.trim().split(/\s+/).length : 0;

                    return @js(__('home.stream.writing')).replace(':count', words.toLocaleString());
                },

                setStreamingUi(active) {
                    this.isStreaming = active;
                    const btn = document.getElementById('stop-streaming-btn');
                    if (!btn) return;

                    btn.classList.toggle('hidden', !active);
                    btn.classList.toggle('flex', active);
                },

                bindStopButton() {
                    const btn = document.getElementById('stop-streaming-btn');
                    if (!btn || btn.dataset.bound === '1') return;

                    btn.dataset.bound = '1';
                    btn.addEventListener('click', () => this.stopStreaming());
                },

                stopStreaming() {
                    if (this.streamController) {
                        this.streamController.abort();
                    }
                    this.setStreamingUi(false);
                },

                /** Server error strings mapped onto the states the UI knows how to explain. */
                classifyStreamError(message) {
                    const text = String(message ?? '');

                    if (/^(Transaction lookup failed|Block or transactions fetch failed)/i.test(text)) {
                        return 'not-found';
                    }
                    if (/daily (OpenAI )?limit/i.test(text)) {
                        return 'quota';
                    }

                    return 'generic';
                },

                formatRetryAfter(seconds) {
                    const total = Number(seconds);
                    if (!Number.isFinite(total) || total <= 0) return '';

                    if (total < 60) return @js(__('home.stream.in_seconds')).replace(':count', String(Math.ceil(total)));
                    if (total < 3600) return @js(__('home.stream.in_minutes')).replace(':count', String(Math.ceil(total / 60)));

                    return @js(__('home.stream.in_hours')).replace(':count', String(Math.ceil(total / 3600)));
                },

                /**
                 * Anything other than a clean `done` ends up here. A missing block or
                 * transaction is a normal empty result, not a failure, so it gets a
                 * neutral card rather than red error text.
                 */
                renderStreamOutcome(assistantEl, outcome, retry = null) {
                    if (!assistantEl) return;

                    const skeletonEl = assistantEl.querySelector('.skeleton-container');
                    const loader = assistantEl.querySelector('.loading-dots-container');
                    const noticeEl = assistantEl.querySelector('.stream-notice');
                    if (skeletonEl) skeletonEl.classList.add('hidden');
                    if (loader) loader.remove();
                    if (!noticeEl) return;

                    if (outcome.content) {
                        assistantEl.querySelector('.streaming-content')?.classList.remove('hidden');
                        assistantEl.querySelector('.message-actions')?.classList.remove('hidden');
                    }

                    noticeEl.innerHTML = this.buildStreamNotice(outcome);
                    noticeEl.classList.remove('hidden');

                    const retryBtn = noticeEl.querySelector('[data-stream-action="retry"]');
                    if (retryBtn && retry) {
                        retryBtn.addEventListener('click', () => {
                            noticeEl.classList.add('hidden');
                            retry();
                        });
                    } else if (retryBtn) {
                        retryBtn.remove();
                    }

                    const payBtn = noticeEl.querySelector('[data-stream-action="pay"]');
                    if (payBtn) {
                        payBtn.addEventListener('click', () => {
                            window.dispatchEvent(new CustomEvent('rate-limit-reached', {detail: outcome.detail}));
                        });
                    }

                    window.refreshLucideIcons?.();
                    if (isNearBottom()) scrollChatToBottom();
                },

                buildStreamNotice(outcome) {
                    const retryButton = `
                        <button type="button" data-stream-action="retry" class="stream-notice__action">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                            <span>{{ __('Try again') }}</span>
                        </button>`;

                    const card = (tone, icon, title, body, actions = retryButton) => `
                        <div class="stream-notice__card stream-notice__card--${tone}">
                            <div class="flex items-start gap-2">
                                <i data-lucide="${icon}" class="w-4 h-4 mt-0.5 shrink-0"></i>
                                <div class="min-w-0">
                                    <p class="font-medium">${title}</p>
                                    <p class="stream-notice__body">${body}</p>
                                    <div class="flex flex-wrap items-center gap-3 mt-2">${actions}</div>
                                </div>
                            </div>
                        </div>`;

                    if (outcome.status === 'stopped') {
                        return card('neutral', 'square', @js(__('home.stream.stopped.title')), @js(__('home.stream.stopped.body')));
                    }

                    if (outcome.status === 'rate-limited') {
                        const resetsIn = this.formatRetryAfter(outcome.detail?.retryAfter);
                        const max = outcome.detail?.maxAttempts ?? '';
                        const title = @js(__('home.stream.quota.title')).replace(':count', String(max));
                        const body = resetsIn
                            ? @js(__('home.stream.quota.resets')).replace(':when', resetsIn)
                            : @js(__('home.stream.quota.body'));
                        const actions = `
                            <button type="button" data-stream-action="pay" class="stream-notice__action">
                                <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                                <span>{{ __('Support with a zap') }}</span>
                            </button>${retryButton}`;

                        return card('warn', 'zap', title, body, actions);
                    }

                    if (outcome.status === 'network') {
                        return card('warn', 'circle-alert', @js(__('home.stream.network.title')), @js(__('home.stream.network.body')));
                    }

                    if (outcome.status === 'http-error') {
                        const title = @js(__('home.stream.http.title')).replace(':status', String(outcome.detail ?? ''));

                        return card('error', 'circle-alert', title, @js(__('home.stream.http.body')));
                    }

                    if (outcome.status === 'interrupted') {
                        return card('warn', 'circle-alert', @js(__('home.stream.interrupted.title')), @js(__('home.stream.interrupted.body')));
                    }

                    const kind = this.classifyStreamError(outcome.detail);

                    if (kind === 'not-found') {
                        return card('neutral', 'search-x', @js(__('home.stream.not_found.title')), @js(__('home.stream.not_found.body')));
                    }

                    if (kind === 'quota') {
                        return card('warn', 'zap', @js(__('home.stream.quota.title')).replace(':count', ''), this.escapeHtml(String(outcome.detail ?? '')));
                    }

                    return card('error', 'circle-alert', @js(__('home.stream.generic.title')), this.escapeHtml(String(outcome.detail ?? '')));
                },

                // Markup for a user message plus the assistant placeholder that the
                // streaming code then fills in. Both the initial search and the
                // follow-up flow insert this, and the streaming code below looks up
                // .streaming-content / .skeleton-container / .message-actions /
                // .stream-status / .stream-notice inside it, so the two must never
                // drift apart.
                buildPendingExchangeHtml(userMessage, assistantMsgCount) {
                    const nostrImg = StorageClient.getNostrImage();
                    const userIcon = nostrImg ?
                        `<img src="${nostrImg}" alt="user" class="w-6 h-6 rounded-full nostr-avatar object-cover">` :
                        `<span class="w-6 h-6 rounded-full bg-gray-300/50 flex items-center justify-center nostr-avatar-placeholder"><i data-lucide="user" class="w-4 h-4 text-gray-500"></i></span>`;

                    return `
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
                            <x-dots-loader />
                            <span class="stream-status text-xs text-gray-500 ml-1">{{ __('home.stream.fetching') }}</span>
                        </span>
                    </div>
                    <div class="loading-skeleton mb-2 space-y-2 skeleton-container">
                        <div class="h-4 bg-gray-200 rounded animate-pulse w-3/4"></div>
                        <div class="h-4 bg-gray-200 rounded animate-pulse w-full"></div>
                        <div class="h-4 bg-gray-200 rounded animate-pulse w-5/6"></div>
                    </div>
                    <div class="inline-block rounded px-3 py-2 prose streaming-content hidden"></div>
                    <div class="stream-notice hidden"></div>
                    <div class="message-actions flex items-center gap-2 mt-1 ml-3 hidden">
                        <button type="button" onclick="copyMessageContent(this)" class="text-xs text-gray-500 hover:text-gray-700 flex items-center gap-1 copy-btn">
                            <i data-lucide="copy" class="w-3 h-3"></i>
                            <span>{{ __('Copy') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
                },

                setupScrollListener() {
                    const scrollEl = document.getElementById('chat-messages-scroll');
                    const scrollBtn = document.getElementById('scroll-to-bottom-btn');
                    if (scrollEl && scrollBtn) {
                        scrollEl.addEventListener('scroll', () => {
                            const threshold = 100;
                            const isNearBottom = scrollEl.scrollHeight - scrollEl.scrollTop - scrollEl.clientHeight < threshold;
                            scrollBtn.classList.toggle('hidden', isNearBottom);
                        });
                    }
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


        function scrollChatToBottom(smooth = true) {
            const scrollContainer = document.getElementById('chat-messages-scroll');
            if (scrollContainer) {
                scrollContainer.scrollTo({
                    top: scrollContainer.scrollHeight,
                    behavior: smooth ? 'smooth' : 'instant'
                });
            }
        }

        function isNearBottom() {
            const scrollContainer = document.getElementById('chat-messages-scroll');
            if (!scrollContainer) return true;
            const threshold = 100;
            return scrollContainer.scrollHeight - scrollContainer.scrollTop - scrollContainer.clientHeight < threshold;
        }

        function copyMessageContent(button) {
            const messageEl = button.closest('.assistant-message');
            const contentEl = messageEl?.querySelector('.streaming-content, .message-content');
            if (!contentEl) return;

            const text = contentEl.innerText;
            navigator.clipboard.writeText(text).then(() => {
                const icon = button.querySelector('[data-lucide]');
                const span = button.querySelector('span');
                if (icon) {
                    icon.setAttribute('data-lucide', 'check');
                    icon.classList.add('text-green-600');
                }
                if (span) span.textContent = '{{ __('Copied!') }}';
                window.refreshLucideIcons?.();

                setTimeout(() => {
                    if (icon) {
                        icon.setAttribute('data-lucide', 'copy');
                        icon.classList.remove('text-green-600');
                    }
                    if (span) span.textContent = '{{ __('Copy') }}';
                    window.refreshLucideIcons?.();
                }, 2000);
            });
        }

        // Scroll to bottom on page load for existing chats
        document.addEventListener('DOMContentLoaded', function() {
            scrollChatToBottom(false);
        });
    </script>
@endpush
