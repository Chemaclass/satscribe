import { nip19, SimplePool, getEventHash, getSignature, getPublicKey } from 'nostr-tools';
import StorageClient from './storage-client';
import { refreshIcons } from './icons';

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

const PLACEHOLDER_IMAGE = 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';

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
            new Promise(resolve => setTimeout(() => resolve(null), 3000)),
        ]);
        pool.close(RELAYS);

        if (event?.content) {
            try {
                const data = JSON.parse(event.content);
                return {
                    name: data.name ?? null,
                    display_name: data.display_name ?? null,
                    about: data.about ?? null,
                    picture: data.picture || data.image || null,
                    image: data.image || data.picture || null,
                    banner: data.banner ?? null,
                    website: data.website || data.url || null,
                    nip05: data.nip05 ?? null,
                    lud16: data.lud16 ?? data.lud06 ?? null,
                };
            } catch (e) {
                console.error('Failed to parse Nostr metadata', e);
            }
        }
    } catch (e) {
        console.error('Failed to fetch profile from relays', e);
    }

    return null;
}

async function getOrFetchProfile(pubkey) {
    let profile = StorageClient.getNostrProfile();
    if (!profile) {
        profile = await fetchNostrProfile(pubkey);
        if (profile) {
            StorageClient.setNostrProfile(profile);
        } else {
            console.warn(`No Nostr metadata found for pubkey ${pubkey}`);
        }
    }
    return profile;
}

export async function updateNostrLogoutLabel(pubkey) {
    const name = StorageClient.getNostrName();
    const image = StorageClient.getNostrImage();

    const label = document.getElementById('nostr-logout-label');
    if (label) label.textContent = name || pubkey.slice(0, 5);

    const avatar = document.getElementById('nostr-avatar');
    if (avatar) {
        if (image) {
            avatar.src = image;
            avatar.classList.remove('hidden', 'bg-gray-300/50');
        } else {
            avatar.src = PLACEHOLDER_IMAGE;
            avatar.classList.remove('hidden');
            avatar.classList.add('bg-gray-300/50');
        }
    }

    applyNostrAvatarToMessages();
}

export function applyNostrAvatarToMessages() {
    const pubkey = StorageClient.getNostrPubkey();
    const image = StorageClient.getNostrImage();

    const userMessages = document.querySelectorAll('.user-message[data-owned="1"]');
    const replaced = [];

    if (pubkey && image) {
        userMessages.forEach(msg => {
            msg.querySelectorAll('.nostr-avatar-placeholder, [data-lucide="user"]').forEach(el => {
                const img = document.createElement('img');
                img.src = image;
                img.alt = 'User Avatar';
                img.className = 'w-6 h-6 rounded-full user-avatar object-cover';
                el.replaceWith(img);
            });

            msg.querySelectorAll('img.user-avatar').forEach(img => {
                img.src = image;
            });
        });
    } else {
        userMessages.forEach(msg => {
            msg.querySelectorAll('img.user-avatar, [data-lucide="user"]').forEach(oldEl => {
                const span = document.createElement('span');
                span.className = 'w-6 h-6 rounded-full bg-gray-300/50 flex items-center justify-center nostr-avatar-placeholder';
                span.innerHTML = '<i data-lucide="user" class="w-4 h-4 text-gray-500"></i>';
                oldEl.replaceWith(span);
                replaced.push(span);
            });
        });

        if (replaced.length > 0) refreshIcons();
    }
}

export function publishProfileEvent(privkey, name) {
    return new Promise(resolve => {
        try {
            const pubkey = getPublicKey(privkey);
            const event = {
                kind: 0,
                pubkey,
                created_at: Math.floor(Date.now() / 1000),
                content: JSON.stringify({ name }),
                tags: [],
            };
            event.id = getEventHash(event);
            event.sig = getSignature(event, privkey);

            const pool = new SimplePool();
            const pub = pool.publish(RELAYS, event);

            let finished = false;
            const finish = () => {
                if (!finished) {
                    finished = true;
                    setTimeout(() => pool.close(RELAYS), 100);
                    resolve();
                }
            };

            pub.on('ok', finish);
            pub.on('seen', finish);
            pub.on('failed', finish);
            setTimeout(finish, 3000);
        } catch (e) {
            console.error('Failed to publish profile event', e);
            resolve();
        }
    });
}

