<div id="nostr-login-modal"
     x-data="nostrLoginModal()"
     x-init="window.nostrLoginModal = $data"
     x-show="show"
     @keydown.escape.window="closeModal"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 backdrop-blur-sm bg-black/40"
     style="display: none;"
     x-cloak>
    <div class="relative w-full max-w-md p-6 rounded-2xl shadow-2xl bg-white text-gray-900 border border-gray-300"
         @click.away="closeModal"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <h2 class="text-xl font-bold mb-4">{{ __('Login with Nostr') }}</h2>

        <template x-if="error">
            <p class="mb-3 text-red-500" x-text="error"></p>
        </template>

        <div class="space-y-6">
            <!-- Extension Login -->
            <div>
                <h3 class="font-semibold">{{ __('Browser extension (recommended)') }}</h3>
                <p class="text-sm text-gray-600 mb-2">
                    {{ __('Good security. Requires a plug-in like') }}
                    <a href="https://getalby.com/products/browser-extension" target="_blank" class="underline text-blue-600">Alby</a>
                    {{ __('or') }}
                    <a href="https://github.com/fiatjaf/nos2x" target="_blank" class="underline text-blue-600">nos2x</a>.
                </p>
                <button @click="loginWithExtension"
                        class="w-full bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded">
                    {{ __('Use Browser Extension') }}
                </button>
            </div>

            <!-- Private Key Login -->
            <div>
                <h3 class="font-semibold">{{ __('Private key') }}</h3>
                <p class="text-sm text-gray-600 mb-2">{{ __('Less secure. Enter a private key.') }}</p>
                <input type="password" x-model="privKey"
                       class="w-full p-2 text-sm bg-white border border-gray-300 text-gray-900 rounded mb-2"
                       placeholder="nsec..." />
                <button @click="loginWithPrivKey"
                        class="w-full bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded">
                    {{ __('Login with Key') }}
                </button>
                <p class="text-xs text-gray-600 mt-2">
                    {{ __('Note: While you can sign in by pasting your secret key, using a browser extension is recommended.') }}
                </p>
            </div>

            <!-- Generate Key -->
            <div>
                <h3 class="font-semibold">{{ __('Sign up with new key') }}</h3>
                <p class="text-sm text-gray-600 mb-2">{{ __('Creates a nostr key pair locally.') }}</p>
                <button @click="signupWithNewKey" :disabled="generating"
                        class="relative h-10 w-full bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded">
                    <span x-show="!generating">{{ __('Generate New Key') }}</span>
                    <span x-show="generating" class="absolute inset-0 flex items-center justify-center" x-cloak>
                        <span class="dots-loader">
                            <span class="dot"></span>
                            <span class="dot"></span>
                            <span class="dot"></span>
                            <span class="dot"></span>
                            <span class="dot"></span>
                            <span class="dot"></span>
                        </span>
                    </span>
                </button>
            </div>

            <!-- Cancel -->
            <button @click="closeModal"
                    class="w-full bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded">
                {{ __('Cancel') }}
            </button>
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
            generating: false,
            loginUrl: document.querySelector('meta[name="nostr-login-url"]').content,
            challengeUrl: document.querySelector('meta[name="nostr-challenge-url"]').content,
            csrf: document.querySelector('meta[name="csrf-token"]').content,
            redirectUrl: document.querySelector('meta[name="redirect-after-login"]')?.content || null,

            async open() {
                this.error = '';
                this.privKey = '';
                try {
                    const res = await fetch(this.challengeUrl, { credentials: 'same-origin' });
                    const { challenge } = await res.json();
                    this.challenge = challenge;
                } catch (err) {
                    this.error = 'Failed to fetch challenge.';
                    return;
                }
                this.show = true;
                document.body.classList.add('modal-open');
            },

            closeModal() {
                this.show = false;
                document.body.classList.remove('modal-open');
            },

            buildEvent(pubkey) {
                const event = {
                    kind: 22242,
                    pubkey,
                    created_at: Math.floor(Date.now() / 1000),
                    content: this.challenge,
                    tags: [],
                };
                event.id = window.nostrTools.getEventHash(event);
                return event;
            },

            async loginWithExtension() {
                this.error = '';
                if (!window.nostr?.getPublicKey || !(window.nostr.getSignature || window.nostr.signEvent)) {
                    this.error = 'No Nostr extension detected. Please install Alby or nos2x.';
                    return;
                }

                try {
                    const pubkey = await window.nostr.getPublicKey();
                    let event = this.buildEvent(pubkey);

                    if (window.nostr.getSignature) {
                        event.sig = await window.nostr.getSignature(event);
                    } else {
                        const signed = await window.nostr.signEvent(event);
                        event.sig = signed.sig;
                    }

                    await this.submitEvent(event);
                } catch (err) {
                    console.error(err);
                    this.error = 'Login failed.';
                }
            },

            async loginWithPrivKey() {
                this.error = '';
                let sk = this.privKey.trim();
                if (!sk) return;

                try {
                    if (sk.startsWith('nsec')) {
                        sk = window.nostrTools.nip19.decode(sk).data;
                    }

                    const pubkey = window.nostrTools.getPublicKey(sk);
                    const event = this.buildEvent(pubkey);
                    event.sig = window.nostrTools.getSignature(event, sk);

                    await this.submitEvent(event);
                } catch (err) {
                    console.error(err);
                    this.error = 'Invalid private key.';
                } finally {
                    this.privKey = '';
                }
            },

            async signupWithNewKey() {
                if (this.generating) return;
                this.error = '';
                this.generating = true;
                try {
                    const sk = window.nostrTools.generatePrivateKey();
                    const nsec = window.nostrTools.nip19.nsecEncode
                        ? window.nostrTools.nip19.nsecEncode(sk)
                        : window.nostrTools.nip19.encode({ type: 'nsec', data: sk });
                    StorageClient.setNostrPrivkey(nsec);

                    const pubkey = window.nostrTools.getPublicKey(sk);
                    const event = this.buildEvent(pubkey);
                    event.sig = window.nostrTools.getSignature(event, sk);

                    const random = Math.floor(Math.random() * 100000);
                    const name = `Satscriber #${random}`;
                    await window.publishProfileEvent(sk, name);

                    // Fetch the freshly published profile so the navbar name updates
                    const profile = await window.fetchNostrProfile(pubkey);
                    if (profile) {
                        StorageClient.setNostrProfile(profile);
                        window.updateNostrLogoutLabel(pubkey);
                    }

                    this.redirectUrl = '/profile';
                    await this.submitEvent(event);
                } catch (err) {
                    console.error(err);
                    this.error = 'Signup failed.';
                } finally {
                    this.generating = false;
                }
            },

            async submitEvent(event) {
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
                        window.location.href = this.redirectUrl || window.location.href;
                    } else {
                        this.error = 'Login failed.';
                    }
                } catch (err) {
                    console.error(err);
                    this.error = 'Login failed.';
                }
            }
        };
    }
</script>
