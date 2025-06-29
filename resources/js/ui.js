import { initIcons, refreshIcons } from './icons';
import { applyNostrAvatarToMessages, updateProfilePage } from './nostr';

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

const toggleRawBlockVisibility = (button, rawBlock, visible) => {
    rawBlock.style.display = visible ? 'block' : 'none';
    rawBlock.classList.toggle('hidden', !visible);

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

async function toggleChatVisibility(button) {
    try {
        const { data } = await axios.post(button.dataset.url);
        const isPublic = data.is_public;

        button.dataset.public = isPublic ? '1' : '0';

        const icon = button.querySelector('[data-lucide]');
        if (icon) {
            const newIcon = isPublic ? 'unlock' : 'lock';
            icon.setAttribute('data-lucide', newIcon);
            icon.setAttribute('aria-label', isPublic ? 'Public chat' : 'Private chat');
        }

        const tooltip = button.querySelector('.tooltip-content');
        if (tooltip) {
            tooltip.textContent = isPublic ? 'Public chat' : 'Private chat';
        }

        window.refreshLucideIcons?.();
    } catch (e) {
        console.error('Failed to toggle visibility', e);
    }
}

function showShareToast() {
    const toast = document.getElementById('share-toast');
    if (!toast) return;

    toast.style.display = 'block';
    requestAnimationFrame(() => {
        toast.classList.remove('opacity-0');
        toast.classList.add('opacity-100');
    });

    setTimeout(() => {
        toast.classList.remove('opacity-100');
        toast.classList.add('opacity-0');
        setTimeout(() => {
            toast.style.display = 'none';
        }, 300);
    }, 2000);
}

async function toggleShare(button) {
    const isShared = button.dataset.shared === '1';

    try {
        await axios.post(button.dataset.url, {
            shared: isShared,
        });
    } catch (e) {
        console.error('Failed to share chat', e);
        return;
    }

    button.dataset.shared = isShared ? '0' : '1';

    const icon = button.querySelector('[data-lucide]');
    if (icon) {
        icon.classList.toggle('text-orange-600', isShared);
        icon.classList.toggle('text-gray-400', !isShared);
        icon.setAttribute('aria-label', isShared ? 'Shared chat' : 'Not shared');
    }

    const tooltip = button.querySelector('.tooltip-content');
    if (tooltip) {
        tooltip.textContent = isShared ? 'Shared chat' : 'Not shared';
    }

    window.refreshLucideIcons?.();

    if (isShared) {
        navigator.clipboard.writeText(button.dataset.link).then(() => {
            showShareToast();
        });
    }
}

function setupEventDelegation() {
    document.addEventListener('click', async (event) => {
        if (event.target.matches('.load-more-btn')) {
            const preBlock = event.target.previousElementSibling;
            preBlock.classList.remove('max-h-[200px]', 'overflow-y-auto');
            event.target.style.display = 'none';
            return;
        }

        const button = event.target.closest('.toggle-history-raw-btn');
        if (button) {
            const targetId = button.dataset.target;
            const entryId = button.dataset.id;
            const rawBlock = document.getElementById(targetId);
            if (!rawBlock) return;

            const isLoaded = rawBlock.dataset.loaded === 'true';
            const isVisible = getComputedStyle(rawBlock).display !== 'none';

            if (!isLoaded) {
                try {
                    const data = await loadRawData(entryId);
                    rawBlock.innerText = JSON.stringify(data, null, 2);
                    rawBlock.dataset.loaded = 'true';
                } catch {
                    rawBlock.innerText = 'Failed to load data.';
                    rawBlock.dataset.loaded = 'true';
                }

                toggleRawBlockVisibility(button, rawBlock, true);
            } else {
                toggleRawBlockVisibility(button, rawBlock, !isVisible);
            }
        }

        const visBtn = event.target.closest('.chat-visibility-btn');
        if (visBtn) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            await toggleChatVisibility(visBtn);
            return;
        }

        const shareBtn = event.target.closest('.share-chat-toggle');
        if (shareBtn) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            await toggleShare(shareBtn);
            return;
        }
    }, true);
}

function interceptFetch() {
    window.addEventListener('load', () => {
        const originalFetch = window.fetch;
        window.fetch = async (...args) => {
            const response = await originalFetch(...args);
            if (response.status === 429) {
                const data = await response.clone().json();
                window.dispatchEvent(new CustomEvent('rate-limit-reached', { detail: data }));
            }
            return response;
        };
    });
}

function setupPaywallModal() {
    document.addEventListener('alpine:init', () => {
        Alpine.data('paywallModal', () => ({
            init() {
                this.$watch('show', (value) => {
                    document.body.classList.toggle('modal-open', value);
                });
            }
        }));
    });

    document.getElementById('customFollowUp')?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            if (window.__PAYWALL_ACTIVE) {
                e.preventDefault();
            }
        }
    });
}

function setupScrollToTop() {
    document.addEventListener('DOMContentLoaded', () => {
        const scrollBtn = document.getElementById('scroll-to-top');
        if (!scrollBtn) return;

        window.addEventListener('scroll', () => {
            if (window.scrollY > 200) {
                scrollBtn.classList.remove('opacity-0', 'pointer-events-none');
                scrollBtn.classList.add('opacity-100');
            } else {
                scrollBtn.classList.add('opacity-0', 'pointer-events-none');
                scrollBtn.classList.remove('opacity-100');
            }
        });

        scrollBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
}

function checkScrollBottom() {
    document.addEventListener('DOMContentLoaded', () => {
        if (sessionStorage.getItem('scrollToBottom') === '1') {
            sessionStorage.removeItem('scrollToBottom');
            window.scrollTo({ top: document.body.scrollHeight, behavior: 'auto' });
        }
    });
}

export function initUI() {
    document.addEventListener('DOMContentLoaded', () => {
        initIcons();
        applyNostrAvatarToMessages();
        updateProfilePage();
        setupBlockchainToggle();
        setupDescriptionToggle();

        window.refreshThemeIcon = () => {
            const icon = document.getElementById('theme-icon');
            if (icon) refreshIcons();
        };

        window.refreshLucideIcons = () => {
            requestAnimationFrame(() => {
                refreshIcons();
                applyNostrAvatarToMessages();
            });
        };
    });

    checkScrollBottom();
    setupEventDelegation();
    interceptFetch();
    setupPaywallModal();
    setupScrollToTop();
}
