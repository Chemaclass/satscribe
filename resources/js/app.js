import './bootstrap';
import {createIcons, icons} from 'lucide';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// ---------- DOM READY ----------
document.addEventListener('DOMContentLoaded', () => {
    createIcons({icons});

    setupFormSubmissionUI();
    setupBlockchainToggle();
    setupDescriptionToggle();

    window.refreshThemeIcon = () => {
        const icon = document.getElementById('theme-icon');
        if (icon) {
            createIcons({icons, attrs: {class: icon.getAttribute('class')}});
        }
    };

    window.refreshLucideIcons = () => {
        requestAnimationFrame(() => createIcons({icons}));
    };
});

// ---------- UI SETUP ----------
function setupFormSubmissionUI() {
    const form = document.querySelector('.describe-form');
    const button = document.getElementById('submit-button');
    const icon = document.getElementById('submit-icon');
    const submitText = document.getElementById('submit-text');
    const spinner = document.getElementById('submit-spinner');
    const infoStatus = document.getElementById('submit-btn-info-status');

    if (!form || !button) return;

    form.addEventListener('submit', () => {
        button.disabled = true;
        icon.style.display = 'none';
        submitText.textContent = 'Loading...';
        infoStatus.textContent = '';
        spinner.style.display = 'inline-block';
    });
}

function setupBlockchainToggle() {
    const rawBlock = document.getElementById('blockchain-data');
    const toggleBtn = document.getElementById('toggle-raw');
    if (!rawBlock || !toggleBtn) return;

    toggleBtn.addEventListener('click', () => {
        const collapsed = rawBlock.classList.toggle('collapsed');
        toggleBtn.textContent = collapsed ? 'Show more' : 'Show less';
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

// ---------- RAW DATA HANDLING ----------
const toggleRawBlockVisibility = (button, rawBlock, visible) => {
    rawBlock.style.display = visible ? 'block' : 'none';
    rawBlock.classList.toggle('hidden', !visible);
    button.textContent = visible ? 'Hide raw data' : 'Show raw data';
};

const loadRawData = async (entryId) => {
    const response = await fetch(`/history/${entryId}/raw`);
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
            const data = await response.json();
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
