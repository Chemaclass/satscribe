import './bootstrap';

function linkBitcoinEntities(containerSelector) {
    const container = document.querySelector(containerSelector);
    if (!container) return;

    const patterns = [
        {
            type: 'block-hash',
            regex: /\b0{10}[a-f0-9]{54}\b/g,
            link: (hash) => `https://mempool.space/block/${hash}`,
        },
        {
            type: 'tx',
            regex: /\b[a-f0-9]{64}\b/g,
            link: (id) => `https://mempool.space/tx/${id}`,
        },
        {
            type: 'block',
            regex: /\bblock\s+#?(\d{3,7})\b/gi,
            link: (n) => `https://mempool.space/block/${n}`,
            display: (match, num) => `block [${num}]`,
        },
        {
            type: 'p2pkh',
            regex: /\b(1[a-km-zA-HJ-NP-Z1-9]{25,34})\b/g,
            link: (addr) => `https://mempool.space/address/${addr}`,
        },
        {
            type: 'p2sh',
            regex: /\b(3[a-km-zA-HJ-NP-Z1-9]{25,34})\b/g,
            link: (addr) => `https://mempool.space/address/${addr}`,
        },
        {
            type: 'p2wpkh_or_p2wsh',
            regex: /\b(bc1q[a-z0-9]{11,87})\b/g,
            link: (addr) => `https://mempool.space/address/${addr}`,
        },
        {
            type: 'p2tr',
            regex: /\b(bc1p[a-z0-9]{11,87})\b/g,
            link: (addr) => `https://mempool.space/address/${addr}`,
        }
    ];

    // To track block hashes so tx doesn't re-link them
    const detectedBlockHashes = new Set();

    // Parse DOM safely and preserve tags
    const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT, null);
    const textNodes = [];

    while (walker.nextNode()) {
        const node = walker.currentNode;
        if (!node.parentElement.closest('a')) {
            textNodes.push(node);
        }
    }

    for (const node of textNodes) {
        let replaced = node.nodeValue;

        for (const { regex, link, display, type } of patterns) {
            replaced = replaced.replace(regex, (match, ...groups) => {
                // Block hash validation
                if (type === 'block-hash') {
                    if (match.length === 64) {
                        detectedBlockHashes.add(match);
                    } else {
                        return match;
                    }
                }

                // Skip linking block hashes again as tx
                if (type === 'tx' && detectedBlockHashes.has(match)) {
                    return match;
                }

                const label = display ? display(match, groups[0]) : match;
                return `<a href="${link(match)}" target="_blank" rel="noopener" class="btc-link">${label}</a>`;
            });
        }

        if (replaced !== node.nodeValue) {
            const span = document.createElement('span');
            span.innerHTML = replaced;
            node.replaceWith(span);
        }
    }
}

linkBitcoinEntities('.description-result .markdown-content');
