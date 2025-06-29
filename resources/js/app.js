import './bootstrap';
import Alpine from 'alpinejs';
import StorageClient from './storage-client';
import { nip19, relayInit } from 'nostr-tools';
import {
    BadgeCheck,
    Bitcoin,
    Bot,
    ChevronLeft,
    ChevronRight,
    ChevronDown,
    createIcons,
    Github,
    Lightbulb,
    Loader2,
    LogIn,
    LogOut,
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
    ChevronDown,
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
    LogIn,
    LogOut,
    ExternalLink,
    X,
};

createIcons({icons: usedIcons});
applyNostrAvatarToMessages();

async function fetchNostrProfile(pubkey) {
    let hex = pubkey;
    if (pubkey.startsWith('npub')) {
        try {
            hex = nip19.decode(pubkey).data;
        } catch (e) {
            console.error('Failed to decode npub', e);
            return null;
        }
    }

    let meta = null;
    try {
        const relay = relayInit('wss://relay.damus.io');
        await relay.connect();
        meta = await new Promise((resolve) => {
            const sub = relay.sub([{ kinds: [0], authors: [hex], limit: 1 }]);
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
                    const m = JSON.parse(ev.content);
                    finalize({
                        name: m.name ?? null,
                        display_name: m.display_name ?? null,
                        about: m.about ?? null,
                        picture: m.picture || m.image || null,
                        image: m.image || m.picture || null,
                        banner: m.banner ?? null,
                        website: m.website || m.url || null,
                        nip05: m.nip05 ?? null,
                        lud16: m.lud16 ?? m.lud06 ?? null,
                    });
                } catch {
                    finalize(null);
                }
            });
            sub.on('eose', () => finalize(null));
            setTimeout(() => finalize(null), 5000);
        });
    } catch (e) {
        console.error('Failed relay fetch', e);
    }

    let stats = null;
    try {
        const resp = await fetch(`https://api.nostr.band/v0/profiles/${hex}`);
        if (resp.ok) {
            const data = await resp.json();
            stats = {
                followers: data.followers ?? null,
                following: data.following ?? null,
            };
        }
    } catch (e) {
        console.error('Failed stats fetch', e);
    }

    if (meta || stats) {
        return {
            ...(meta || {}),
            ...(stats || {}),
        };
    }
    return null;
}

async function updateNostrLogoutLabel(pubkey) {
    let name = StorageClient.getNostrName();
    let image = StorageClient.getNostrImage();

    if (!name || !image) {
        const profile = await fetchNostrProfile(pubkey);
        console.log('Nostr profile', profile);
        if (profile) {
            if (!name && (profile.display_name || profile.name)) {
                name = profile.display_name || profile.name;
                StorageClient.setNostrName(name);
            }
            if (!image && (profile.picture || profile.image)) {
                image = profile.picture || profile.image;
                StorageClient.setNostrImage(image);
            }
        } else {
            console.warn(`No nostr metadata found for pubkey ${pubkey}. Consider setting display_name via a client.`);
        }
    }

    if (name) {
        const label = document.getElementById('nostr-logout-label');
        if (label) {
            label.textContent = `${name}`;
        }
    }

    const $avatar = document.getElementById('nostr-avatar');

    if (image) {
        if ($avatar) {
            $avatar.src = image;
            $avatar.classList.remove('hidden');
        }
    } else if ($avatar) {
        $avatar.classList.add('hidden');
    }

    applyNostrAvatarToMessages();
}

