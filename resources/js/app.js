import './bootstrap';
import Alpine from 'alpinejs';
import StorageClient from './storage-client';
import { initUI } from './ui';
import { initNostrAuth } from './nostr';
import { nip19, getPublicKey, getSignature, getEventHash } from 'nostr-tools';

window.Alpine = Alpine;
window.StorageClient = StorageClient;
window.nostrTools = { nip19, getPublicKey, getSignature, getEventHash };
Alpine.start();

initUI();
initNostrAuth();
