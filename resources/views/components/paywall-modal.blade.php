<!-- resources/views/components/paywall-modal.blade.php -->
<div
    x-data="{
        show: false,
        invoice: null
    }"
    x-show="show"
    x-on:rate-limit-reached.window="
        show = true;
        invoice = $event.detail.invoice;
    "
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
>
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

        <div class="relative bg-white rounded-lg p-8 max-w-md w-full">
            <div class="text-center">
                <h3 class="text-xl font-semibold mb-4">Rate Limit Reached</h3>
                <p class="mb-4">You've reached the limit of 5 requests per hour. Support our service by tipping some sats!</p>

                <!-- Correctly render the Lightning Invoice -->
                <div class="bg-gray-100 p-4 rounded mb-4">
                    <template x-if="invoice && invoice.payment_request">
                        <p class="text-sm font-mono break-all" x-text="invoice.payment_request"></p>
                    </template>
                    <template x-if="!invoice">
                        <p class="text-sm text-gray-500">Loading invoice...</p>
                    </template>
                </div>

                <button
                    @click="show = false"
                    class="bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
