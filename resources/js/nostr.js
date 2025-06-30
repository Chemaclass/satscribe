import {nip19, SimplePool} from 'nostr-tools';
import StorageClient from './storage-client';
import {refreshIcons} from './icons';

const RELAYS = [
    'wss://atlas.nostr.land',
    'wss://eden.nostr.land',
    'wss://no.str.cr',
    'wss://nos.lol',
    'wss://nostr.azte.co',
    'wss://nostr.mom',
    'wss://nostr.wine',
    'wss://puravida.nostr.land',
    'wss://relay.damus.io',
    'wss://relay.nostr.band',
    'wss://nostr.fmt.wiz.biz',
    'wss://nostr.oxtr.dev',
    'wss://nostr.bitcoiner.social',
];

export async function fetchNostrProfile(pubkey) {
    let hex = pubkey;
    if (pubkey.startsWith('npub')) {
        try {
            hex = nip19.decode(pubkey).data;
        } catch (e) {
            console.error('Failed to decode npub', e);
            return null;
        }
    }

    try {
        const pool = new SimplePool();
        const event = await Promise.race([
            pool.get(RELAYS, { kinds: [0], authors: [hex], limit: 1 }),
            new Promise((resolve) => setTimeout(() => resolve(null), 3000)),
        ]);
        pool.close(RELAYS);
        if (event) {
            const m = JSON.parse(event.content);
            return {
                name: m.name ?? null,
                display_name: m.display_name ?? null,
                about: m.about ?? null,
                picture: m.picture || m.image || null,
                image: m.image || m.picture || null,
                banner: m.banner ?? null,
                website: m.website || m.url || null,
                nip05: m.nip05 ?? null,
                lud16: m.lud16 ?? m.lud06 ?? null,
            };
        }
    } catch (e) {
        console.error('Failed relay fetch', e);
    }

    return null;
}

export async function updateNostrLogoutLabel(pubkey) {
    let profile = StorageClient.getNostrProfile();
    if (!profile) {
        profile = await fetchNostrProfile(pubkey);
        if (profile) {
            StorageClient.setNostrProfile(profile);
        } else {
            console.warn(`No nostr metadata found for pubkey ${pubkey}. Consider setting display_name via a client.`);
        }
    }

    const name = StorageClient.getNostrName();
    const image = StorageClient.getNostrImage();

    const label = document.getElementById('nostr-logout-label');
    if (label) {
        if (name) {
            label.textContent = `${name}`;
        } else {
            label.textContent = pubkey.slice(0, 5);
        }
    }

    const avatar = document.getElementById('nostr-avatar');
    if (image) {
        if (avatar) {
            avatar.src = image;
            avatar.classList.remove('hidden');
        }
    } else if (avatar) {
        avatar.classList.add('hidden');
    }

    applyNostrAvatarToMessages();
}

export function applyNostrAvatarToMessages() {
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
        if (replaced.length > 0) refreshIcons();
    }
}

