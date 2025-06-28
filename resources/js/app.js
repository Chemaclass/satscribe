import './bootstrap';
import Alpine from 'alpinejs';
import StorageClient from './storage-client';
import { relayInit } from 'nostr-tools';
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
    X,
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
    X,
};

createIcons({icons: usedIcons});

async function fetchNostrProfile(pubkey) {
    try {
        console.log('Fetching nostr profile', pubkey);

        const relay = relayInit('wss://relay.damus.io');
        await relay.connect();
        console.log('Connected to relay');
        return await new Promise((resolve) => {
            console.log('Subscribing to relay');
            const sub = relay.sub([{ kinds: [0], authors: [pubkey], limit: 1 }]);
            let done = false;
            const finalize = (val) => {
                if (done) return;
                done = true;
                sub.unsub();
                relay.close();
                resolve(val);
            };
            sub.on('event', (ev) => {
                try {
                    console.log('Got event', ev);
                    const meta = JSON.parse(ev.content);
                    console.log('Got meta', meta);
                    finalize(meta.display_name || meta.name || null);
                } catch {
                    console.error('Failed to parse metadata');
                    finalize(null);
                }
            });
            sub.on('eose', () => finalize(null));
            setTimeout(() => finalize(null), 5000);
        });
    } catch (e) {
        console.error('Failed to fetch metadata', e);
        return null;
    }
}

async function updateNostrLogoutLabel(pubkey) {
    let name = StorageClient.getNostrName();
    if (!name) {
        name = await fetchNostrProfile(pubkey);
        if (name) {
            StorageClient.setNostrName(name);
        }
    }
    if (name) {
        const label = document.getElementById('nostr-logout-label');
        if (label) {
            label.textContent = `${name} Logout`;
        }
    }
}

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

document.addEventListener('DOMContentLoaded', () => {
    if (sessionStorage.getItem('scrollToBottom') === '1') {
        sessionStorage.removeItem('scrollToBottom');
        window.scrollTo({ top: document.body.scrollHeight, behavior: 'auto' });
    }
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

// ---------- NOSTR LOGIN ----------
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const pubkeyMeta = document.querySelector('meta[name="nostr-pubkey"]')?.content;
    const loginUrl = document.querySelector('meta[name="nostr-login-url"]')?.content || '/auth/nostr/login';
    const logoutUrl = document.querySelector('meta[name="nostr-logout-url"]')?.content || '/auth/nostr/logout';
    const challengeUrl = document.querySelector('meta[name="nostr-challenge-url"]')?.content || '/auth/nostr/challenge';
    const storedPk = StorageClient.getNostrPubkey();

    const replaceLoginWithLogout = (pubkey) => {
        const loginBtn = document.getElementById('nostr-login-btn');
        if (!loginBtn) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = logoutUrl;
        form.className = 'nav-link flex items-center gap-1';
        form.innerHTML =
            `<input type="hidden" name="_token" value="${csrfToken}">` +
            `<button type="submit" class="flex items-center gap-1">` +
            `<svg data-lucide="log-out" class="w-5 h-5"></svg>` +
            `<span id="nostr-logout-label" class="link-text">${pubkey.slice(0, 5)}&hellip; Logout</span>` +
            `</button>`;
        loginBtn.replaceWith(form);
        form.addEventListener('submit', handleLogout);
        window.refreshLucideIcons();
        updateNostrLogoutLabel(pubkey);
    };

    const replaceLogoutWithLogin = () => {
        const logoutForm = document.querySelector('form[action*="nostr/logout"]');
        if (!logoutForm) return;
        const button = document.createElement('button');
        button.type = 'button';
        button.id = 'nostr-login-btn';
        button.className = 'nav-link flex items-center gap-1';
        button.innerHTML =
            `<svg data-lucide="log-in" class="w-5 h-5"></svg>` +
            `<span class="link-text">Nostr Login</span>`;
        logoutForm.replaceWith(button);
        button.addEventListener('click', handleLogin);
        window.refreshLucideIcons();
    };

    const handleLogin = async () => {
        if (!window.nostr || !window.nostr.getPublicKey || !window.nostr.signEvent) {
            alert('Nostr extension not available');
            return;
        }
        try {
            const pk = await window.nostr.getPublicKey();
            if (!pk) return;
            const challResp = await fetch(challengeUrl, { credentials: 'same-origin' });
            const { challenge } = await challResp.json();
            const event = {
                kind: 22242,
                pubkey: pk,
                created_at: Math.floor(Date.now() / 1000),
                content: challenge,
                tags: []
            };
            const signed = await window.nostr.signEvent(event);
            StorageClient.setNostrPubkey(pk);
            const resp = await fetch(loginUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin',
                body: JSON.stringify({ event: signed })
            });
            if (resp.ok) {
                replaceLoginWithLogout(pk);
            } else {
                console.error('Nostr login failed');
            }
        } catch (e) {
            console.error(e);
        }
    };

    const handleLogout = async (e) => {
        e.preventDefault();
        const form = e.target.closest('form');
        await fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
        });
        StorageClient.clearNostrPubkey();
        StorageClient.clearNostrName();
        replaceLogoutWithLogin();
    };

    if (storedPk && !pubkeyMeta) {
        replaceLoginWithLogout(storedPk);
        updateNostrLogoutLabel(storedPk);
        fetch(loginUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
            body: JSON.stringify({ pubkey: storedPk })
        }).catch(() => {});
    } else if (pubkeyMeta && !storedPk) {
        StorageClient.setNostrPubkey(pubkeyMeta);
        updateNostrLogoutLabel(pubkeyMeta);
    }

    const loginBtn = document.getElementById('nostr-login-btn');
    if (loginBtn) loginBtn.addEventListener('click', handleLogin);

    const logoutForm = document.querySelector('form[action*="nostr/logout"]');
    if (logoutForm) logoutForm.addEventListener('submit', handleLogout);
});

