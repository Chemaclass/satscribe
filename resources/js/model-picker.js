import StorageClient from './storage-client';

/**
 * Mirrors App\View\Components\ModelPicker::CHOICE_SEPARATOR — one `<option>`
 * value carries both halves of the selection so it round-trips through
 * localStorage as a single string.
 */
const CHOICE_SEPARATOR = '::';

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
    choice: '',
    apiKey: '',
    label: '',
    keyErrorShown: false,
    defaultChoice: config.defaultChoice ?? '',
    requiresKeyByChoice: config.requiresKey ?? {},

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
        this.open = !this.open;

        if (this.open) {
            window.refreshLucideIcons?.();
        }
    },

    onChoiceChange() {
        StorageClient.setAiModelChoice(this.choice);
        this.keyErrorShown = false;
        this.syncLabel();
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

                this.open = true;
                this.keyErrorShown = true;
                this.$nextTick(() => {
                    window.refreshLucideIcons?.();
                    this.$refs.keyInput?.focus();
                });

                return true;
            },
        };
    },
});

export default modelPicker;
