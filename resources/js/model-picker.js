import StorageClient from './storage-client';

/**
 * Mirrors App\View\Components\ModelPicker::CHOICE_SEPARATOR — one `<option>`
 * value carries both halves of the selection so it round-trips through
 * localStorage as a single string.
 */
const CHOICE_SEPARATOR = '::';

/** Used before the panel is visible and can be measured. */
const ESTIMATED_PANEL_HEIGHT = 320;
const PANEL_GAP = 12;

/**
 * Splits a picker choice back into the two fields the server expects. An empty
 * choice means "automatic": both fields stay blank and the request is byte for
 * byte what it was before model selection existed.
 */
const splitChoice = (choice) => {
    const index = (choice ?? '').indexOf(CHOICE_SEPARATOR);

    if (index === -1) {
        return { provider: '', model: '' };
    }

    return {
        provider: choice.slice(0, index),
        model: choice.slice(index + CHOICE_SEPARATOR.length),
    };
};

/**
 * Alpine component behind <x-model-picker/>.
 *
 * The key is only ever held here and in localStorage. It is never logged, never
 * written to a data attribute, never put in the URL, and never added to a
 * request body — it leaves exclusively through the X-Ai-Api-Key header.
 */
export const modelPicker = (config = {}) => ({
    open: false,
    dropUp: false,
    balance: 0,
    packMessages: 0,
    packSats: 0,
    packInvoice: null,
    buying: false,
    copiedInvoice: false,
    packPoll: null,
    choice: '',
    apiKey: '',
    label: '',
    keyErrorShown: false,
    defaultChoice: config.defaultChoice ?? '',
    requiresKeyByChoice: config.requiresKey ?? {},
    premiumByChoice: config.premium ?? {},
    loggedIn: config.loggedIn === true,
    labels: config.labels ?? {},

    init() {
        const stored = StorageClient.getAiModelChoice();

        // A stored choice that is no longer in the registry (a model was
        // dropped) must not silently keep being sent — fall back to the default.
        this.choice = stored !== null && this.isKnownChoice(stored) ? stored : this.defaultChoice;
        this.apiKey = StorageClient.getAiApiKey();

        this.$nextTick(() => this.syncLabel());

        window.AiModelPicker = this.publicApi();
    },

    isKnownChoice(choice) {
        return choice === '' || Object.prototype.hasOwnProperty.call(this.requiresKeyByChoice, choice);
    },

    /** True when the selected model is one this deployment pays for. */
    get isPremiumChoice() {
        return this.premiumByChoice[this.choice] === true;
    },

    /** Translated server-side; only the count is filled in here. */
    get balanceLabel() {
        return (this.labels.balance ?? ':count').replace(':count', String(this.balance));
    },

    get requiresKey() {
        return this.requiresKeyByChoice[this.choice] === true;
    },

    get missingKey() {
        return this.requiresKey && this.apiKey.trim() === '';
    },

    get showKeyError() {
        return this.keyErrorShown && this.missingKey;
    },

    toggle() {
        if (!this.open) {
            this.updatePlacement();
        }

        this.open = !this.open;

        if (this.open) {
            window.refreshLucideIcons?.();
            // The panel has no height until it is shown, so the first estimate
            // uses a fallback and this second pass corrects it before paint.
            this.$nextTick(() => this.updatePlacement());
        }
    },

    /**
     * Opens the panel upward when it would otherwise run off the bottom of the
     * viewport. On the home page the trigger sits just above the composer, so
     * dropping downward cut off the API key field entirely.
     */
    updatePlacement() {
        const trigger = this.$refs.trigger;

        if (!trigger) {
            return;
        }

        const rect = trigger.getBoundingClientRect();
        const measured = this.$refs.panel?.offsetHeight ?? 0;
        const needed = (measured > 0 ? measured : ESTIMATED_PANEL_HEIGHT) + PANEL_GAP;
        const spaceBelow = window.innerHeight - rect.bottom;

        this.dropUp = spaceBelow < needed && rect.top > spaceBelow;
    },

    /**
     * Premium balance is only fetched when a premium model is actually chosen,
     * so the common case costs no request.
     */
    async refreshBalance() {
        if (!this.isPremiumChoice || !this.loggedIn) return;

        try {
            const response = await fetch('/api/premium/balance');
            const data = await response.json();

            this.balance = data.balance ?? 0;
            this.packMessages = data.pack_messages ?? 0;
            this.packSats = data.pack_sats ?? 0;
        } catch {
            // A failed balance check must not block picking a model.
        }
    },

    async buyPack() {
        if (this.buying) return;

        this.buying = true;

        try {
            const response = await fetch('/api/premium/pack', {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''},
            });

            if (!response.ok) return;

            const data = await response.json();
            this.packInvoice = data.invoice ?? null;

            this.pollPack();
        } finally {
            this.buying = false;
        }
    },

    /**
     * The buyer's own poll is what credits them; the Alby webhook is a fallback
     * that may be slow or never arrive.
     */
    pollPack() {
        const hash = this.packInvoice?.payment_hash;
        if (!hash) return;

        clearInterval(this.packPoll);

        this.packPoll = setInterval(async () => {
            try {
                const response = await fetch(`/api/premium/pack/${encodeURIComponent(hash)}/status`);
                const data = await response.json();

                if (data.paid) {
                    this.balance = data.balance ?? this.balance;
                    this.packInvoice = null;
                    clearInterval(this.packPoll);
                }
            } catch {
                clearInterval(this.packPoll);
            }
        }, 2000);
    },

    copyInvoice() {
        const request = this.packInvoice?.payment_request;
        if (!request) return;

        navigator.clipboard.writeText(request).then(() => {
            this.copiedInvoice = true;
            setTimeout(() => this.copiedInvoice = false, 2000);
        });
    },

    onChoiceChange() {
        StorageClient.setAiModelChoice(this.choice);
        this.keyErrorShown = false;
        this.packInvoice = null;
        this.syncLabel();
        this.refreshBalance();
    },

    onKeyInput() {
        StorageClient.setAiApiKey(this.apiKey.trim());
        this.keyErrorShown = false;
    },

    forgetKey() {
        this.apiKey = '';
        StorageClient.clearAiApiKey();
        this.keyErrorShown = false;
    },

    /**
     * The trigger shows the bare model name; the badges only belong in the
     * open list. `data-short` is rendered by the Blade template so the label
     * never has to be reassembled here.
     */
    syncLabel() {
        const selected = this.$refs.select?.selectedOptions?.[0];

        this.label = selected?.dataset?.short ?? selected?.textContent?.trim() ?? '';
    },

    /**
     * The imperative surface the streaming code calls. Kept as closures over
     * the Alpine proxy so a caller can never read the key off a plain object it
     * might then log.
     */
    publicApi() {
        return {
            selectionFields: () => splitChoice(this.choice),

            applyToHeaders: (headers) => {
                const key = this.apiKey.trim();

                if (key !== '') {
                    headers['X-Ai-Api-Key'] = key;
                }

                return headers;
            },

            /**
             * True when the request must not be sent. Opens the panel and points
             * at the empty key field, so the visitor is told what is missing
             * instead of watching the stream fail.
             */
            blockSubmit: () => {
                if (!this.missingKey) {
                    return false;
                }

                this.updatePlacement();
                this.open = true;
                this.keyErrorShown = true;
                this.$nextTick(() => {
                    this.updatePlacement();
                    window.refreshLucideIcons?.();
                    this.$refs.keyInput?.focus();
                });

                return true;
            },
        };
    },
});

export default modelPicker;
