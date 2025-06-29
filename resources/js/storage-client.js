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
    getNostrImage() {
        return localStorage.getItem('nostr_image');
    },
    setNostrImage(url) {
        localStorage.setItem('nostr_image', url);
    },
    clearNostrImage() {
        localStorage.removeItem('nostr_image');
    },
};

export default StorageClient;
