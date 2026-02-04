@extends('layouts.base')

@section('title', __('Trace UTXO - Bitcoin Transaction Input Tracer') . ' – Satscribe')
@section('description', __('Trace Bitcoin UTXOs (Unspent Transaction Outputs) through the blockchain. Follow the flow of satoshis from transaction to transaction.'))

@section('content')
    <section class="px-4 py-6" x-data="traceUtxoComponent()">
        <x-page.header title="{{ __('Trace UTXO') }}" containerClass="max-w-xl">
              <p class="subtitle text-base sm:text-lg leading-relaxed text-gray-700">
                {{ __('Enter a TXID to trace its UTXOs.') }}
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

        <div x-show="trace" x-cloak class="mt-6">
            <div class="border-b mb-4 flex gap-4">
                <button type="button"
                        class="pb-2"
                        :class="tab === 'view' ? 'border-b-2 border-orange-500 text-orange-600 font-medium' : 'text-gray-600'"
                        @click="tab = 'view'">
                    View
                </button>
                <button type="button"
                        class="pb-2"
                        :class="tab === 'raw' ? 'border-b-2 border-orange-500 text-orange-600 font-medium' : 'text-gray-600'"
                        @click="tab = 'raw'">
                    {{ __('Raw') }}
                </button>
            </div>

            <div x-show="tab === 'view'" x-cloak class="space-y-4">
                <template x-if="trace.error">
                    <div class="bg-red-200 text-red-800 p-4 rounded" x-text="trace.error"></div>
                </template>

                <template x-if="trace.utxos">
                    <div class="space-y-4">
                        <template x-for="(item, idx) in trace.utxos" :key="idx">
                            <div class="bg-gray-50 rounded p-4">
                                <h3 class="font-semibold mb-2">UTXO <span x-text="idx + 1"></span></h3>
                                <ul class="ml-4 text-sm space-y-1">
                                    <li>
                                        txid:
                                        <a :href="`https://mempool.space/tx/${item.utxo.txid}`" target="_blank" class="link" x-text="item.utxo.txid"></a>
                                    </li>
                                    <li>vout: <span x-text="item.utxo.vout"></span></li>
                                    <template x-if="item.utxo.scriptpubkey_address">
                                        <li>
                                            address:
                                            <a :href="`https://mempool.space/address/${item.utxo.scriptpubkey_address}`" target="_blank" class="link" x-text="item.utxo.scriptpubkey_address"></a>
                                        </li>
                                    </template>
                                    <li>value: <span x-text="item.utxo.value"></span></li>
                                    <li>
                                        trace:
                                        <template x-for="(ref, j) in item.trace" :key="ref">
                                            <span>
                                                <a :href="`#${ref}`" class="link" x-text="ref"></a><span x-show="j < item.trace.length - 1">, </span>
                                            </span>
                                        </template>
                                    </li>
                                </ul>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="trace.references">
                    <div>
                        <h3 class="font-semibold mt-2 mb-2">References</h3>
                        <div class="space-y-4">
                            <template x-for="(ref, key) in trace.references" :key="key">
<div :id="key" class="bg-gray-50 rounded p-4">
                                    <strong x-text="key"></strong>
                                    <ul class="ml-4 text-sm space-y-1 mt-1">
                                        <li>
                                            txid:
                                            <a :href="`https://mempool.space/tx/${ref.txid}`" target="_blank" class="link" x-text="ref.txid"></a>
                                        </li>
                                        <li>vout: <span x-text="ref.vout"></span></li>
                                        <template x-if="ref.scriptpubkey_address">
                                            <li>
                                                address:
                                                <a :href="`https://mempool.space/address/${ref.scriptpubkey_address}`" target="_blank" class="link" x-text="ref.scriptpubkey_address"></a>
                                            </li>
                                        </template>
                                        <li>value: <span x-text="ref.value"></span></li>
                                        <li>
                                            source:
                                            <template x-for="(src, k) in ref.source" :key="src">
                                                <span>
                                                    <a :href="`#${src}`" class="link" x-text="src"></a><span x-show="k < ref.source.length - 1">, </span>
                                                </span>
                                            </template>
                                        </li>
                                    </ul>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="tab === 'raw'" x-cloak>
                <pre @click="showRaw = !showRaw" :class="showRaw ? '' : 'max-h-[250px] overflow-y-auto cursor-pointer'" class="p-4 bg-gray-100 rounded text-sm">
<code x-text="JSON.stringify(trace, null, 2)"></code>
                </pre>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('traceUtxoComponent', () => ({
        txid: '',
        trace: null,
        loading: false,
        tab: 'view',
        showRaw: false,
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
