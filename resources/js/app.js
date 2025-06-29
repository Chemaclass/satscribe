import './bootstrap';
import Alpine from 'alpinejs';
import StorageClient from './storage-client';
import { initUI } from './ui';
import { initNostrAuth } from './nostr';

window.Alpine = Alpine;
window.StorageClient = StorageClient;
Alpine.start();

initUI();
initNostrAuth();