// ---------- NOSTR LOGIN ----------
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const pubkeyMeta = document.querySelector('meta[name="nostr-pubkey"]')?.content;
    const storedPk = StorageClient.getNostrPubkey();

    const replaceLoginWithLogout = (pubkey) => {
        const loginBtn = document.getElementById('nostr-login-btn');
        if (!loginBtn) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/auth/nostr/logout';
        form.className = 'nav-link flex items-center gap-1';
        form.innerHTML =
            `<input type="hidden" name="_token" value="${csrfToken}">` +
            `<button type="submit" class="flex items-center gap-1">` +
            `<svg data-lucide="log-out" class="w-5 h-5"></svg>` +
            `<span id="nostr-logout-label" class="link-text">${pubkey.slice(0, 5)}&hellip; Logout</span>` +
            `</button>`;
        loginBtn.replaceWith(form);
        form.addEventListener('submit', handleLogout);
        window.refreshLucideIcons();
        updateNostrLogoutLabel(pubkey);
    };

    const replaceLogoutWithLogin = () => {
        const logoutForm = document.querySelector('form[action*="nostr/logout"]');
        if (!logoutForm) return;
        const button = document.createElement('button');
        button.type = 'button';
        button.id = 'nostr-login-btn';
        button.className = 'nav-link flex items-center gap-1';
        button.innerHTML =
            `<svg data-lucide="log-in" class="w-5 h-5"></svg>` +
            `<span class="link-text">Nostr Login</span>`;
        logoutForm.replaceWith(button);
        button.addEventListener('click', handleLogin);
        window.refreshLucideIcons();
    };

    const handleLogin = async () => {
        if (!window.nostr || !window.nostr.getPublicKey || !window.nostr.signEvent) {
            alert('Nostr extension not available');
            return;
        }
        try {
            const pk = await window.nostr.getPublicKey();
            if (!pk) return;
            const challResp = await fetch(challengeUrl, { credentials: 'same-origin' });
            const { challenge } = await challResp.json();
            const event = {
                kind: 22242,
                pubkey: pk,
                created_at: Math.floor(Date.now() / 1000),
                content: challenge,
                tags: []
            };
            const signed = await window.nostr.signEvent(event);
            StorageClient.setNostrPubkey(pk);
            const resp = await fetch('/auth/nostr/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin',
                body: JSON.stringify({ event: signed })
            });
            if (resp.ok) {
                replaceLoginWithLogout(pk);
                window.location.reload();
            } else {
                console.error('Nostr login failed');
            }
        } catch (e) {
            console.error(e);
        }
    };

    const handleLogout = async (e) => {
        e.preventDefault();
        const form = e.target.closest('form');
        await fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
        });
        StorageClient.clearNostrPubkey();
        StorageClient.clearNostrName();
        replaceLogoutWithLogin();
    };

    if (storedPk && !pubkeyMeta) {
        replaceLoginWithLogout(storedPk);
        updateNostrLogoutLabel(storedPk);
        fetch('/auth/nostr/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
            body: JSON.stringify({ pubkey: storedPk })
        }).catch(() => {});
    } else if (pubkeyMeta && !storedPk) {
        StorageClient.setNostrPubkey(pubkeyMeta);
        updateNostrLogoutLabel(pubkeyMeta);
    }

    const loginBtn = document.getElementById('nostr-login-btn');
    if (loginBtn) loginBtn.addEventListener('click', handleLogin);

    const logoutForm = document.querySelector('form[action*="nostr/logout"]');
    if (logoutForm) logoutForm.addEventListener('submit', handleLogout);
});