export async function updateProfilePage(force = false) {
    const metaTag = document.querySelector('meta[name="nostr-pubkey"]');
    const pubkey = metaTag?.content;
    if (!pubkey) return;

    let profile = null;
    if (!force) {
        profile = StorageClient.getNostrProfile();
    }
    if (!profile) {
        profile = await fetchNostrProfile(pubkey);
        if (profile) {
            StorageClient.setNostrProfile(profile);
        }
    }
    if (!profile) return;

    const container = document.getElementById('nostr-profile-meta');
    if (!container) return;

    if (profile.banner) {
        const banner = document.getElementById('profile-banner');
        if (banner) {
            banner.style.backgroundImage = `url(${profile.banner})`;
            banner.classList.remove('hidden');
        }
    }

    if (profile.picture) {
        const img = document.getElementById('profile-avatar');
        if (img) {
            img.src = profile.picture;
            img.classList.remove('hidden');
        }
    }

    const displayName = profile.display_name || profile.name;
    if (displayName) {
        const el = document.getElementById('profile-name');
        if (el) el.textContent = displayName;
    }

    if (profile.name) {
        const el = document.getElementById('profile-username');
        if (el) el.textContent = profile.name;
    }

    if (profile.website) {
        const el = document.getElementById('profile-url');
        if (el) {
            el.textContent = profile.website;
            el.href = profile.website;
            el.classList.remove('hidden');
        }
    }

    if (profile.nip05) {
        const el = document.getElementById('profile-nip05');
        if (el) {
            el.textContent = profile.nip05;
            el.classList.remove('hidden');
        }
    }

    if (profile.lud16) {
        const el = document.getElementById('profile-lud16');
        if (el) {
            el.textContent = profile.lud16;
            el.classList.remove('hidden');
        }
    }

    if (profile.about) {
        const el = document.getElementById('profile-about');
        if (el) {
            el.textContent = profile.about;
            el.classList.remove('hidden');
        }
    }

}

export function initNostrAuth() {
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
                `<span id="nostr-logout-label" class="link-text">${window.i18n.loading}</span>` +
                `<svg id="nostr-menu-icon" data-lucide="chevron-down" class="w-5 h-5"></svg>` +
                `</button>` +
                `<div x-show="open" x-cloak @click.away="open = false" class="absolute right-0 mt-2 w-36 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 z-50">` +
                `<a href="/history" class="flex items-center gap-1 px-4 py-2 nav-link text-left border-b border-gray-200 dark:border-gray-700">` +
                `<svg data-lucide="scroll" class="w-5 h-5"></svg>` +
                `<span class="ml-1">History</span>` +
                `</a>` +
                `<a href="/profile" class="flex items-center gap-1 px-4 py-2 nav-link text-left">` +
                `<svg data-lucide="user" class="w-5 h-5"></svg>` +
                `<span class="ml-1">Profile</span>` +
                `</a>` +
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
            refreshIcons();
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
                `<button type="button" id="nostr-login-btn" class="w-full text-left px-4 py-2 nav-link flex items-center gap-1 border-b border-gray-200 dark:border-gray-700">` +
                `<svg data-lucide="log-in" class="w-5 h-5"></svg>` +
                `<span class="ml-1">Nostr</span>` +
                `</button>` +
                `<a href="/history" class="flex items-center gap-1 px-4 py-2 nav-link text-left border-b border-gray-200 dark:border-gray-700">` +
                `<svg data-lucide="scroll" class="w-5 h-5"></svg>` +
                `<span class="ml-1">History</span>` +
                `</a>` +
                `</div>`;
            menu.replaceWith(wrapper);
            const btn = wrapper.querySelector('#nostr-login-btn');
            btn.addEventListener('click', handleLogin);
            refreshIcons();
        };

        const handleLogin = async () => {
            if (!window.nostr || !window.nostr.getPublicKey || !window.nostr.signEvent) {
                try {
                    const challResp = await fetch(challengeUrl, { credentials: 'same-origin' });
                    const { challenge } = await challResp.json();
                    const copy = prompt(
                        'No Nostr extension detected.\n' +
                        'Copy the text below and sign it with your Nostr client:',
                        challenge
                    );
                    if (copy === null) return;
                    const entered = prompt('Paste the signed event JSON here:');
                    if (!entered) return;
                    let event;
                    try {
                        event = JSON.parse(entered);
                    } catch {
                        alert('Invalid JSON');
                        return;
                    }
                    const resp = await fetch(loginUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ event })
                    });
                    if (resp.ok) {
                        const { pubkey } = await resp.json();
                        StorageClient.setNostrPubkey(pubkey);
                        replaceLoginWithLogout(pubkey);
                        window.location.reload();
                    } else {
                        alert('Login failed');
                    }
                } catch (e) {
                    console.error(e);
                }
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
            StorageClient.clearNostrProfile();
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
}
