import './bootstrap';

function linkBitcoinEntities(containerSelector) {
    const container = document.querySelector(containerSelector);
    if (!container) return;

    // Regex patterns for BTC data
    const patterns = [
        {
            type: 'tx',
            regex: /\b([a-f0-9]{64})\b/g,
            link: (id) => `https://mempool.space/tx/${id}`,
        },
        {
            type: 'block',
            regex: /\bblock\s+#?(\d{3,7})\b/gi,
            link: (n) => `https://mempool.space/block/${n}`,
            display: (match, num) => `block [${num}]`,
        },
        {
            type: 'p2pkh', // Legacy: starts with 1
            regex: /\b(1[a-km-zA-HJ-NP-Z1-9]{25,34})\b/g,
            link: (addr) => `https://mempool.space/address/${addr}`,
        },
        {
            type: 'p2sh', // Script-based: starts with 3
            regex: /\b(3[a-km-zA-HJ-NP-Z1-9]{25,34})\b/g,
            link: (addr) => `https://mempool.space/address/${addr}`,
        },
        {
            type: 'p2wpkh_or_p2wsh', // SegWit v0: bc1q prefix
            regex: /\b(bc1q[a-z0-9]{11,87})\b/g,
            link: (addr) => `https://mempool.space/address/${addr}`,
        },
        {
            type: 'p2tr', // Taproot: bc1p prefix
            regex: /\b(bc1p[a-z0-9]{11,87})\b/g,
            link: (addr) => `https://mempool.space/address/${addr}`,
        }
    ];

    let html = container.innerHTML;

    for (const {regex, link, display} of patterns) {
        html = html.replace(regex, (match, group) => {
            const href = link(group);
            const label = display ? display(match, group) : group;
            return `<a href="${href}" target="_blank" rel="noopener" class="btc-link">${label}</a>`;
        });
    }

    container.innerHTML = html;
}

linkBitcoinEntities('.description-result .markdown-content');