// ---------- NOSTR LOGIN ----------
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const pubkeyMeta = document.querySelector('meta[name="nostr-pubkey"]')?.content;
    const storedPk = StorageClient.getNostrPubkey();

    const replaceLoginWithLogout = (pubkey) => {
        const loginBtn = document.getElementById('nostr-login-btn');
        if (!loginBtn) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/auth/nostr/logout';
        form.className = 'nav-link flex items-center gap-1';
        form.innerHTML =
            `<input type="hidden" name="_token" value="${csrfToken}">` +
            `<button type="submit" class="flex items-center gap-1">` +
            `<svg data-lucide="log-out" class="w-5 h-5"></svg>` +
            `<span id="nostr-logout-label" class="link-text">${pubkey.slice(0, 5)}&hellip; Logout</span>` +
            `</button>`;
        loginBtn.replaceWith(form);
        form.addEventListener('submit', handleLogout);
        window.refreshLucideIcons();
        updateNostrLogoutLabel(pubkey);
    };

    const replaceLogoutWithLogin = () => {
        const logoutForm = document.querySelector('form[action*="nostr/logout"]');
        if (!logoutForm) return;
        const button = document.createElement('button');
        button.type = 'button';
        button.id = 'nostr-login-btn';
        button.className = 'nav-link flex items-center gap-1';
        button.innerHTML =
            `<svg data-lucide="log-in" class="w-5 h-5"></svg>` +
            `<span class="link-text">Nostr Login</span>`;
        logoutForm.replaceWith(button);
        button.addEventListener('click', handleLogin);
        window.refreshLucideIcons();
    };

    const handleLogin = async () => {
        if (!window.nostr || !window.nostr.getPublicKey || !window.nostr.signEvent) {
            alert('Nostr extension not available');
            return;
        }
        try {
            const pk = await window.nostr.getPublicKey();
            if (!pk) return;
            const challResp = await fetch(challengeUrl, { credentials: 'same-origin' });
            const { challenge } = await challResp.json();
            const event = {
                kind: 22242,
                pubkey: pk,
                created_at: Math.floor(Date.now() / 1000),
                content: challenge,
                tags: []
            };
            const signed = await window.nostr.signEvent(event);
            await fetch('/auth/nostr/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin',
                body: JSON.stringify({ event: signed })
            });
            replaceLoginWithLogout(pk);
                window.location.reload();
        } catch (e) {
            console.error(e);
        }
    };

    const handleLogout = async (e) => {
        e.preventDefault();
        const form = e.target.closest('form');
        await fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
        });
        StorageClient.clearNostrPubkey();
        StorageClient.clearNostrName();
        replaceLogoutWithLogin();
    };

    if (storedPk && !pubkeyMeta) {
        replaceLoginWithLogout(storedPk);
        updateNostrLogoutLabel(storedPk);
        fetch('/auth/nostr/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
            body: JSON.stringify({ pubkey: storedPk })
        }).catch(() => {});
    } else if (pubkeyMeta && !storedPk) {
        StorageClient.setNostrPubkey(pubkeyMeta);
        updateNostrLogoutLabel(pubkeyMeta);
    }

    const loginBtn = document.getElementById('nostr-login-btn');
    if (loginBtn) loginBtn.addEventListener('click', handleLogin);

    const logoutForm = document.querySelector('form[action*="nostr/logout"]');
    if (logoutForm) logoutForm.addEventListener('submit', handleLogout);
});

