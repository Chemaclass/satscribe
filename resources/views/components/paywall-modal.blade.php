<!-- resources/views/components/paywall-modal.blade.php -->
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

        <div class="relative bg-white rounded-lg p-8 max-w-md w-full">
            <!-- Toast Notification -->
            <div
                x-show="showToast"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-2"
                class="fixed top-6 right-6 bg-orange-400 text-white text-sm px-4 py-2 rounded shadow-md"
                style="display: none;"
            >
                Invoice Copied!
            </div>

            <div class="text-center">
                <h3 class="text-xl font-semibold mb-4">Rate Limit Reached</h3>

                <div class="mb-4 text-sm text-gray-700">
                    <p x-transition x-text="`You've already done ${maxAttempts} free requests.`"></p>
                    <p x-transition x-text="`If you find Satscribe helpful, consider tipping ${invoice.amount} sats to support its development!`"></p>
                </div>

                <!-- QR Code -->
                <div
                    class="flex justify-center mb-6"
                    x-show="invoice && invoice.qr_code_svg"
                    x-transition
                >
                    <img :src="invoice.qr_code_svg" alt="Lightning Invoice QR" class="w-70 h-70 object-contain" />
                </div>

                <!-- Invoice with copy button -->
                <div class="flex items-center bg-gray-100 p-3 rounded mb-6">
                    <div class="flex-1 overflow-hidden">
                        <p class="text-xs font-mono whitespace-nowrap overflow-hidden text-ellipsis" x-text="invoice.payment_request"></p>
                    </div>
                    <button
                        @click="copyInvoice"
                        class="ml-2 bg-orange-400 text-white text-xs px-3 py-1 rounded hover:bg-orange-500"
                    >
                        Copy
                    </button>
                </div>

                <div class="mb-4 text-sm text-gray-700">
                    <p x-transition x-text="`Invoice Memo: ${invoice.memo}`"></p>
                </div>

                <button
                    @click="show = false"
                    class="bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-500"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