function applyNostrAvatarToMessages() {
    const pubkey = StorageClient.getNostrPubkey();
    const image = StorageClient.getNostrImage();

    if (pubkey && image) {
        document.querySelectorAll('.user-message[data-owned="1"] [data-lucide="user"]').forEach(el => {
            const img = document.createElement('img');
            img.src = image;
            img.className = 'w-6 h-6 rounded-full user-avatar';
            el.replaceWith(img);
        });
        document.querySelectorAll('.user-message[data-owned="1"] img.user-avatar').forEach(img => {
            img.src = image;
        });
    } else {
        const replaced = [];
        document.querySelectorAll('.user-message[data-owned="1"] img.user-avatar').forEach(img => {
            const icon = document.createElement('i');
            icon.setAttribute('data-lucide', 'user');
            icon.setAttribute('class', 'w-6 h-6');
            img.replaceWith(icon);
            replaced.push(icon);
        });
        if (replaced.length > 0) {
            createIcons({icons: usedIcons});
        }
    }
}

async function updateProfilePage() {
    const metaTag = document.querySelector('meta[name="nostr-pubkey"]');
    const pubkey = metaTag?.content;
    if (!pubkey) return;

    const profile = await fetchNostrProfile(pubkey);
    if (!profile) return;

    const container = document.getElementById('nostr-profile-meta');
    if (!container) return;

    if (profile && profile.banner) {
        const banner = document.getElementById('profile-banner');
        if (banner) {
            banner.style.backgroundImage = `url(${profile.banner})`;
            banner.classList.remove('hidden');
        }
    }

    if (profile && profile.picture) {
        const img = document.getElementById('profile-avatar');
        if (img) {
            img.src = profile.picture;
            img.classList.remove('hidden');
        }
    }

    const displayName = profile ? (profile.display_name || profile.name) : null;
    if (displayName) {
        const el = document.getElementById('profile-name');
        if (el) el.textContent = displayName;
    }

    if (profile && profile.name) {
        const el = document.getElementById('profile-username');
        if (el) el.textContent = profile.name;
    }

    if (profile && profile.website) {
        const el = document.getElementById('profile-url');
        if (el) {
            el.textContent = profile.website;
            el.href = profile.website;
            el.classList.remove('hidden');
        }
    }

    if (profile && profile.nip05) {
        const el = document.getElementById('profile-nip05');
        if (el) {
            el.textContent = profile.nip05;
            el.classList.remove('hidden');
        }
    }

    if (profile && profile.lud16) {
        const el = document.getElementById('profile-lud16');
        if (el) {
            el.textContent = profile.lud16;
            el.classList.remove('hidden');
        }
    }

    if (profile && profile.about) {
        const el = document.getElementById('profile-about');
        if (el) {
            el.textContent = profile.about;
            el.classList.remove('hidden');
        }
    }

    if (profile && (profile.followers || profile.following)) {
        if (profile.followers) {
            const el = document.getElementById('profile-followers');
            if (el) {
                el.textContent = `${profile.followers} followers`;
                el.classList.remove('hidden');
            }
        }
        if (profile.following) {
            const el = document.getElementById('profile-following');
            if (el) {
                el.textContent = `${profile.following} following`;
                el.classList.remove('hidden');
            }
        }
    }
}

