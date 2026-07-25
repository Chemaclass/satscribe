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
    DEFAULT_RELAYS,
} from './nostr';
import { nip19, getPublicKey, getSignature, getEventHash, generatePrivateKey } from 'nostr-tools';
import { classifyPromptInput, isBlockInput, promptInputIcon, promptInputLabel } from './prompt-input';
import { modelPicker } from './model-picker';

window.Alpine = Alpine;
window.StorageClient = StorageClient;
window.PromptInput = { classifyPromptInput, isBlockInput, promptInputIcon, promptInputLabel };
// x-data="modelPicker(...)" resolves this global, so it must be set before Alpine.start().
window.modelPicker = modelPicker;
window.nostrTools = { nip19, getPublicKey, getSignature, getEventHash, generatePrivateKey };
window.DEFAULT_RELAYS = DEFAULT_RELAYS;
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
