<div id="nostr-login-modal"
     x-data="nostrLoginModal()"
     x-init="window.nostrManualLoginModal = $data"
     x-show="show"
     @keydown.escape.window="closeModal"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 backdrop-blur-sm"
     style="display: none;"
     x-cloak>
    <div class="relative w-full max-w-md p-6 rounded-2xl shadow-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700"
         @click.away="closeModal"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ __('Manual Nostr Login') }}</h2>
        <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
            {{ __('Copy the challenge below and sign it in your Nostr client.') }}
            <a href="{{ route('nostr.index') }}" class="hover:underline ml-1" target="_blank">{{ __('What is nostr?') }}</a>
            <span class="hidden sm:inline">•</span>
            <button @click="showHelp = !showHelp" class="text-sm text-orange-400 hover:underline mb-2 cursor-pointer">
                <span x-text="showHelp ? 'Hide tutorial' : 'How do I sign this in my Nostr client?'"></span>
            </button>
        </p>
        <div x-show="showHelp" class="mb-4 text-sm text-gray-800 dark:text-gray-200 bg-blue-50 dark:bg-gray-800 p-3 rounded" x-transition>
            <p class="mb-2 font-semibold">Signing the challenge in your Nostr client:</p>
            <ol class="list-decimal list-inside space-y-1">
                <li>Copy the challenge string below.</li>
                <li>Use a Nostr client or script that supports signing arbitrary events. Most mobile/web apps like Damus or Amethyst may not support this yet.</li>
                <li>Alternatively, use a browser extension like <a href="https://github.com/fiatjaf/nos2x" target="_blank" class="text-blue-500 underline">nos2x</a> or a command-line tool.</li>
                <li>Sign the challenge and copy the signed event JSON.</li>
                <li>Paste it into the textarea below and click "Login."</li>
            </ol>
            <p class="mt-2 text-xs text-orange-500">Note: If you're not sure how to sign manually, it's recommended to use NIP-07 browser extension login instead.</p>
        </div>
        <div class="bg-gray-100 dark:bg-gray-700 text-xs text-orange-600 dark:text-orange-300 font-mono p-3 rounded mb-3 select-all break-all" x-text="challenge"></div>
        <textarea x-model="eventJson" class="w-full h-24 p-2 text-sm bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white rounded mb-3" placeholder='{"id":...}'></textarea>
        <div class="flex gap-2">
            <button @click="submit" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded">{{ __('Login') }}</button>
            <button @click="closeModal" class="flex-1 bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded">{{ __('Cancel') }}</button>
        </div>
    </div>
</div>
<script>
    function nostrLoginModal() {
        return {
            show: false,
            showHelp: false,
            challenge: '',
            eventJson: '',
            loginUrl: document.querySelector('meta[name="nostr-login-url"]').content,
            csrf: document.querySelector('meta[name="csrf-token"]').content,
            open(challenge) {
                this.challenge = challenge;
                this.eventJson = '';
                this.show = true;
                document.body.classList.add('modal-open');
            },
            closeModal() {
                this.show = false;
                document.body.classList.remove('modal-open');
            },
            async submit() {
                if (!this.eventJson.trim()) return;
                let event;
                try {
                    event = JSON.parse(this.eventJson);
                } catch (e) {
                    alert('Invalid JSON');
                    return;
                }
                try {
                    const resp = await fetch(this.loginUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrf
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ event })
                    });
                    if (resp.ok) {
                        const { pubkey } = await resp.json();
                        StorageClient.setNostrPubkey(pubkey);
                        window.location.reload();
                    } else {
                        alert('Login failed');
                    }
                } catch (e) {
                    console.error(e);
                }
            }
        };
    }
</script>