// ---------- NOSTR LOGIN ----------
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const pubkeyMeta = document.querySelector('meta[name="nostr-pubkey"]')?.content;
    const storedPk = StorageClient.getNostrPubkey();

    const replaceLoginWithLogout = (pubkey) => {
        const loginBtn = document.getElementById('nostr-login-btn');
        if (!loginBtn) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/auth/nostr/logout';
        form.className = 'nav-link flex items-center gap-1';
        form.innerHTML =
            `<input type="hidden" name="_token" value="${csrfToken}">` +
            `<button type="submit" class="flex items-center gap-1">` +
            `<svg data-lucide="log-out" class="w-5 h-5"></svg>` +
            `<span id="nostr-logout-label" class="link-text">${pubkey.slice(0, 5)}&hellip; Logout</span>` +
            `</button>`;
        loginBtn.replaceWith(form);
        form.addEventListener('submit', handleLogout);
        window.refreshLucideIcons();
        updateNostrLogoutLabel(pubkey);
    };

    const replaceLogoutWithLogin = () => {
        const logoutForm = document.querySelector('form[action*="nostr/logout"]');
        if (!logoutForm) return;
        const button = document.createElement('button');
        button.type = 'button';
        button.id = 'nostr-login-btn';
        button.className = 'nav-link flex items-center gap-1';
        button.innerHTML =
            `<svg data-lucide="log-in" class="w-5 h-5"></svg>` +
            `<span class="link-text">Nostr Login</span>`;
        logoutForm.replaceWith(button);
        button.addEventListener('click', handleLogin);
        window.refreshLucideIcons();
    };

    const handleLogin = async () => {
        if (!window.nostr || !window.nostr.getPublicKey || !window.nostr.signEvent) {
            alert('Nostr extension not available');
            return;
        }
        try {
            const pk = await window.nostr.getPublicKey();
            if (!pk) return;
            const challResp = await fetch(challengeUrl, { credentials: 'same-origin' });
            const { challenge } = await challResp.json();
            const event = {
                kind: 22242,
                pubkey: pk,
                created_at: Math.floor(Date.now() / 1000),
                content: challenge,
                tags: []
            };
            const signed = await window.nostr.signEvent(event);
            await fetch('/auth/nostr/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin',
                body: JSON.stringify({ event: signed })
            });
            replaceLoginWithLogout(pk);
                window.location.reload();
        } catch (e) {
            console.error(e);
        }
    };

    const handleLogout = async (e) => {
        e.preventDefault();
        const form = e.target.closest('form');
        await fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
        });
        StorageClient.clearNostrPubkey();
        StorageClient.clearNostrName();
        replaceLogoutWithLogin();
    };

    if (storedPk && !pubkeyMeta) {
        replaceLoginWithLogout(storedPk);
        updateNostrLogoutLabel(storedPk);
        fetch('/auth/nostr/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
            body: JSON.stringify({ pubkey: storedPk })
        }).catch(() => {});
    } else if (pubkeyMeta && !storedPk) {
        StorageClient.setNostrPubkey(pubkeyMeta);
        updateNostrLogoutLabel(pubkeyMeta);
    }

    const loginBtn = document.getElementById('nostr-login-btn');
    if (loginBtn) loginBtn.addEventListener('click', handleLogin);

    const logoutForm = document.querySelector('form[action*="nostr/logout"]');
    if (logoutForm) logoutForm.addEventListener('submit', handleLogout);
});

