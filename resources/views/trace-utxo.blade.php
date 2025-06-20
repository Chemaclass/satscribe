@extends('layouts.base')

@section('title', __('Trace UTXO'))

@section('content')
    <section class="px-4 py-6" x-data="traceUtxoComponent()">
        <x-page.header title="{{ __('Trace UTXO') }}" containerClass="max-w-xl">
            <p class="subtitle text-base sm:text-lg leading-relaxed text-gray-700 dark:text-gray-300">
                {{ __('Enter a transaction ID to trace its UTXOs.') }}
            </p>
        </x-page.header>

        <form class="max-w-xl space-y-4" @submit.prevent="submit">
            <input type="text" x-model="txid" placeholder="{{ __('txid') }}" class="form-input w-full" autocomplete="off" />
            <button type="submit" class="form-button w-full" :disabled="loading">
                {{ __('Trace') }}
            </button>
            <div x-show="loading" x-cloak class="flex justify-center mt-2">
                <span class="dots-loader">
                    <span class="dot"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                </span>
            </div>
        </form>

        <pre x-show="trace" class="mt-6 p-4 bg-gray-100 dark:bg-gray-800 rounded overflow-auto text-sm">
<code x-text="JSON.stringify(trace, null, 2)"></code>
        </pre>
    </section>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('traceUtxoComponent', () => ({
        txid: '',
        trace: null,
        loading: false,
        async submit() {
            if (!this.txid.trim() || this.loading) return;
            this.loading = true;
            this.trace = null;
            try {
                const { data } = await axios.get(`/api/trace-utxo/${encodeURIComponent(this.txid)}`);
                this.trace = data;
            } catch (e) {
                this.trace = { error: e.response?.data?.error || 'Request failed' };
            } finally {
                this.loading = false;
            }
        }
    }));
});
</script>
@endpush