export async function updateProfilePage(force = false) {
    const pubkey = document.querySelector('meta[name="nostr-pubkey"]')?.content;
    if (!pubkey) return;

    const npubEl = document.getElementById('profile-npub');
    if (npubEl) {
        try {
            const npub = window.nostrTools.nip19.npubEncode
                ? window.nostrTools.nip19.npubEncode(pubkey)
                : window.nostrTools.nip19.encode({ type: 'npub', data: pubkey });
            npubEl.textContent = npub;
        } catch (e) {
            npubEl.textContent = pubkey;
        }
    }

    const profile = force ? await fetchNostrProfile(pubkey) : await getOrFetchProfile(pubkey);
    if (!profile) return;

    const $ = id => document.getElementById(id);

    const sk = StorageClient.getNostrPrivkey();
    const skContainer = $('secret-key-container');
    const skValue = $('secret-key-value');
    const skDelete = $('secret-key-delete');
    const skCopy = $('secret-key-copy');
    const skToggle = $('secret-key-toggle');
    if (skContainer && skValue) {
        if (sk) {
            skValue.value = sk;
            skValue.type = 'password';
            skContainer.classList.remove('hidden');
            if (skDelete && !skDelete.dataset.bound) {
                skDelete.dataset.bound = '1';
                skDelete.addEventListener('click', () => {
                    StorageClient.clearNostrPrivkey();
                    skContainer.classList.add('hidden');
                });
            }
            if (skCopy && !skCopy.dataset.bound) {
                skCopy.dataset.bound = '1';
                skCopy.addEventListener('click', () => {
                    navigator.clipboard.writeText(skValue.value).catch(() => {});
                    skCopy.classList.add('bg-orange-400', 'text-white');
                    setTimeout(() => skCopy.classList.remove('bg-orange-400', 'text-white'), 500);
                });
            }
            if (skToggle && !skToggle.dataset.bound) {
                skToggle.dataset.bound = '1';
                skToggle.addEventListener('click', () => {
                    if (skValue.type === 'password') {
                        skValue.type = 'text';
                        skToggle.textContent = 'Hide';
                    } else {
                        skValue.type = 'password';
                        skToggle.textContent = 'Show';
                    }
                    skToggle.classList.add('bg-orange-400');
                    setTimeout(() => skToggle.classList.remove('bg-orange-400'), 500);
                });
            }
        } else {
            skContainer.classList.add('hidden');
        }
    }

    if (profile.banner) {
        const banner = $('profile-banner');
        if (banner) {
            banner.style.backgroundImage = `url(${profile.banner})`;
            banner.classList.remove('skeleton', 'hidden');
        }
    }

    if (profile.picture) {
        const avatar = $('profile-avatar');
        if (avatar) {
            avatar.src = profile.picture;
            avatar.classList.remove('skeleton', 'hidden');
        }
    }

    const displayNameEl = $('profile-displayname');
    if (displayNameEl) {
        displayNameEl.textContent = profile.display_name || `@${profile.name}`;
        displayNameEl.classList.remove('skeleton');
    }

    const nameEl = $('profile-name');
    if (nameEl) {
        if (profile.display_name && profile.name !== profile.display_name) {
            nameEl.textContent = `@${profile.name}`;
            nameEl.classList.remove('skeleton');
        } else {
            nameEl.classList.add('hidden');
        }
    }

    if (profile.website) {
        const urlEl = $('profile-url');
        if (urlEl) {
            urlEl.textContent = profile.website;
            urlEl.href = profile.website;
            urlEl.classList.remove('hidden');
        }
    }

    if (profile.nip05) {
        const nip05El = $('profile-nip05');
        if (nip05El) {
            nip05El.textContent = profile.nip05;
            nip05El.classList.remove('hidden');
        }
    }

    if (profile.lud16) {
        const lud16El = $('profile-lud16');
        if (lud16El) {
            lud16El.textContent = profile.lud16;
            lud16El.classList.remove('hidden');
        }
    }

    if (profile.about) {
        const aboutEl = $('profile-about');
        if (aboutEl) {
            aboutEl.textContent = profile.about;
            aboutEl.classList.remove('hidden');
        }
    }
}

export function initNostrAuth() {
    document.addEventListener('DOMContentLoaded', () => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const pubkeyMeta = document.querySelector('meta[name="nostr-pubkey"]')?.content;
        const storedPubkey = StorageClient.getNostrPubkey();

        if (pubkeyMeta && !storedPubkey) {
            StorageClient.setNostrPubkey(pubkeyMeta);
        }

        if (pubkeyMeta) {
            updateNostrLogoutLabel(pubkeyMeta);
        }

        document.getElementById('nostr-login-btn')?.addEventListener('click', () => {
            if (window.nostrLoginModal?.open) {
                window.nostrLoginModal.open();
            } else {
                alert('Login modal not found');
            }
        });

        document.querySelector('form[action*="nostr/logout"]')?.addEventListener('submit', async e => {
            e.preventDefault();
            const form = e.target.closest('form');
            await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
            });
            StorageClient.clearNostrPubkey();
            StorageClient.clearNostrProfile();
            StorageClient.clearNostrPrivkey();
            window.location.reload();
        });
    });
}
