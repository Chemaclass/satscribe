<div
    x-data="invoiceModal()"
    x-init="init()"
    x-show="show"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
>
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

        <div class="relative bg-white rounded-xl p-8 max-w-md w-full shadow-lg">
            <!-- Toast Notification -->
            <div
                x-show="showToast"
                x-transition
                class="fixed top-6 right-6 bg-orange-300 text-white text-sm px-4 py-2 rounded shadow-md"
                style="display: none;"
            >
                Invoice Copied!
            </div>

            <div class="text-center space-y-6">
                <div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-2" x-text="`You’ve used ${maxAttempts} free requests`"></h3>
                    <p class="text-sm text-gray-700 mt-2" x-text="`Consider tipping ${invoice.amount} sats to support its development!`"></p>
                </div>

                <!-- QR Code -->
                <div x-show="invoice?.qr_code_svg" x-transition>
                    <img
                        :src="invoice.qr_code_svg"
                        alt="Lightning Invoice QR"
                        class="w-75 h-75 object-contain mx-auto shadow rounded-lg cursor-pointer"
                        @click="copyInvoice"
                    />
                </div>

                <!-- Invoice with copy button -->
                <div class="flex items-center bg-gray-100 p-3 rounded-lg shadow-sm">
                    <div class="flex-1 overflow-hidden">
                        <p class="text-xs font-mono whitespace-nowrap overflow-hidden text-ellipsis" x-text="invoice.payment_request"></p>
                    </div>
                    <button
                        @click="copyInvoice"
                        class="ml-3 bg-orange-400 text-white text-xs font-semibold px-4 py-1.5 rounded hover:bg-orange-500 transition cursor-pointer"
                    >
                        Copy
                    </button>
                </div>

                <!-- Zap Memo -->
                <div class="text-xs text-gray-500" x-show="invoice.memo">
                    <p x-text="`Memo: ${invoice.memo}`"></p>
                </div>

                <!-- Extra Tip Option -->
                <div class="text-xs text-gray-500 leading-relaxed">
                    <p>
                        Or if you prefer, you can
                        <a href="https://getalby.com/p/chemaclass" target="_blank" class="text-orange-400 hover:underline">
                            create a custom invoice yourself
                        </a>
                    </p>
                </div>

                <!-- Close Button -->
                <div class="pt-2">
                    <button
                        @click="closeModal"
                        class="w-full bg-gray-400 text-white px-4 py-2 rounded-md hover:bg-orange-400 transition cursor-pointer"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

<script>
    function invoiceModal() {
        return {
            show: false,
            invoice: {},
            maxAttempts: 0,
            showToast: false,
            controller: null,
            interval: null,
            attempts: 0,
            maxPollAttempts: 180,

            init() {
                window.addEventListener('rate-limit-reached', (event) => {
                    this.show = true;
                    this.invoice = event.detail.invoice;
                    this.maxAttempts = event.detail.maxAttempts;
                    this.startPolling(this.invoice.identifier);
                });

                window.addEventListener('invoice-paid', () => {
                    this.stopPolling();
                    this.show = false;
                });

                window.addEventListener('paywall-modal-closed', () => {
                    this.stopPolling();
                });
            },

            copyInvoice() {
                navigator.clipboard.writeText(this.invoice.payment_request).then(() => {
                    this.showToast = true;
                    setTimeout(() => this.showToast = false, 2000);
                });
            },

            closeModal() {
                this.show = false;
                window.dispatchEvent(new CustomEvent('paywall-modal-closed'));
            },

            async startPolling(identifier) {
                this.stopPolling(); // Always clear any old polling first

                this.controller = new AbortController();
                this.attempts = 0;

                this.interval = setInterval(async () => {
                    if (!this.show) {
                        this.stopPolling();
                        return;
                    }

                    if (this.attempts >= this.maxPollAttempts) {
                        console.log('Polling timed out.');
                        this.stopPolling();
                        return;
                    }

                    this.attempts++;

                    try {
                        const response = await fetch(`/api/invoice/${identifier}/status`, {
                            signal: this.controller.signal,
                        });
                        const data = await response.json();

                        if (data.paid) {
                            this.stopPolling();
                            confetti({ particleCount: 100, spread: 70, origin: { y: 0.6 } });
                            setTimeout(() => {
                                window.dispatchEvent(new CustomEvent('invoice-paid'));
                            }, 1500);
                        }
                    } catch (error) {
                        if (error.name === 'AbortError') {
                            console.log('Fetch aborted.');
                        } else {
                            console.error('Polling error:', error);
                            this.stopPolling();
                        }
                    }
                }, 1500);
            },

            stopPolling() {
                if (this.interval) {
                    clearInterval(this.interval);
                    this.interval = null;
                }
                if (this.controller) {
                    this.controller.abort();
                    this.controller = null;
                }
            },
        };
    }
</script>
