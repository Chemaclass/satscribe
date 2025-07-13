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
    if (avatar) {
        if (image) {
            avatar.src = image;
            avatar.classList.remove('hidden', 'bg-gray-300/50');
        } else {
            avatar.src = 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';
            avatar.classList.remove('hidden');
            avatar.classList.add('bg-gray-300/50');
        }
    }

    applyNostrAvatarToMessages();
}

export function applyNostrAvatarToMessages() {
    const pubkey = StorageClient.getNostrPubkey();
    const image = StorageClient.getNostrImage();

    if (pubkey && image) {
        document.querySelectorAll('.user-message[data-owned="1"] .nostr-avatar-placeholder, .user-message[data-owned="1"] [data-lucide="user"]').forEach(el => {
            const img = document.createElement('img');
            img.src = image;
            img.className = 'w-6 h-6 rounded-full user-avatar object-cover';
            el.replaceWith(img);
        });
        document.querySelectorAll('.user-message[data-owned="1"] img.user-avatar').forEach(img => {
            img.src = image;
        });
    } else {
        const replaced = [];
        document.querySelectorAll('.user-message[data-owned="1"] img.user-avatar').forEach(img => {
            const span = document.createElement('span');
            span.className = 'w-6 h-6 rounded-full bg-gray-300/50 flex items-center justify-center nostr-avatar-placeholder';
            span.innerHTML = '<i data-lucide="user" class="w-4 h-4 text-gray-500"></i>';
            img.replaceWith(span);
            replaced.push(span);
        });
        document.querySelectorAll('.user-message[data-owned="1"] [data-lucide="user"]').forEach(icon => {
            const span = document.createElement('span');
            span.className = 'w-6 h-6 rounded-full bg-gray-300/50 flex items-center justify-center nostr-avatar-placeholder';
            icon.className = 'w-4 h-4 text-gray-500';
            span.appendChild(icon.cloneNode(true));
            icon.replaceWith(span);
            replaced.push(span);
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
            banner.classList.remove('skeleton', 'hidden');
        }
    }

    if (profile.picture) {
        const img = document.getElementById('profile-avatar');
        img.src = profile.picture;
        img.classList.remove('skeleton', 'hidden');
    }

    const profileDisplayNameElement = document.getElementById('profile-displayname');
    if (profileDisplayNameElement) {
        profileDisplayNameElement.textContent = profile.display_name || "@" + profile.name;
        profileDisplayNameElement.classList.remove('skeleton');
    }

    const profileNameElement = document.getElementById('profile-name');
    if (profile.display_name !== null && profile.name !== profile.display_name) {
        if (profile.name) {
            profileNameElement.textContent = "@" + profile.name;
            profileNameElement.classList.remove('skeleton');
        }
    } else {
        profileNameElement.classList.add('hidden');
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
        const storedPk = StorageClient.getNostrPubkey();

        const handleLogin = async () => {
            if (window.nostrLoginModal && window.nostrLoginModal.open) {
                window.nostrLoginModal.open();
            } else {
                alert('Login modal not found');
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
            window.location.reload();
        };

        if (pubkeyMeta) {
            if (!storedPk) {
                StorageClient.setNostrPubkey(pubkeyMeta);
            }
            updateNostrLogoutLabel(pubkeyMeta);
        }

        const loginBtn = document.getElementById('nostr-login-btn');
        if (loginBtn) loginBtn.addEventListener('click', handleLogin);

        const logoutForm = document.querySelector('form[action*="nostr/logout"]');
        if (logoutForm) logoutForm.addEventListener('submit', handleLogout);
    });
}
