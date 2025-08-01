import { nip19, SimplePool, getEventHash, getSignature, getPublicKey } from 'nostr-tools';
import StorageClient from './storage-client';
import { refreshIcons } from './icons';

export const DEFAULT_RELAYS = [
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
    'wss://relay.primal.net/',
];

export function getRelays() {
    const relays = [...DEFAULT_RELAYS];
    const custom = StorageClient.getRelays();
    if (Array.isArray(custom)) {
        custom.forEach(r => {
            if (typeof r === 'string' && r && !relays.includes(r)) {
                relays.push(r);
            }
        });
    }
    return relays;
}

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
        const relays = getRelays();
        const event = await Promise.race([
            pool.get(relays, { kinds: [0], authors: [hex], limit: 1 }),
            new Promise(resolve => setTimeout(() => resolve(null), 3000)),
        ]);
        pool.close(relays);

        if (event?.content) {
            try {
                const data = JSON.parse(event.content);
                return {
                    name: data.name ?? null,
                    display_name: data.display_name ?? null,
                    about: data.about ?? null,
                    picture: data.picture || data.image || null,
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

export async function fetchRelayList(pubkey) {
    let hex = pubkey;
    if (pubkey.startsWith('npub')) {
        try {
            hex = nip19.decode(pubkey).data;
        } catch (e) {
            console.error('Failed to decode npub', e);
            return [];
        }
    }

    try {
        const pool = new SimplePool();
        const relays = getRelays();
        const event = await Promise.race([
            pool.get(relays, { kinds: [10002], authors: [hex], limit: 1 }),
            new Promise(resolve => setTimeout(() => resolve(null), 3000)),
        ]);
        pool.close(relays);

        if (event?.tags) {
            return event.tags
                .filter(t => t[0] === 'r' && typeof t[1] === 'string')
                .map(t => t[1]);
        }
    } catch (e) {
        console.error('Failed to fetch relay list', e);
    }

    return [];
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
            const relays = getRelays();
            const pub = pool.publish(relays, event);

            let finished = false;
            const finish = () => {
                if (!finished) {
                    finished = true;
                    setTimeout(() => pool.close(relays), 100);
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
    let npubStr = pubkey;
    if (npubEl) {
        try {
            const npub = window.nostrTools.nip19.npubEncode
                ? window.nostrTools.nip19.npubEncode(pubkey)
                : window.nostrTools.nip19.encode({ type: 'npub', data: pubkey });
            npubEl.textContent = npub;
            npubStr = npub;
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
    const copyPub = $('copy-pubkey');
    const copyNpub = $('copy-npub');
    const tipPub = $('copy-pubkey-tooltip');
    const tipNpub = $('copy-npub-tooltip');
    const tipSk = $('secret-key-copy-tooltip');

    function bindOnce(button, handler) {
        if (button && !button.dataset.bound) {
            button.dataset.bound = '1';
            button.addEventListener('click', handler);
        }
    }

    if (skContainer && skValue) {
        if (sk) {
            skValue.value = sk;
            skValue.type = 'password';
            skContainer.classList.remove('hidden');

            bindOnce(skDelete, () => {
                StorageClient.clearNostrPrivkey();
                skContainer.classList.add('hidden');
            });

            bindOnce(skCopy, () => {
                navigator.clipboard.writeText(skValue.value).catch(() => {});
                if (tipSk) {
                    tipSk.style.display = 'block';
                    requestAnimationFrame(() => tipSk.classList.add('opacity-100'));
                    setTimeout(() => {
                        tipSk.classList.remove('opacity-100');
                        setTimeout(() => {
                            tipSk.style.display = 'none';
                        }, 200);
                    }, 1000);
                }
            });

            bindOnce(skToggle, () => {
                const isHidden = skValue.type === 'password';
                skValue.type = isHidden ? 'text' : 'password';
                skToggle.textContent = isHidden ? 'Hide' : 'Show';
            });
        } else {
            skContainer.classList.add('hidden');
        }
    }

    bindOnce(copyPub, () => {
        navigator.clipboard.writeText(pubkey).catch(() => {});
        if (tipPub) {
            tipPub.style.display = 'block';
            requestAnimationFrame(() => tipPub.classList.add('opacity-100'));
            setTimeout(() => {
                tipPub.classList.remove('opacity-100');
                setTimeout(() => {
                    tipPub.style.display = 'none';
                }, 200);
            }, 1000);
        }
    });

    bindOnce(copyNpub, () => {
        navigator.clipboard.writeText(npubStr).catch(() => {});
        if (tipNpub) {
            tipNpub.style.display = 'block';
            requestAnimationFrame(() => tipNpub.classList.add('opacity-100'));
            setTimeout(() => {
                tipNpub.classList.remove('opacity-100');
                setTimeout(() => {
                    tipNpub.style.display = 'none';
                }, 200);
            }, 1000);
        }
    });

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

    const relaysList = $('relays-list');
    if (relaysList) {
        relaysList.innerHTML = '';
        let relays = StorageClient.getRelays();
        if (relays.length === 0) {
            relays = await fetchRelayList(pubkey);
            if (relays.length > 0) {
                StorageClient.setRelays(relays);
            }
        }
        if (relays.length === 0) {
            const li = document.createElement('li');
            li.textContent = 'No custom relays';
            relaysList.appendChild(li);
        } else {
            relays.forEach(r => {
                const li = document.createElement('li');
                li.textContent = r;
                li.className = 'break-all';
                relaysList.appendChild(li);
            });
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

        if (!pubkeyMeta && storedPubkey) {
            StorageClient.clearNostr();
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
            StorageClient.clearNostr();
            window.location.reload();
        });
    });
}

function publishSignedEvent(event) {
    return new Promise(resolve => {
        try {
            const pool = new SimplePool();
            const relays = getRelays();
            const pub = pool.publish(relays, event);

            let finished = false;
            const finish = () => {
                if (!finished) {
                    finished = true;
                    setTimeout(() => pool.close(relays), 100);
                    resolve();
                }
            };

            pub.on('ok', finish);
            pub.on('seen', finish);
            pub.on('failed', finish);
            setTimeout(finish, 3000);
        } catch (e) {
            console.error('Failed to publish event', e);
            resolve();
        }
    });
}

export function publishProfileMetadata(privkey, metadata) {
    const pubkey = getPublicKey(privkey);
    const event = {
        kind: 0,
        pubkey,
        created_at: Math.floor(Date.now() / 1000),
        content: JSON.stringify(metadata),
        tags: [],
    };
    event.id = getEventHash(event);
    event.sig = getSignature(event, privkey);

    return publishSignedEvent(event);
}

export function publishRelayList(privkey, relays) {
    const pubkey = getPublicKey(privkey);
    const event = {
        kind: 10002,
        pubkey,
        created_at: Math.floor(Date.now() / 1000),
        content: '',
        tags: relays.map(r => ['r', r]),
    };
    event.id = getEventHash(event);
    event.sig = getSignature(event, privkey);

    return publishSignedEvent(event);
}

export async function initProfileEdit() {
    const form = document.getElementById('nostr-profile-form');
    if (!form) return;

    const pubkey = document.querySelector('meta[name="nostr-pubkey"]')?.content;
    if (!pubkey) return;

    const profile = await getOrFetchProfile(pubkey);
    const fields = ['name','display_name','about','picture','banner','website','nip05','lud16'];

    const pictureInput = document.getElementById('edit-picture');
    const bannerInput = document.getElementById('edit-banner');
    const picturePreview = document.getElementById('picture-preview');
    const bannerPreview = document.getElementById('banner-preview');

    function updatePreview(input, img) {
        if (!img) return;
        const url = input.value.trim();
        if (!url) {
            img.classList.add('hidden');
            img.removeAttribute('src');
            return;
        }
        img.onload = () => img.classList.remove('hidden');
        img.onerror = () => {
            img.classList.add('hidden');
            img.removeAttribute('src');
        };
        img.src = url;
    }

    if (profile) {
        fields.forEach(f => {
            const input = document.getElementById(`edit-${f}`);
            if (input && profile[f]) input.value = profile[f];
        });
    }

    if (pictureInput) {
        pictureInput.addEventListener('input', () => updatePreview(pictureInput, picturePreview));
        updatePreview(pictureInput, picturePreview);
    }

    if (bannerInput) {
        bannerInput.addEventListener('input', () => updatePreview(bannerInput, bannerPreview));
        updatePreview(bannerInput, bannerPreview);
    }

    const relaysContainer = document.getElementById('edit-relays-list');
    const addRelayBtn = document.getElementById('add-relay');
    const newRelayInput = document.getElementById('new-relay');

    let relays = StorageClient.getRelays();
    if (relays.length === 0) {
        relays = await fetchRelayList(pubkey);
        if (relays.length > 0) {
            StorageClient.setRelays(relays);
        }
    }

    function renderRelays() {
        if (!relaysContainer) return;
        relaysContainer.innerHTML = '';
        const relays = StorageClient.getRelays();
        if (relays.length === 0) {
            const li = document.createElement('li');
            li.textContent = 'No custom relays';
            relaysContainer.appendChild(li);
        } else {
            relays.forEach((r, idx) => {
                const li = document.createElement('li');
                li.className = 'flex items-center gap-2';

                const input = document.createElement('input');
                input.type = 'text';
                input.value = r;
                input.className = 'flex-1 p-1 border rounded';
                input.addEventListener('change', () => {
                    const all = StorageClient.getRelays();
                    const val = input.value.trim();
                    if (val) {
                        all[idx] = val;
                    } else {
                        all.splice(idx, 1);
                    }
                    StorageClient.setRelays(all);
                    renderRelays();
                });

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.textContent = 'Remove';
                remove.className = 'px-2 py-1 text-sm rounded link border';
                remove.addEventListener('click', () => {
                    const all = StorageClient.getRelays();
                    all.splice(idx, 1);
                    StorageClient.setRelays(all);
                    renderRelays();
                });

                li.appendChild(input);
                li.appendChild(remove);
                relaysContainer.appendChild(li);
            });
        }
    }

    if (addRelayBtn && newRelayInput) {
        addRelayBtn.addEventListener('click', () => {
            const relay = newRelayInput.value.trim();
            if (relay) {
                StorageClient.addRelay(relay);
                newRelayInput.value = '';
                renderRelays();
            }
        });
    }

    renderRelays();

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const data = {};
        fields.forEach(f => {
            const val = document.getElementById(`edit-${f}`)?.value.trim();
            if (val) data[f] = val;
        });

        console.log('Submitting profile update', data);

        const relays = StorageClient.getRelays();
        let sk = StorageClient.getNostrPrivkey();

        if (!sk && window.nostr?.signEvent) {
            try {
                const pk = await window.nostr.getPublicKey();
                const event = {
                    kind: 0,
                    pubkey: pk,
                    created_at: Math.floor(Date.now() / 1000),
                    content: JSON.stringify(data),
                    tags: [],
                };
                const signed = await window.nostr.signEvent(event);
                event.id = signed.id;
                event.sig = signed.sig;
                await publishSignedEvent(event);

                const rEvent = {
                    kind: 10002,
                    pubkey: pk,
                    created_at: Math.floor(Date.now() / 1000),
                    content: '',
                    tags: relays.map(r => ['r', r]),
                };
                const signedRelays = await window.nostr.signEvent(rEvent);
                rEvent.id = signedRelays.id;
                rEvent.sig = signedRelays.sig;
                await publishSignedEvent(rEvent);

                StorageClient.setNostrProfile(data);
                StorageClient.setRelays(relays);
                window.location.href = '/profile';
                return;
            } catch (err) {
                console.error('Failed to sign with extension', err);
                alert('Failed to update profile.');
                return;
            }
        }

        if (!sk) {
            sk = prompt('Enter your private key to update your profile');
        }

        if (sk) {
            if (sk.startsWith('nsec')) {
                try { sk = nip19.decode(sk).data; } catch {}
            }
            await publishProfileMetadata(sk, data);
            await publishRelayList(sk, relays);
            StorageClient.setNostrProfile(data);
            StorageClient.setRelays(relays);
            window.location.href = '/profile';
        } else {
            alert('No private key provided.');
        }
    });
}
