<div
    x-data="{
        show: false,
        invoice: {},
        maxAttempts: 0,
        showToast: false,
        copyInvoice() {
            navigator.clipboard.writeText(this.invoice.payment_request).then(() => {
                this.showToast = true;
                setTimeout(() => this.showToast = false, 2000);
            });
        }
    }"
    x-show="show"
    x-on:rate-limit-reached.window="
        show = true;
        invoice = $event.detail.invoice;
        maxAttempts = $event.detail.maxAttempts;
    "
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
>
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

        <div class="relative bg-white rounded-xl p-8 max-w-md w-full shadow-lg">
            <!-- Toast Notification -->
            <div
                x-show="showToast"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-2"
                class="fixed top-6 right-6 bg-orange-300 text-white text-sm px-4 py-2 rounded shadow-md"
                style="display: none;"
            >
                Invoice Copied!
            </div>

            <div class="text-center space-y-6">
                <div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">
                        <span x-transition x-text="`You’ve used ${maxAttempts} free requests`"></span>
                    </h3>

                    <div class="text-sm text-gray-700 leading-relaxed">
                        <p x-transition class="mt-2" x-text="`Consider tipping ${invoice.amount} sats to support its development!`"></p>
                    </div>
                </div>

                <!-- QR Code -->
                <div x-show="invoice && invoice.qr_code_svg" x-transition>
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
                    <p x-transition x-text="`Memo: ${invoice.memo}`"></p>
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
                        @click="show = false"
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
function checkInvoiceStatus(identifier) {
    let attempts = 0;
    const maxAttempts = 180; // 3 minutes max

    let checkInterval = setInterval(async () => {
        if (attempts >= maxAttempts) {
            clearInterval(checkInterval);
            console.log('Payment check timed out');
            return;
        }

        attempts++;

        try {
            const response = await fetch(`/api/invoice/${identifier}/status`);
            const data = await response.json();

            if (data.paid) {
                clearInterval(checkInterval);

                confetti({
                    particleCount: 100,
                    spread: 70,
                    origin: { y: 0.6 }
                });

                setTimeout(() => {
                    // Assuming you're using Alpine.js for the modal
                    Alpine.store('paywall').show = false;
                }, 1500);
            }
        } catch (error) {
            console.error('Error checking invoice status:', error);
            clearInterval(checkInterval);
        }
    }, 1000); // Check every second

    // Store the interval ID to clear it when the modal is closed
    window.currentCheckInterval = checkInterval;
}

// Add this to your modal's x-init or where you display the invoice
document.addEventListener('invoice-created', (event) => {
    const identifier = event.detail.identifier;
    checkInvoiceStatus(identifier);
});

// Clean up interval when modal is closed
document.addEventListener('paywall-modal-closed', () => {
    if (window.currentCheckInterval) {
        clearInterval(window.currentCheckInterval);
    }
});
</script>