// ---------- NOSTR LOGIN ----------
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const pubkeyMeta = document.querySelector('meta[name="nostr-pubkey"]')?.content;
    const storedPk = StorageClient.getNostrPubkey();

    const replaceLoginWithLogout = (pubkey) => {
        const loginBtn = document.getElementById('nostr-login-btn');
        if (!loginBtn) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/auth/nostr/logout';
        form.className = 'nav-link flex items-center gap-1';
        form.innerHTML =
            `<input type="hidden" name="_token" value="${csrfToken}">` +
            `<button type="submit" class="flex items-center gap-1">` +
            `<svg data-lucide="log-out" class="w-5 h-5"></svg>` +
            `<span id="nostr-logout-label" class="link-text">${pubkey.slice(0, 5)}&hellip; Logout</span>` +
            `</button>`;
        loginBtn.replaceWith(form);
        form.addEventListener('submit', handleLogout);
        window.refreshLucideIcons();
        updateNostrLogoutLabel(pubkey);
    };

    const replaceLogoutWithLogin = () => {
        const logoutForm = document.querySelector('form[action*="nostr/logout"]');
        if (!logoutForm) return;
        const button = document.createElement('button');
        button.type = 'button';
        button.id = 'nostr-login-btn';
        button.className = 'nav-link flex items-center gap-1';
        button.innerHTML =
            `<svg data-lucide="log-in" class="w-5 h-5"></svg>` +
            `<span class="link-text">Nostr Login</span>`;
        logoutForm.replaceWith(button);
        button.addEventListener('click', handleLogin);
        window.refreshLucideIcons();
    };

    const handleLogin = async () => {
        if (!window.nostr || !window.nostr.getPublicKey || !window.nostr.signEvent) {
            alert('Nostr extension not available');
            return;
        }
        try {
            const pk = await window.nostr.getPublicKey();
            if (!pk) return;
            const challResp = await fetch(challengeUrl, { credentials: 'same-origin' });
            const { challenge } = await challResp.json();
            const event = {
                kind: 22242,
                pubkey: pk,
                created_at: Math.floor(Date.now() / 1000),
                content: challenge,
                tags: []
            };
            const signed = await window.nostr.signEvent(event);
            await fetch('/auth/nostr/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin',
                body: JSON.stringify({ event: signed })
            });
            replaceLoginWithLogout(pk);
                window.location.reload();
        } catch (e) {
            console.error(e);
        }
    };

    const handleLogout = async (e) => {
        e.preventDefault();
        const form = e.target.closest('form');
        await fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
        });
        StorageClient.clearNostrPubkey();
        StorageClient.clearNostrName();
        replaceLogoutWithLogin();
    };

    if (storedPk && !pubkeyMeta) {
        replaceLoginWithLogout(storedPk);
        updateNostrLogoutLabel(storedPk);
        fetch('/auth/nostr/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
            body: JSON.stringify({ pubkey: storedPk })
        }).catch(() => {});
    } else if (pubkeyMeta && !storedPk) {
        StorageClient.setNostrPubkey(pubkeyMeta);
        updateNostrLogoutLabel(pubkeyMeta);
    }

    const loginBtn = document.getElementById('nostr-login-btn');
    if (loginBtn) loginBtn.addEventListener('click', handleLogin);

    const logoutForm = document.querySelector('form[action*="nostr/logout"]');
    if (logoutForm) logoutForm.addEventListener('submit', handleLogout);
});

// ---------- NOSTR LOGIN ----------
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const pubkeyMeta = document.querySelector('meta[name="nostr-pubkey"]')?.content;
    const storedPk = StorageClient.getNostrPubkey();

    const replaceLoginWithLogout = (pubkey) => {
        const loginBtn = document.getElementById('nostr-login-btn');
        if (!loginBtn) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/auth/nostr/logout';
        form.className = 'nav-link flex items-center gap-1';
        form.innerHTML =
            `<input type="hidden" name="_token" value="${csrfToken}">` +
            `<button type="submit" class="flex items-center gap-1">` +
            `<svg data-lucide="log-out" class="w-5 h-5"></svg>` +
            `<span id="nostr-logout-label" class="link-text">${pubkey.slice(0, 5)}&hellip; Logout</span>` +
            `</button>`;
        loginBtn.replaceWith(form);
        form.addEventListener('submit', handleLogout);
        window.refreshLucideIcons();
        updateNostrLogoutLabel(pubkey);
    };

    const replaceLogoutWithLogin = () => {
        const logoutForm = document.querySelector('form[action*="nostr/logout"]');
        if (!logoutForm) return;
        const button = document.createElement('button');
        button.type = 'button';
        button.id = 'nostr-login-btn';
        button.className = 'nav-link flex items-center gap-1';
        button.innerHTML =
            `<svg data-lucide="log-in" class="w-5 h-5"></svg>` +
            `<span class="link-text">Nostr Login</span>`;
        logoutForm.replaceWith(button);
        button.addEventListener('click', handleLogin);
        window.refreshLucideIcons();
    };

    const handleLogin = async () => {
        if (!window.nostr || !window.nostr.getPublicKey) {
            alert('Nostr extension not available');
            return;
        }
        try {
            const pk = await window.nostr.getPublicKey();
            if (!pk) return;
            StorageClient.setNostrPubkey(pk);
            await fetch('/auth/nostr/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin',
                body: JSON.stringify({ pubkey: pk })
            });
            replaceLoginWithLogout(pk);
                window.location.reload();
        } catch (e) {
            console.error(e);
        }
    };

    const handleLogout = async (e) => {
        e.preventDefault();
        const form = e.target.closest('form');
        await fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
        });
        StorageClient.clearNostrPubkey();
        StorageClient.clearNostrName();
        replaceLogoutWithLogin();
    };

    if (storedPk && !pubkeyMeta) {
        replaceLoginWithLogout(storedPk);
        updateNostrLogoutLabel(storedPk);
        fetch('/auth/nostr/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
            body: JSON.stringify({ pubkey: storedPk })
        }).catch(() => {});
    } else if (pubkeyMeta && !storedPk) {
        StorageClient.setNostrPubkey(pubkeyMeta);
        updateNostrLogoutLabel(pubkeyMeta);
    }

    const loginBtn = document.getElementById('nostr-login-btn');
    if (loginBtn) loginBtn.addEventListener('click', handleLogin);

    const logoutForm = document.querySelector('form[action*="nostr/logout"]');
    if (logoutForm) logoutForm.addEventListener('submit', handleLogout);
});

