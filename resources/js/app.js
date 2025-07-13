import './bootstrap';
import Alpine from 'alpinejs';
import StorageClient from './storage-client';
import { initUI } from './ui';
import {
    initNostrAuth,
    publishProfileEvent,
    fetchNostrProfile,
    fetchRelayList,
    updateNostrLogoutLabel,
    publishProfileMetadata,
    publishRelayList,
    initProfileEdit,
} from './nostr';
import { nip19, getPublicKey, getSignature, getEventHash, generatePrivateKey } from 'nostr-tools';

window.Alpine = Alpine;
window.StorageClient = StorageClient;
window.nostrTools = { nip19, getPublicKey, getSignature, getEventHash, generatePrivateKey };
window.publishProfileEvent = publishProfileEvent;
window.fetchNostrProfile = fetchNostrProfile;
window.fetchRelayList = fetchRelayList;
window.updateNostrLogoutLabel = updateNostrLogoutLabel;
window.publishProfileMetadata = publishProfileMetadata;
window.publishRelayList = publishRelayList;
window.initProfileEdit = initProfileEdit;
Alpine.start();

initUI();
initNostrAuth();
