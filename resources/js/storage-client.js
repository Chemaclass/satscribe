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
    removeRelay(relay) {
        const relays = this.getRelays().filter(r => r !== relay);
        this.setRelays(relays);
    },
};

export default StorageClient;
