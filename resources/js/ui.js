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

    // Toggle button text
    const fullSpan = button.querySelector('.full-label');
    const shortSpan = button.querySelector('.short-label');
    if (fullSpan) fullSpan.textContent = visible ? window.i18n.hideRawData : window.i18n.showRawData;
    if (shortSpan) shortSpan.textContent = visible ? window.i18n.hide : window.i18n.raw;

    // Toggle icons
    const iconShow = button.querySelector('.icon-show');
    const iconHide = button.querySelector('.icon-hide');
    if (iconShow) iconShow.classList.toggle('hidden', visible);
    if (iconHide) iconHide.classList.toggle('hidden', !visible);

    // Toggle active state on button
    button.classList.toggle('active', visible);
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
    const currentlyShared = button.dataset.shared === '1';
    const newShared = !currentlyShared;

    try {
        await axios.post(button.dataset.url, {
            shared: newShared,
        });
    } catch (e) {
        console.error('Failed to share chat', e);
        return;
    }

    button.dataset.shared = newShared ? '1' : '0';

    const icon = button.querySelector('[data-lucide]');
    if (icon) {
        icon.classList.toggle('text-orange-600', newShared);
        icon.classList.toggle('text-gray-400', !newShared);
        icon.setAttribute('aria-label', newShared ? 'Shared chat' : 'Not shared');
    }

    const tooltip = button.querySelector('.tooltip-content');
    if (tooltip) {
        tooltip.textContent = newShared ? 'Shared chat' : 'Not shared';
    }

    window.refreshLucideIcons?.();

    if (newShared) {
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

            // Find the content element (nested pre) or fall back to the container itself
            const contentEl = rawBlock.querySelector('.raw-data-content') || rawBlock;

            const isLoaded = rawBlock.dataset.loaded === 'true';
            const isVisible = getComputedStyle(rawBlock).display !== 'none';

            if (!isLoaded) {
                try {
                    const data = await loadRawData(entryId);
                    contentEl.innerText = JSON.stringify(data, null, 2);
                    rawBlock.dataset.loaded = 'true';
                } catch {
                    contentEl.innerText = 'Failed to load data.';
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

        const refreshBtn = document.getElementById('profile-refresh');
        if (refreshBtn) {
            const original = refreshBtn.innerHTML;

            refreshBtn.classList.add("flex", "items-center", "gap-1"); // ensures horizontal alignment and spacing

            refreshBtn.addEventListener("click", async () => {
                refreshBtn.disabled = true;
                refreshBtn.innerHTML = `<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i><span>Refreshing...</span>`;
                refreshIcons();

                await updateProfilePage(true);

                refreshBtn.innerHTML = `<i data-lucide="badge-check" class="w-4 h-4"></i><span>Refreshed</span>`;
                refreshIcons();

                setTimeout(() => {
                    refreshBtn.innerHTML = original;
                    refreshBtn.disabled = false;
                    refreshIcons();
                }, 1500);
            });
        }


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
