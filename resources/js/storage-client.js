const StorageClient = {
    getFiatCurrency() {
        return localStorage.getItem('fiat_currency');
    },
    setFiatCurrency(currency) {
        localStorage.setItem('fiat_currency', currency);
    },
    getNostrPubkey() {
        return localStorage.getItem('nostr_pubkey');
    },
    setNostrPubkey(pubkey) {
        localStorage.setItem('nostr_pubkey', pubkey);
    },
    clearNostrPubkey() {
        localStorage.removeItem('nostr_pubkey');
    },
    getNostrPrivkey() {
        return localStorage.getItem('nostr_privkey');
    },
    setNostrPrivkey(privkey) {
        localStorage.setItem('nostr_privkey', privkey);
    },
    clearNostrPrivkey() {
        localStorage.removeItem('nostr_privkey');
    },
    getNostrName() {
        const profile = this.getNostrProfile();
        if (profile) {
            return profile.display_name || profile.name || null;
        }
        return null;
    },
    getNostrImage() {
        const profile = this.getNostrProfile();
        if (profile) {
            return profile.picture || profile.image || null;
        }
        return null;
    },
    getNostrProfile() {
        const json = localStorage.getItem('nostr_profile');
        if (!json) return null;
        try {
            return JSON.parse(json);
        } catch {
            return null;
        }
    },
    setNostrProfile(profile) {
        localStorage.setItem('nostr_profile', JSON.stringify(profile));
    },
    clearNostrProfile() {
        localStorage.removeItem('nostr_profile');
    },
    getRelays() {
        const json = localStorage.getItem('nostr_relays');
        if (!json) return [];
        try {
            const relays = JSON.parse(json);
            return Array.isArray(relays) ? relays : [];
        } catch {
            return [];
        }
    },
    setRelays(relays) {
        localStorage.setItem('nostr_relays', JSON.stringify(relays));
    },
    addRelay(relay) {
        const relays = this.getRelays();
        if (!relays.includes(relay)) {
            relays.push(relay);
            this.setRelays(relays);
        }
    },
    clearNostrRelays() {
        localStorage.removeItem('nostr_relays');
    },
    /**
     * The picked provider/model, as the combined `provider::model` option value.
     * Returns null when the visitor has never chosen, which is different from
     * '' (they explicitly picked "automatic").
     */
    getAiModelChoice() {
        return localStorage.getItem('ai_model_choice');
    },
    setAiModelChoice(choice) {
        localStorage.setItem('ai_model_choice', choice ?? '');
    },
    /**
     * A bring-your-own API key. localStorage is the only place it is ever kept:
     * it is never sent to Satscribe's own storage, never logged, and only
     * leaves the browser in the X-Ai-Api-Key header of an AI request.
     */
    getAiApiKey() {
        return localStorage.getItem('ai_api_key') ?? '';
    },
    setAiApiKey(key) {
        if (!key) {
            this.clearAiApiKey();
            return;
        }
        localStorage.setItem('ai_api_key', key);
    },
    clearAiApiKey() {
        localStorage.removeItem('ai_api_key');
    },
    clearNostr() {
        this.clearNostrPubkey();
        this.clearNostrPrivkey();
        this.clearNostrProfile();
        this.clearNostrRelays();
    }
};

export default StorageClient;
