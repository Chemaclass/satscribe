import './bootstrap';
import Alpine from 'alpinejs';
import StorageClient from './storage-client';
import {
    BadgeCheck,
    Bitcoin,
    Bot,
    ChevronLeft,
    ChevronRight,
    createIcons,
    Github,
    Lightbulb,
    Loader2,
    Moon,
    Scroll,
    Send,
    Shuffle,
    SlidersHorizontal,
    Sun,
    User,
    Zap,
    ArrowUp,
    Scissors,
    Laptop,
    Lock,
    ExternalLink,
} from 'lucide';

window.Alpine = Alpine;
window.StorageClient = StorageClient;
Alpine.start();

const usedIcons = {
    ChevronLeft,
    ChevronRight,
    Bitcoin,
    Bot,
    Loader2,
    Lightbulb,
    Scroll,
    Sun,
    Moon,
    Github,
    SlidersHorizontal,
    Zap,
    Shuffle,
    User,
    Send,
    BadgeCheck,
    ArrowUp,
    Scissors,
    Laptop,
    Lock,
    ExternalLink,
};

createIcons({icons: usedIcons});

// ---------- DOM READY ----------
document.addEventListener('DOMContentLoaded', () => {
    createIcons({icons: usedIcons});

    setupBlockchainToggle();
    setupDescriptionToggle();

    window.refreshThemeIcon = () => {
        const icon = document.getElementById('theme-icon');
        if (icon) {
            createIcons({icons, attrs: {class: icon.getAttribute('class')}});
        }
    };

    window.refreshLucideIcons = () => {
        requestAnimationFrame(() => createIcons({icons: usedIcons}));
    };
});

// ---------- UI SETUP ----------
function setupBlockchainToggle() {
    const rawBlock = document.getElementById('blockchain-data');
    const toggleBtn = document.getElementById('toggle-raw');
    if (!rawBlock || !toggleBtn) return;

    toggleBtn.addEventListener('click', () => {
        const collapsed = rawBlock.classList.toggle('collapsed');
        toggleBtn.textContent = collapsed ? window.i18n.showMore : window.i18n.showLess;
    });
}

function setupDescriptionToggle() {
    document.querySelectorAll('.chat-body').forEach(body => {
        const collapsible = body.querySelector('.collapsed-response');
        if (!collapsible) return;

        body.classList.add('cursor-pointer');
        body.addEventListener('click', () => {
            const isCollapsed = !collapsible.classList.toggle('collapsed');
            collapsible.classList.toggle('max-h-[6.5rem]', isCollapsed);
        });
    });
}

// ---------- RAW DATA HANDLING ----------
const toggleRawBlockVisibility = (button, rawBlock, visible) => {
    rawBlock.style.display = visible ? 'block' : 'none';
    rawBlock.classList.toggle('hidden', !visible);

    // Update the text inside both spans inside the button
    const fullSpan = button.querySelector('.full-label');
    const shortSpan = button.querySelector('.short-label');

    if (fullSpan) fullSpan.textContent = visible ? window.i18n.hideRawData : window.i18n.showRawData;
    if (shortSpan) shortSpan.textContent = visible ? window.i18n.hide : window.i18n.raw;
};

const loadRawData = async (messageId) => {
    const response = await fetch(`/history/${messageId}/raw`);
    if (!response.ok) throw new Error('Failed to fetch raw data');
    return await response.json();
};

// ---------- EVENT DELEGATION ----------
document.addEventListener('click', async (event) => {
    // Expand .load-more-btn content
    if (event.target.matches('.load-more-btn')) {
        const preBlock = event.target.previousElementSibling;
        preBlock.classList.remove('max-h-[200px]', 'overflow-y-auto');
        event.target.style.display = 'none';
        return;
    }

    // Handle raw JSON toggle
    const button = event.target.closest('.toggle-history-raw-btn');
    if (button) {
        const targetId = button.dataset.target;
        const entryId = button.dataset.id;
        const rawBlock = document.getElementById(targetId);
        if (!rawBlock) return;

        const isLoaded = rawBlock.dataset.loaded === "true";
        const isVisible = getComputedStyle(rawBlock).display !== 'none';

        if (!isLoaded) {
            try {
                const data = await loadRawData(entryId);
                rawBlock.innerText = JSON.stringify(data, null, 2);
                rawBlock.dataset.loaded = "true";
            } catch {
                rawBlock.innerText = "Failed to load data.";
                rawBlock.dataset.loaded = "true";
            }

            toggleRawBlockVisibility(button, rawBlock, true);
        } else {
            toggleRawBlockVisibility(button, rawBlock, !isVisible);
        }
    }
});

// ---------- FETCH INTERCEPT ----------
window.addEventListener('load', () => {
    const originalFetch = window.fetch;
    window.fetch = async (...args) => {
        const response = await originalFetch(...args);
        if (response.status === 429) {
            const data = await response.clone().json();
            window.dispatchEvent(new CustomEvent('rate-limit-reached', {detail: data}));
        }
        return response;
    };
});

// ---------- PAYWALL MODAL ----------
document.addEventListener('alpine:init', () => {
    Alpine.data('paywallModal', () => ({
        init() {
            this.$watch('show', (value) => {
                document.body.classList.toggle('modal-open', value);
            });
        }
    }));
});

document.getElementById('customFollowUp')?.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
        if (window.__PAYWALL_ACTIVE) {
            e.preventDefault();
        }
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const scrollBtn = document.getElementById('scroll-to-top');

    // Show/hide the button based on scroll position
    window.addEventListener('scroll', () => {
        if (window.scrollY > 200) {
            scrollBtn.classList.remove('opacity-0', 'pointer-events-none');
            scrollBtn.classList.add('opacity-100');
        } else {
            scrollBtn.classList.add('opacity-0', 'pointer-events-none');
            scrollBtn.classList.remove('opacity-100');
        }
    });

    // On click, scroll up smoothly
    scrollBtn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});