// ---------- DOM READY ----------
document.addEventListener('DOMContentLoaded', () => {
    createIcons({icons: usedIcons});
    applyNostrAvatarToMessages();
    updateProfilePage();

    setupBlockchainToggle();
    setupDescriptionToggle();

    window.refreshThemeIcon = () => {
        const icon = document.getElementById('theme-icon');
        if (icon) {
            createIcons({icons, attrs: {class: icon.getAttribute('class')}});
        }
    };

    window.refreshLucideIcons = () => {
        requestAnimationFrame(() => {
            createIcons({icons: usedIcons});
            applyNostrAvatarToMessages();
        });
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
        const menu = document.querySelector('[data-nostr-menu]');
        if (!menu) return;
        const wrapper = document.createElement('div');
        wrapper.className = 'relative';
        wrapper.setAttribute('x-data', '{ open: false }');
        wrapper.setAttribute('data-nostr-menu', '');
        wrapper.innerHTML =
            `<button type="button" class="nav-link flex items-center gap-1" @click="open = !open">` +
            `<img id="nostr-avatar" src="" alt="nostr avatar" class="w-5 h-5 rounded-full hidden" />` +
            `<span id="nostr-logout-label" class="link-text">${pubkey.slice(0, 5)}</span>` +
            `<svg id="nostr-menu-icon" data-lucide="chevron-down" class="w-5 h-5"></svg>` +
            `</button>` +
            `<div x-show="open" x-cloak @click.away="open = false" class="absolute right-0 mt-2 w-36 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 z-50">` +
            `<a href="/history" class="flex items-center gap-1 px-4 py-2 nav-link text-left">` +
            `<svg data-lucide="scroll" class="w-5 h-5"></svg>` +
            `<span class="ml-1">History</span>` +
            `</a>` +
            `<button type="button" class="w-full text-left px-4 py-2 nav-link flex items-center gap-1" @click="dark = !dark; $nextTick(() => refreshThemeIcon()); open = false;">` +
            `<svg :data-lucide="dark ? 'sun' : 'moon'" id="theme-icon" class="w-5 h-5"></svg>` +
            `<span class="ml-1">Theme</span>` +
            `</button>` +
            `<form method="POST" action="${logoutUrl}" class="mt-1">` +
            `<input type="hidden" name="_token" value="${csrfToken}">` +
            `<button type="submit" class="w-full text-left px-4 py-2 nav-link flex items-center gap-1">` +
            `<svg data-lucide="log-out" class="w-5 h-5"></svg>` +
            `<span class="ml-1">Logout</span>` +
            `</button>` +
            `</form>` +
            `</div>`;
        menu.replaceWith(wrapper);
        const form = wrapper.querySelector('form');
        form.addEventListener('submit', handleLogout);
        window.refreshLucideIcons();
        updateNostrLogoutLabel(pubkey);
    };

    const replaceLogoutWithLogin = () => {
        const menu = document.querySelector('[data-nostr-menu]');
        if (!menu) return;
        const wrapper = document.createElement('div');
        wrapper.className = 'relative';
        wrapper.setAttribute('x-data', '{ open: false }');
        wrapper.setAttribute('data-nostr-menu', '');
        wrapper.innerHTML =
            `<button type="button" class="nav-link flex items-center gap-1" @click="open = !open">` +
            `<svg data-lucide="user" class="w-5 h-5"></svg>` +
            `<span class="link-text">Login</span>` +
            `<svg data-lucide="chevron-down" class="w-5 h-5"></svg>` +
            `</button>` +
            `<div x-show="open" x-cloak @click.away="open = false" class="absolute right-0 mt-2 w-36 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 z-50">` +
            `<button type="button" id="nostr-login-btn" class="w-full text-left px-4 py-2 nav-link flex items-center gap-1">` +
            `<svg data-lucide="log-in" class="w-5 h-5"></svg>` +
            `<span class="ml-1">Nostr</span>` +
            `</button>` +
            `<button type="button" class="w-full text-left px-4 py-2 nav-link flex items-center gap-1" @click="dark = !dark; $nextTick(() => refreshThemeIcon()); open = false;">` +
            `<svg :data-lucide="dark ? 'sun' : 'moon'" id="theme-icon" class="w-5 h-5"></svg>` +
            `<span class="ml-1">Theme</span>` +
            `</button>` +
            `</div>`;
        menu.replaceWith(wrapper);
        const btn = wrapper.querySelector('#nostr-login-btn');
        btn.addEventListener('click', handleLogin);
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
        StorageClient.clearNostrImage();
        replaceLogoutWithLogin();
        window.location.reload();
    };

    if (pubkeyMeta) {
        if (!storedPk) {
            StorageClient.setNostrPubkey(pubkeyMeta);
        }
        updateNostrLogoutLabel(pubkeyMeta);
    } else if (storedPk) {
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
    }

    const loginBtn = document.getElementById('nostr-login-btn');
    if (loginBtn) loginBtn.addEventListener('click', handleLogin);

    const logoutForm = document.querySelector('form[action*="nostr/logout"]');
    if (logoutForm) logoutForm.addEventListener('submit', handleLogout);
});