// ---------- NOSTR LOGIN ----------
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    const replaceLoginWithLogout = (pubkey) => {
        const loginBtn = document.getElementById('nostr-login-btn');
        if (!loginBtn) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/auth/nostr/logout';
        form.className = 'nav-link flex items-center gap-1';
        form.innerHTML =
            `<input type="hidden" name="_token" value="${csrfToken}">` +
            `<button type="submit" class="flex items-center gap-1">` +
            `<svg data-lucide="log-out" class="w-5 h-5"></svg>` +
            `<span id="nostr-logout-label" class="link-text">${pubkey.slice(0, 5)}&hellip; Logout</span>` +
            `</button>`;
        loginBtn.replaceWith(form);
        form.addEventListener('submit', handleLogout);
        window.refreshLucideIcons();
        updateNostrLogoutLabel(pubkey);
    };

    const replaceLogoutWithLogin = () => {
        const logoutForm = document.querySelector('form[action*="nostr/logout"]');
        if (!logoutForm) return;
        const button = document.createElement('button');
        button.type = 'button';
        button.id = 'nostr-login-btn';
        button.className = 'nav-link flex items-center gap-1';
        button.innerHTML =
            `<svg data-lucide="log-in" class="w-5 h-5"></svg>` +
            `<span class="link-text">Nostr Login</span>`;
        logoutForm.replaceWith(button);
        button.addEventListener('click', handleLogin);
        window.refreshLucideIcons();
    };

    const handleLogin = async () => {
        if (!window.nostr || !window.nostr.getPublicKey || !window.nostr.signEvent) {
            alert('Nostr extension not available');
            return;
        }
        try {
            const pk = await window.nostr.getPublicKey();
            if (!pk) return;
            const challResp = await fetch(challengeUrl, { credentials: 'same-origin' });
            const { challenge } = await challResp.json();
            const event = {
                kind: 22242,
                pubkey: pk,
                created_at: Math.floor(Date.now() / 1000),
                content: challenge,
                tags: []
            };
            const signed = await window.nostr.signEvent(event);
            await fetch('/auth/nostr/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ event: signed })
            });
            replaceLoginWithLogout(pk);
                window.location.reload();
        } catch (e) {
            console.error(e);
        }
    };

    const handleLogout = async (e) => {
        e.preventDefault();
        const form = e.target.closest('form');
        await fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
        });
        StorageClient.clearNostrPubkey();
        replaceLogoutWithLogin();
    };

    const loginBtn = document.getElementById('nostr-login-btn');
    if (loginBtn) loginBtn.addEventListener('click', handleLogin);

    const logoutForm = document.querySelector('form[action*="nostr/logout"]');
    if (logoutForm) logoutForm.addEventListener('submit', handleLogout);
});
