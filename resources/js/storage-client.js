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
    getNostrName() {
        return localStorage.getItem('nostr_name');
    },
    setNostrName(name) {
        localStorage.setItem('nostr_name', name);
    },
    clearNostrName() {
        localStorage.removeItem('nostr_name');
    },
};

export default StorageClient;
