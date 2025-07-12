<div id="nostr-login-modal"
     x-data="nostrLoginModal()"
     x-init="window.nostrLoginModal = $data"
     x-show="show"
     @keydown.escape.window="closeModal"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 backdrop-blur-sm bg-black/40 dark:bg-black/60"
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
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">{{ __('Login with Nostr') }}</h2>

        <template x-if="error">
            <p class="mb-3 text-red-500" x-text="error"></p>
        </template>

        <div class="space-y-6">
            <div>
                <h3 class="font-semibold">{{ __('Browser extension (recommended)') }}</h3>
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
                    Good security. Requires a plug-in like
                    <a href="https://getalby.com/products/browser-extension" target="_blank" class="underline">Alby</a>
                    or
                    <a href="https://github.com/fiatjaf/nos2x" target="_blank" class="underline">nos2x</a>
                </p>
                <button @click="loginExtension"
                        class="w-full bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded">{{ __('Use Browser Extension') }}</button>
            </div>

            <div>
                <h3 class="font-semibold">{{ __('Private key') }}</h3>
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">{{ __('Less secure. Enter a private key.') }}</p>
                <input type="password" x-model="privKey"
                       class="w-full p-2 text-sm bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white rounded mb-2"
                       placeholder="nsec..."/>
                <button @click="loginPrivKey"
                        class="w-full bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded">{{ __('Login with Key') }}</button>
            </div>

            <button @click="closeModal"
                    class="w-full bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded">{{ __('Cancel') }}</button>
        </div>
    </div>
</div>
<script>
    function nostrLoginModal() {
        return {
            show: false,
            privKey: '',
            challenge: '',
            error: '',
            loginUrl: document.querySelector('meta[name="nostr-login-url"]').content,
            challengeUrl: document.querySelector('meta[name="nostr-challenge-url"]').content,
            csrf: document.querySelector('meta[name="csrf-token"]').content,
            async open() {
                this.error = '';
                this.privKey = '';
                try {
                    const r = await fetch(this.challengeUrl, {credentials: 'same-origin'});
                    const data = await r.json();
                    this.challenge = data.challenge;
                } catch {
                }
                this.show = true;
                document.body.classList.add('modal-open');
            },
            closeModal() {
                this.show = false;
                document.body.classList.remove('modal-open');
            },
            async loginExtension() {
                if (!window.nostr || !window.nostr.getPublicKey || !(window.nostr.getSignature || window.nostr.signEvent)) {
                    this.error = 'No Nostr extension detected. Please install Alby or nos2x.';
                    return;
                }
                try {
                    const pk = await window.nostr.getPublicKey();
                    const event = {
                        kind: 22242,
                        pubkey: pk,
                        created_at: Math.floor(Date.now() / 1000),
                        content: this.challenge,
                        tags: []
                    };
                    event.id = window.nostrTools.getEventHash(event);
                    if (window.nostr.getSignature) {
                        event.sig = await window.nostr.getSignature(event);
                    } else {
                        const signed = await window.nostr.signEvent(event);
                        event.sig = signed.sig;
                    }
                    const resp = await fetch(this.loginUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrf
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({event})
                    });
                    if (resp.ok) {
                        const {pubkey} = await resp.json();
                        StorageClient.setNostrPubkey(pubkey);
                        window.location.reload();
                    } else {
                        this.error = 'Login failed';
                    }
                } catch (e) {
                    this.error = 'Login failed';
                }
            },
            async loginPrivKey() {
                if (!this.privKey.trim()) return;
                let sk = this.privKey.trim();
                try {
                    if (sk.startsWith('nsec')) {
                        sk = window.nostrTools.nip19.decode(sk).data;
                    }
                    const pk = window.nostrTools.getPublicKey(sk);
                    const event = {
                        kind: 22242,
                        pubkey: pk,
                        created_at: Math.floor(Date.now() / 1000),
                        content: this.challenge,
                        tags: []
                    };
                    event.id = window.nostrTools.getEventHash(event);
                    event.sig = window.nostrTools.getSignature(event, sk);
                    const resp = await fetch(this.loginUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrf
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({event})
                    });
                    this.privKey = '';
                    if (resp.ok) {
                        const {pubkey} = await resp.json();
                        StorageClient.setNostrPubkey(pubkey);
                        window.location.reload();
                    } else {
                        this.error = 'Login failed';
                    }
                } catch (e) {
                    this.error = 'Invalid private key';
                } finally {
                    this.privKey = '';
                }
            }
        };
    }
</script>
