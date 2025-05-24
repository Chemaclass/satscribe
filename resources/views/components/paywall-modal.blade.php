<div
    x-data="invoiceModal()"
    x-init="init()"
    x-show="show"
    @keydown.escape.window="closeModal"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 backdrop-blur-sm"
    style="display: none;"
    x-cloak
>
    <!-- Modal Content -->
    <div
        class="relative w-full max-w-md p-8 rounded-2xl shadow-2xl bg-gray-800 dark:bg-gray-900 border border-gray-700"
        @click.away="closeModal"
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
    >

        <!-- Toast Notification -->
        <div
            x-show="showToast"
            x-transition
            class="fixed top-6 right-6 bg-orange-400 text-white text-sm px-4 py-2 rounded shadow-lg z-50"
            style="display: none;"
        >
            Invoice Copied!
        </div>

        <!-- Main content -->
        <div class="text-center space-y-6">
            <!-- Title -->
            <h3 class="text-3xl font-bold text-white mb-2" x-text="`You’ve used ${maxAttempts} free requests`"></h3>

            <!-- Subtitle -->
            <p class="text-sm text-gray-400" x-text="`Consider tipping ${invoice.amount} sats to support development!`"></p>

            <!-- QR Code -->
            <div x-show="invoice?.qr_code_svg" x-transition class="flex justify-center">
                <img
                    :src="invoice.qr_code_svg"
                    alt="Lightning Invoice QR"
                    class="w-64 h-64 object-contain rounded-lg shadow-lg bg-white p-2 ring-1 ring-gray-300 cursor-pointer"
                    @click="copyInvoice"
                />
            </div>

            <!-- Invoice with Copy Button -->
            <div class="flex items-center bg-gray-700 p-3 rounded-lg shadow-inner">
                <div class="flex-1 overflow-hidden">
                    <p class="text-xs font-mono text-gray-300 whitespace-nowrap overflow-hidden text-ellipsis" x-text="invoice.payment_request"></p>
                </div>
                <button
                    @click="copyInvoice"
                    class="ml-3 bg-orange-500 hover:bg-orange-600 text-white text-xs font-semibold px-4 py-1.5 rounded transition"
                >
                    Copy
                </button>
            </div>

            <!-- Memo -->
            <div class="text-xs text-gray-500 mt-2" x-show="invoice.memo">
                <p x-text="`Memo: ${invoice.memo}`"></p>
            </div>

            <!-- Close Button -->
            <div class="pt-4">
                <button
                    @click="closeModal"
                    class="w-full bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded-md transition font-semibold"
                >
                    Close
                </button>
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
                    window.__PAYWALL_ACTIVE = true;
                    this.show = true;
                    this.invoice = event.detail.invoice;
                    this.maxAttempts = event.detail.maxAttempts;
                    document.body.classList.add('modal-open');
                    this.startPolling(this.invoice.identifier);
                });

                window.addEventListener('invoice-paid', () => {
                    window.__PAYWALL_ACTIVE = false;
                    this.stopPolling();
                    this.show = false;
                    document.body.classList.remove('modal-open');
                    window.retryLastPaidRequest?.();
                });

                window.addEventListener('paywall-modal-closed', () => {
                    window.__PAYWALL_ACTIVE = false;
                    this.stopPolling();
                    document.body.classList.remove('modal-open');
                });
            },

            copyInvoice() {
                navigator.clipboard.writeText(this.invoice.payment_request).then(() => {
                    this.showToast = true;
                    setTimeout(() => this.showToast = false, 2000);
                });
            },

            closeModal() {
                window.__PAYWALL_ACTIVE = false;
                this.show = false;
                window.dispatchEvent(new CustomEvent('paywall-modal-closed'));
                document.querySelectorAll('.loading-spinner-group').forEach(el => {
                    el.innerHTML = `
                   <div class="flex items-center gap-1 group relative">
                        <i data-lucide="bot" class="w-6 h-6 font-semibold"></i>
                        <span class="font-semibold">Scribe</span>
                    </div>
                    <div class="inline-block rounded px-3 py-2 text-orange-700">
                        Request cancelled.
                    </div>
                    `;
                    el.classList.remove('loading-spinner-group');
                });
                window.refreshLucideIcons?.();
            },

            async startPolling(identifier) {
                this.stopPolling();

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
                        if (error.name !== 'AbortError') {
                            console.error('Polling error:', error);
                        }
                        this.stopPolling();
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
