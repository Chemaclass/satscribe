import './bootstrap';
import Alpine from 'alpinejs';
import StorageClient from './storage-client';
import { initUI } from './ui';
import { initNostrAuth, publishProfileEvent } from './nostr';
import { nip19, getPublicKey, getSignature, getEventHash, generatePrivateKey } from 'nostr-tools';

window.Alpine = Alpine;
window.StorageClient = StorageClient;
window.nostrTools = { nip19, getPublicKey, getSignature, getEventHash, generatePrivateKey };
window.publishProfileEvent = publishProfileEvent;
Alpine.start();

initUI();
initNostrAuth();
