import './bootstrap';
import { createIcons, icons } from 'lucide';

document.addEventListener('DOMContentLoaded', () => {
    createIcons({ icons });
    setupFormSubmissionUI();
    setupBlockchainToggle();
    setupDescriptionToggle();
    linkBitcoinEntities('.markdown-content');
});

function setupFormSubmissionUI() {
    const form = document.querySelector('.describe-form');
    const button = document.getElementById('submit-button');
    const icon = document.getElementById('submit-icon');
    const submitBtnInfoStatus = document.getElementById('submit-btn-info-status');
    const text = document.getElementById('submit-text');
    const spinner = document.getElementById('submit-spinner');

    if (!form || !button) return;

    form.addEventListener('submit', () => {
        button.disabled = true;
        icon.style.display = 'none';
        text.textContent = 'Satscribing...';
        submitBtnInfoStatus.textContent = '';
        spinner.style.display = 'inline-block';
    });
}

function setupBlockchainToggle() {
    const rawBlock = document.getElementById('blockchain-data');
    const toggleBtn = document.getElementById('toggle-raw');

    if (!rawBlock || !toggleBtn) return;

    toggleBtn.addEventListener('click', () => {
        const isCollapsed = rawBlock.classList.toggle('collapsed');
        toggleBtn.textContent = isCollapsed ? 'Show more' : 'Show less';
    });
}

function setupDescriptionToggle() {
    document.querySelectorAll('.description-body').forEach(body => {
        const collapsible = body.querySelector('.collapsed-response');
        if (!collapsible) return;

        body.classList.add('cursor-pointer');

        body.addEventListener('click', () => {
            const isCollapsed = !collapsible.classList.toggle('collapsed');
            collapsible.classList.toggle('max-h-[6.5rem]', isCollapsed);
        });
    });
}

function linkBitcoinEntities(containerSelector) {
    const containers = document.querySelectorAll(containerSelector);
    if (!containers.length) return;

    const patterns = getBitcoinPatterns();
    const detectedBlockHashes = new Set();
    containers.forEach(container => {
        const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT, null);
        const textNodes = [];

        while (walker.nextNode()) {
            const node = walker.currentNode;
            if (!node.parentElement.closest('a')) {
                textNodes.push(node);
            }
        }

        for (const node of textNodes) {
            let updatedText = node.nodeValue;

            for (const { regex, link, display, type } of patterns) {
                updatedText = updatedText.replace(regex, (match, ...groups) => {
                    if (type === 'block-hash') {
                        if (match.length === 64) {
                            detectedBlockHashes.add(match);
                        } else {
                            return match;
                        }
                    }

                    if (type === 'tx' && detectedBlockHashes.has(match)) {
                        return match;
                    }

                    const label = display ? display(match, groups[0]) : match;
                    return `<a href="${link(match)}" target="_blank" rel="noopener" class="btc-link">${label}</a>`;
                });
            }

            if (updatedText !== node.nodeValue) {
                const span = document.createElement('span');
                span.innerHTML = updatedText;
                node.replaceWith(span);
            }
        }
    });
}

function getBitcoinPatterns() {
    return [
        {
            type: 'block-hash',
            regex: /\b0{8}[a-f0-9]{56}\b/g,
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
}
